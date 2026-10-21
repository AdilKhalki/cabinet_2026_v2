<?php

namespace Tests\Feature\Consultation;

use App\Livewire\Appointment\WaitingRoom;
use App\Livewire\Consultation\ConsultationForm;
use App\Livewire\Consultation\ConsultationShow;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class ConsultationWorkflowTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(9, 55));

        $this->seedRoles();
        $this->doctor = $this->doctor(['name' => 'Alami']);
        $this->patient = $this->makePatient(['first_name' => 'Salma', 'last_name' => 'Idrissi']);
    }

    private function waitingAppointment(array $attrs = []): Appointment
    {
        return Appointment::create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'scheduled_at' => today()->setTime(10, 0),
            'duration_minutes' => 30,
            'consultation_type' => 'followup',
            'status' => 'waiting',
            'arrived_at' => now(),
            'notes' => 'Douleurs abdominales',
        ], $attrs));
    }

    public function test_doctor_runs_a_consultation_from_the_waiting_room_to_completion(): void
    {
        $appt = $this->waitingAppointment();
        $this->actingAs($this->doctor);

        // 1. Le médecin appelle le patient
        Livewire::test(WaitingRoom::class)
            ->call('callNext', $appt->id)
            ->assertSee(route('consultations.create', ['appointmentId' => $appt->id]), false);
        $this->assertSame('in_progress', $appt->fresh()->status);

        // 2. Il ouvre le formulaire depuis le lien « Consulter » (query string)
        $this->get(route('consultations.create', ['appointmentId' => $appt->id]))->assertOk();

        $form = Livewire::withQueryParams(['appointmentId' => $appt->id])
            ->test(ConsultationForm::class)
            ->assertSet('appointment_id', $appt->id)
            ->assertSet('patient_id', $this->patient->id)
            ->assertSet('doctor_id', $this->doctor->id)
            ->assertSet('consultation_type', 'followup')
            ->assertSet('reason', 'Douleurs abdominales');

        // 3. Signes vitaux + diagnostic, enregistré « en cours »
        $form->set('weight_kg', '68.5')
            ->set('height_cm', '165')
            ->set('blood_pressure', '125/80')
            ->set('heart_rate', '78')
            ->set('temperature', '37.2')
            ->set('spo2', '98')
            ->set('diagnosis', 'Gastrite aiguë')
            ->set('icd10_code', 'k29.1')
            ->call('save', false)
            ->assertHasNoErrors()
            ->assertRedirect();

        $consultation = Consultation::sole();
        $this->assertSame($appt->id, $consultation->appointment_id);
        $this->assertSame('in_progress', $consultation->status);
        $this->assertSame('K29.1', $consultation->icd10_code);
        $this->assertSame(25.2, $consultation->bmi);
        $this->assertSame($this->doctor->id, $consultation->created_by);
        $this->assertSame('in_progress', $appt->fresh()->status);

        // Reprendre depuis la salle d'attente pointe vers l'édition, pas un doublon
        Livewire::withQueryParams(['appointmentId' => $appt->id])
            ->test(ConsultationForm::class)
            ->assertRedirect(route('consultations.edit', $consultation));

        // 4. Clôture depuis la fiche consultation
        $this->travel(15)->minutes();
        Livewire::test(ConsultationShow::class, ['consultation' => $consultation])
            ->call('complete')
            ->assertSee('Terminée');

        $this->assertSame('completed', $consultation->fresh()->status);
        $appt->refresh();
        $this->assertSame('completed', $appt->status);
        $this->assertNotNull($appt->ended_at);
        $this->assertSame(1, Consultation::count());
    }

    public function test_save_and_finish_completes_the_linked_appointment(): void
    {
        $appt = $this->waitingAppointment(['status' => 'confirmed', 'arrived_at' => null]);
        $this->actingAs($this->doctor);

        Livewire::test(ConsultationForm::class, ['appointmentId' => $appt->id])
            ->set('diagnosis', 'Rhinopharyngite')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('completed', Consultation::sole()->status);
        $appt->refresh();
        $this->assertSame('completed', $appt->status);
        $this->assertNotNull($appt->started_at);
        $this->assertNotNull($appt->ended_at);
    }

    public function test_validation_of_vitals_and_followup(): void
    {
        $this->actingAs($this->doctor);

        Livewire::test(ConsultationForm::class)
            ->set('patient_id', $this->patient->id)
            ->set('temperature', '50')
            ->set('spo2', '120')
            ->set('blood_pressure', 'haute')
            ->set('followup_required', true)
            ->call('save')
            ->assertHasErrors(['temperature', 'spo2', 'blood_pressure', 'followup_date']);

        $this->assertSame(0, Consultation::count());
    }

    public function test_cannot_link_a_consultation_to_another_patients_appointment(): void
    {
        $appt = $this->waitingAppointment();
        $this->actingAs($this->doctor);

        Livewire::test(ConsultationForm::class, ['appointmentId' => $appt->id])
            ->set('patient_id', $this->makePatient()->id)
            ->call('save')
            ->assertHasErrors('patient_id');

        $this->assertSame(0, Consultation::count());
    }

    public function test_editing_keeps_author_and_doctor(): void
    {
        $colleague = $this->doctor();
        $consultation = Consultation::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'consultation_date' => now(),
            'created_by' => $this->doctor->id,
        ]);

        $this->actingAs($admin = $this->admin());
        Livewire::test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('doctor_id', $colleague->id)
            ->set('diagnosis', 'Corrigé par admin')
            ->call('save')
            ->assertHasNoErrors();

        $consultation->refresh();
        $this->assertSame($this->doctor->id, $consultation->doctor_id);
        $this->assertSame($this->doctor->id, $consultation->created_by);
        $this->assertSame('Corrigé par admin', $consultation->diagnosis);
    }

    public function test_secretary_cannot_create_or_view_consultations(): void
    {
        $secretary = $this->secretary();
        $consultation = Consultation::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'consultation_date' => now(),
        ]);

        $this->actingAs($secretary);
        $this->get(route('consultations.index'))->assertForbidden();
        $this->get(route('consultations.create'))->assertForbidden();
        $this->get(route('consultations.show', $consultation))->assertForbidden();

        // Appel direct du composant (contourne le middleware de route)
        Livewire::test(ConsultationForm::class)->assertForbidden();
        Livewire::test(ConsultationShow::class, ['consultation' => $consultation])->assertForbidden();
    }

    public function test_doctor_cannot_open_or_edit_a_colleagues_consultation(): void
    {
        $colleague = $this->doctor();
        $consultation = Consultation::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $colleague->id,
            'consultation_date' => now(),
        ]);

        $this->actingAs($this->doctor);
        $this->get(route('consultations.show', $consultation))->assertForbidden();
        $this->get(route('consultations.edit', $consultation))->assertForbidden();

        $this->actingAs($colleague);
        $this->get(route('consultations.show', $consultation))->assertOk()->assertSee('Salma Idrissi');
        $this->get(route('consultations.edit', $consultation))->assertOk();

        $this->actingAs($this->admin());
        $this->get(route('consultations.show', $consultation))->assertOk();
    }

    public function test_doctor_cannot_create_consultation_for_a_colleagues_appointment(): void
    {
        $colleague = $this->doctor();
        $appt = $this->waitingAppointment(['doctor_id' => $colleague->id]);

        $this->actingAs($this->doctor);
        Livewire::test(ConsultationForm::class, ['appointmentId' => $appt->id])->assertForbidden();
    }

    public function test_index_and_create_pages_render(): void
    {
        Consultation::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'consultation_date' => now(),
            'consultation_type' => 'emergency',
        ]);

        $this->actingAs($this->doctor);
        $this->get(route('consultations.index'))->assertOk()->assertSee('Urgence')->assertSee('En cours');
        $this->get(route('consultations.create', ['patient' => $this->patient->id]))->assertOk();
    }
}
