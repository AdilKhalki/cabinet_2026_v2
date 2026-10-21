<?php

namespace Tests\Feature\Patient;

use App\Livewire\Patient\PatientShow;
use App\Models\Consultation;
use App\Models\MedicalHistory;
use App\Models\MedicalImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class PatientFileTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->withoutVite();
    }

    public function test_doctor_views_patient_file_with_consultations_and_history_tabs(): void
    {
        $doctor = $this->doctor(['name' => 'Dr Berrada']);
        $patient = $this->makePatient(['known_allergies' => 'Pénicilline']);
        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now()->subMonth(),
            'reason' => 'Ancienne visite',
        ]);
        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now(),
            'reason' => 'Fièvre et toux',
            'status' => 'completed',
        ]);
        MedicalHistory::create([
            'patient_id' => $patient->id,
            'type' => 'medical',
            'title' => 'Hypertension artérielle',
        ]);

        $this->actingAs($doctor);

        Livewire::test(PatientShow::class, ['patient' => $patient])
            ->assertOk()
            ->assertSee('Pénicilline')
            ->assertSee('Consultations (2)')
            ->assertSee('Imagerie (0)')
            // La « dernière consultation » est bien la plus récente
            ->assertSee('Fièvre et toux')
            ->call('setTab', 'consultations')
            ->assertSet('activeTab', 'consultations')
            ->assertSee('Ancienne visite')
            ->assertSee('Dr Berrada')
            ->assertSee('Terminée')
            ->call('setTab', 'history')
            ->assertSee('Hypertension artérielle')
            ->assertSee('Antécédent médical');
    }

    public function test_secretary_cannot_open_clinical_tabs(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now(),
            'diagnosis' => 'Diagnostic confidentiel',
        ]);
        MedicalImage::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'category' => 'mri',
            'title' => 'IRM confidentielle',
            'file_path' => 'medical-images/x.jpg',
            'file_type' => 'jpg',
            'image_date' => today(),
        ]);

        $this->actingAs($this->secretary());

        $component = Livewire::test(PatientShow::class, ['patient' => $patient])
            ->assertOk()
            ->assertDontSee('Diagnostic confidentiel')
            ->assertDontSee('Imagerie (')
            ->assertSee('Documents (0)')
            ->assertSee('Paiements (0)');

        foreach (['consultations', 'images', 'history', 'prescriptions', 'lab-tests', 'reports', 'inexistant'] as $tab) {
            $component->call('setTab', $tab)->assertSet('activeTab', 'overview');
        }

        $component->assertDontSee('IRM confidentielle')->assertDontSee('Diagnostic confidentiel');

        // Onglets autorisés pour la secrétaire
        $component->call('setTab', 'documents')->assertSet('activeTab', 'documents');
        $component->call('setTab', 'payments')->assertSet('activeTab', 'payments');
    }

    public function test_doctor_adds_and_deletes_medical_history_entries(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $this->actingAs($doctor);

        $component = Livewire::test(PatientShow::class, ['patient' => $patient])
            ->call('setTab', 'history')
            ->call('openHistoryForm')
            ->assertSet('showHistoryForm', true)
            ->call('saveHistory')
            ->assertHasErrors(['history_title' => 'required'])
            ->set('history_type', 'vaccination')
            ->set('history_title', 'Rappel DTP')
            ->set('history_vaccine_name', 'DTP')
            ->set('history_vaccine_date', '2026-01-10')
            ->set('history_vaccine_next_date', '2025-01-10')
            ->call('saveHistory')
            ->assertHasErrors(['history_vaccine_next_date'])
            ->set('history_vaccine_next_date', '2036-01-10')
            ->set('history_relation', 'ignoré pour un vaccin')
            ->call('saveHistory')
            ->assertHasNoErrors()
            ->assertSet('showHistoryForm', false)
            ->assertSee('Rappel DTP');

        $history = $patient->medicalHistories()->firstOrFail();
        $this->assertSame('vaccination', $history->type);
        $this->assertSame('DTP', $history->vaccine_name);
        $this->assertSame('2036-01-10', $history->vaccine_next_date->format('Y-m-d'));
        $this->assertNull($history->relation);
        $this->assertSame($doctor->id, $history->created_by);

        $component->call('openHistoryForm')
            ->set('history_type', 'family')
            ->set('history_title', 'Diabète')
            ->set('history_relation', 'Père')
            ->call('saveHistory')
            ->assertHasNoErrors()
            ->assertSee('Père');

        $component->call('deleteHistory', $history->id)->assertDontSee('Rappel DTP');
        $this->assertSoftDeleted($history);
    }

    public function test_secretary_cannot_add_medical_history(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($this->secretary());

        Livewire::test(PatientShow::class, ['patient' => $patient])
            ->call('openHistoryForm')
            ->assertForbidden();

        Livewire::test(PatientShow::class, ['patient' => $patient])
            ->set('history_title', 'Tentative')
            ->call('saveHistory')
            ->assertForbidden();

        $this->assertSame(0, MedicalHistory::count());
    }

    public function test_cannot_delete_history_of_another_patient(): void
    {
        $patient = $this->makePatient();
        $other = $this->makePatient();
        $foreign = MedicalHistory::create(['patient_id' => $other->id, 'type' => 'allergy', 'title' => 'Arachide']);

        $this->actingAs($this->admin());

        try {
            Livewire::test(PatientShow::class, ['patient' => $patient])
                ->call('deleteHistory', $foreign->id);
            $this->fail('La suppression aurait dû échouer');
        } catch (ModelNotFoundException) {
            // attendu : l'antécédent n'appartient pas à ce patient
        }

        $this->assertNotSoftDeleted($foreign);
    }

    public function test_patient_show_page_renders_over_http_for_each_role(): void
    {
        $patient = $this->makePatient();

        foreach (['admin', 'medecin', 'secretaire'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('patients.show', $patient))
                ->assertOk()
                ->assertSee($patient->dossier_number);
        }
    }
}
