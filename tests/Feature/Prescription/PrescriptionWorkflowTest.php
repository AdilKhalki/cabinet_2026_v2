<?php

namespace Tests\Feature\Prescription;

use App\Livewire\Prescription\PrescriptionForm;
use App\Livewire\Prescription\PrescriptionIndex;
use App\Livewire\Prescription\PrescriptionShow;
use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class PrescriptionWorkflowTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->seedRoles();
    }

    private function makePrescription($doctor, $patient): Prescription
    {
        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'prescribed_at' => today(),
        ]);
        $prescription->lines()->create(['medication_name' => 'Doliprane 1000mg', 'sort_order' => 0]);

        return $prescription;
    }

    public function test_doctor_creates_prescription_with_several_lines_from_consultation(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now(),
        ]);
        $med = Medication::create(['name' => 'Amoxicilline', 'strength' => '500mg', 'is_active' => true]);

        $this->actingAs($doctor)
            ->get(route('prescriptions.create', ['patient' => $patient->id, 'consultation' => $consultation->id]))
            ->assertOk();

        $component = Livewire::actingAs($doctor)
            ->withQueryParams(['patient' => $patient->id, 'consultation' => $consultation->id])
            ->test(PrescriptionForm::class)
            ->assertSet('patient_id', $patient->id)
            ->assertSet('consultation_id', $consultation->id)
            ->assertSet('doctor_id', $doctor->id)
            ->call('selectMedication', 0, (string) $med->id)
            ->assertSet('lines.0.medication_name', 'Amoxicilline 500mg')
            ->assertSet('lines.0.medication_id', $med->id)
            ->set('lines.0.dosage', '1 gélule')
            ->set('lines.0.frequency', '3 fois/jour')
            ->set('lines.0.duration_days', '7')
            ->set('lines.0.quantity', '2')
            ->call('addLine')
            ->set('lines.1.medication_name', 'Doliprane 1000mg')
            ->call('addLine')
            ->set('lines.2.medication_name', 'À supprimer')
            ->call('removeLine', 2)
            ->assertCount('lines', 2)
            ->set('valid_until', today()->addMonth()->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $prescription = Prescription::with('lines')->sole();
        $component->assertRedirect(route('prescriptions.show', $prescription));

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-00001$/', $prescription->prescription_number);
        $this->assertSame($consultation->id, $prescription->consultation_id);
        $this->assertSame($doctor->id, $prescription->doctor_id);
        $this->assertSame(['Amoxicilline 500mg', 'Doliprane 1000mg'], $prescription->lines->pluck('medication_name')->all());
        $this->assertSame([0, 1], $prescription->lines->pluck('sort_order')->all());
        $this->assertSame(2, $prescription->lines[0]->quantity);
        $this->assertSame($med->id, $prescription->lines[0]->medication_id);
    }

    public function test_doctor_cannot_prescribe_in_another_doctors_name(): void
    {
        $doctor = $this->doctor();
        $other = $this->doctor();
        $patient = $this->makePatient();

        Livewire::actingAs($doctor)
            ->test(PrescriptionForm::class)
            ->set('patient_id', $patient->id)
            ->set('doctor_id', $other->id)
            ->set('lines.0.medication_name', 'Doliprane')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($doctor->id, Prescription::sole()->doctor_id);
    }

    public function test_validation_rejects_bad_dates_and_non_numeric_quantities(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();

        Livewire::actingAs($doctor)
            ->test(PrescriptionForm::class)
            ->set('patient_id', $patient->id)
            ->set('prescribed_at', '2026-05-10')
            ->set('valid_until', '2026-05-01')
            ->set('lines.0.medication_name', '')
            ->set('lines.0.quantity', '1 boîte')
            ->set('lines.0.duration_days', 'abc')
            ->call('save')
            ->assertHasErrors(['valid_until', 'lines.0.medication_name', 'lines.0.quantity', 'lines.0.duration_days']);

        $this->assertSame(0, Prescription::count());
    }

    public function test_consultation_must_belong_to_patient(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $otherPatient = $this->makePatient();
        $consultation = Consultation::create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now(),
        ]);

        Livewire::actingAs($doctor)
            ->test(PrescriptionForm::class)
            ->set('patient_id', $patient->id)
            ->set('consultation_id', $consultation->id)
            ->set('lines.0.medication_name', 'Doliprane')
            ->call('save')
            ->assertHasErrors(['consultation_id']);
    }

    public function test_doctor_edits_prescription_and_lines_are_replaced_in_order(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $prescription = $this->makePrescription($doctor, $patient);

        $this->actingAs($doctor)->get(route('prescriptions.edit', $prescription))->assertOk();

        Livewire::actingAs($doctor)
            ->test(PrescriptionForm::class, ['prescription' => $prescription])
            ->assertSet('isEdit', true)
            ->assertSet('lines.0.medication_name', 'Doliprane 1000mg')
            ->call('addLine')
            ->set('lines.1.medication_name', 'Ibuprofène 400mg')
            ->set('notes', 'Boire beaucoup d\'eau')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('prescriptions.show', $prescription));

        $prescription->refresh();
        $this->assertSame('Boire beaucoup d\'eau', $prescription->notes);
        $this->assertSame(['Doliprane 1000mg', 'Ibuprofène 400mg'], $prescription->lines->pluck('medication_name')->all());
        $this->assertSame(2, $prescription->lines()->count());
    }

    public function test_doctor_prints_own_prescription_pdf(): void
    {
        $doctor = $this->doctor();
        $prescription = $this->makePrescription($doctor, $this->makePatient(['known_allergies' => 'Pénicilline']));

        $this->actingAs($doctor)->get(route('prescriptions.show', $prescription))
            ->assertOk()
            ->assertSee($prescription->prescription_number);

        $response = $this->actingAs($doctor)->get(route('prescriptions.pdf', $prescription));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertTrue($prescription->fresh()->is_printed);
    }

    public function test_other_doctor_cannot_view_edit_print_or_delete_prescription(): void
    {
        $owner = $this->doctor();
        $other = $this->doctor();
        $prescription = $this->makePrescription($owner, $this->makePatient());

        $this->actingAs($other)->get(route('prescriptions.pdf', $prescription))->assertForbidden();
        $this->actingAs($other)->get(route('prescriptions.show', $prescription))->assertForbidden();
        $this->actingAs($other)->get(route('prescriptions.edit', $prescription))->assertForbidden();

        Livewire::actingAs($other)
            ->test(PrescriptionIndex::class)
            ->call('confirmDelete', $prescription->id)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($prescription);
        $this->assertFalse($prescription->fresh()->is_printed);
    }

    public function test_secretary_cannot_access_prescriptions(): void
    {
        $secretary = $this->secretary();
        $prescription = $this->makePrescription($this->doctor(), $this->makePatient());

        $this->actingAs($secretary)->get(route('prescriptions.index'))->assertForbidden();
        $this->actingAs($secretary)->get(route('prescriptions.pdf', $prescription))->assertForbidden();
    }

    public function test_admin_can_print_any_prescription(): void
    {
        $prescription = $this->makePrescription($this->doctor(), $this->makePatient());

        $this->actingAs($this->admin())->get(route('prescriptions.pdf', $prescription))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_index_lists_only_own_prescriptions_for_doctor(): void
    {
        $doctor = $this->doctor();
        $mine = $this->makePrescription($doctor, $this->makePatient());
        $theirs = $this->makePrescription($this->doctor(), $this->makePatient());

        Livewire::actingAs($doctor)
            ->test(PrescriptionIndex::class)
            ->assertSee($mine->prescription_number)
            ->assertDontSee($theirs->prescription_number);
    }

    public function test_show_component_mount_is_authorized(): void
    {
        $prescription = $this->makePrescription($this->doctor(), $this->makePatient());

        Livewire::actingAs($this->doctor())
            ->test(PrescriptionShow::class, ['prescription' => $prescription])
            ->assertForbidden();
    }
}
