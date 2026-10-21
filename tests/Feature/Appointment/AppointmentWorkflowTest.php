<?php

namespace Tests\Feature\Appointment;

use App\Livewire\Appointment\AppointmentIndex;
use App\Livewire\Appointment\WaitingRoom;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class AppointmentWorkflowTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    private User $doctor;

    private User $secretary;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(8, 0));

        $this->seedRoles();
        $this->doctor = $this->doctor(['name' => 'Alami']);
        $this->secretary = $this->secretary();
        $this->patient = $this->makePatient(['first_name' => 'Karim', 'last_name' => 'Bennani']);
    }

    private function book(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'scheduled_at' => today()->setTime(10, 0),
            'duration_minutes' => 30,
            'consultation_type' => 'general',
            'status' => 'planned',
        ], $overrides));
    }

    public function test_secretary_books_an_appointment_for_a_patient_with_a_doctor(): void
    {
        $this->actingAs($this->secretary);

        Livewire::test(AppointmentIndex::class)
            ->call('openForm')
            ->set('patient_id', $this->patient->id)
            ->set('doctor_id', $this->doctor->id)
            ->set('scheduled_at', '2026-10-22T09:30')
            ->set('duration_minutes', 20)
            ->set('consultation_type', 'followup')
            ->set('notes', 'Contrôle tension')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $appt = Appointment::sole();
        $this->assertSame($this->doctor->id, $appt->doctor_id);
        $this->assertSame('planned', $appt->status);
        $this->assertSame('2026-10-22 09:30', $appt->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame($this->secretary->id, $appt->created_by);

        // Le médecin est notifié
        $this->assertSame(1, $this->doctor->notifications()->count());
    }

    public function test_booking_form_can_be_prefilled_from_patient_page_and_calendar(): void
    {
        $this->actingAs($this->secretary);

        Livewire::withQueryParams(['patient' => $this->patient->id, 'date' => '2026-10-23T14:00'])
            ->test(AppointmentIndex::class)
            ->assertSet('showForm', true)
            ->assertSet('patient_id', $this->patient->id)
            ->assertSet('scheduled_at', '2026-10-23T14:00');
    }

    public function test_overlapping_appointment_for_the_same_doctor_is_rejected(): void
    {
        $this->book(['scheduled_at' => '2026-10-22 09:00:00', 'duration_minutes' => 30]);
        $other = $this->makePatient();
        $this->actingAs($this->secretary);

        $form = fn (string $at, ?int $doctorId = null) => Livewire::test(AppointmentIndex::class)
            ->call('openForm')
            ->set('patient_id', $other->id)
            ->set('doctor_id', $doctorId ?? $this->doctor->id)
            ->set('scheduled_at', $at)
            ->set('duration_minutes', 30)
            ->call('save');

        // Chevauchements : même heure, début pendant, fin pendant
        $form('2026-10-22T09:00')->assertHasErrors('scheduled_at');
        $form('2026-10-22T09:15')->assertHasErrors('scheduled_at');
        $form('2026-10-22T08:45')->assertHasErrors('scheduled_at');
        $this->assertSame(1, Appointment::count());

        // Créneaux adjacents acceptés
        $form('2026-10-22T09:30')->assertHasNoErrors();
        $form('2026-10-22T08:30')->assertHasNoErrors();

        // Un autre médecin est libre au même moment
        $form('2026-10-22T09:00', $this->doctor(['name' => 'Tazi'])->id)->assertHasNoErrors();

        $this->assertSame(4, Appointment::count());
    }

    public function test_cancelled_slot_can_be_rebooked_and_edit_does_not_conflict_with_itself(): void
    {
        $cancelled = $this->book(['scheduled_at' => '2026-10-22 11:00:00', 'status' => 'cancelled']);
        $existing = $this->book(['scheduled_at' => '2026-10-22 12:00:00']);
        $this->actingAs($this->secretary);

        Livewire::test(AppointmentIndex::class)
            ->call('openForm')
            ->set('patient_id', $this->patient->id)
            ->set('doctor_id', $this->doctor->id)
            ->set('scheduled_at', '2026-10-22T11:00')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(AppointmentIndex::class)
            ->call('openForm', $existing->id)
            ->set('duration_minutes', 45)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(45, $existing->fresh()->duration_minutes);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }

    public function test_doctor_must_be_an_active_medecin_and_past_dates_are_rejected(): void
    {
        $this->actingAs($this->secretary);

        Livewire::test(AppointmentIndex::class)
            ->call('openForm')
            ->set('patient_id', $this->patient->id)
            ->set('doctor_id', $this->secretary->id)
            ->set('scheduled_at', '2026-10-20T09:00')
            ->set('color', '"><script>')
            ->call('save')
            ->assertHasErrors(['doctor_id', 'scheduled_at', 'color']);

        $this->assertSame(0, Appointment::count());
    }

    public function test_full_status_workflow_sets_timestamps(): void
    {
        $appt = $this->book();
        $this->actingAs($this->secretary);

        $index = Livewire::test(AppointmentIndex::class);
        $index->call('updateStatus', $appt->id, 'confirmed');
        $this->assertSame('confirmed', $appt->fresh()->status);

        $this->travel(5)->minutes();
        $index->call('updateStatus', $appt->id, 'waiting');
        $appt->refresh();
        $this->assertSame('waiting', $appt->status);
        $this->assertNotNull($appt->arrived_at);

        // Le patient apparaît en salle d'attente
        $this->actingAs($this->doctor);
        $room = Livewire::test(WaitingRoom::class)->assertSee('Karim Bennani');
        $this->assertSame([$appt->id], $room->instance()->waitingPatients->modelKeys());

        $room->call('callNext', $appt->id);
        $appt->refresh();
        $this->assertSame('in_progress', $appt->status);
        $this->assertNotNull($appt->started_at);

        $this->travel(20)->minutes();
        $room->call('markCompleted', $appt->id);
        $appt->refresh();
        $this->assertSame('completed', $appt->status);
        $this->assertTrue($appt->ended_at->greaterThan($appt->started_at));
    }

    public function test_invalid_status_transitions_are_refused(): void
    {
        $appt = $this->book(['status' => 'completed']);
        $this->actingAs($this->secretary);

        Livewire::test(AppointmentIndex::class)->call('updateStatus', $appt->id, 'waiting');
        $this->assertSame('completed', $appt->fresh()->status);

        Livewire::test(AppointmentIndex::class)->call('updateStatus', $appt->id, 'bogus');
        $this->assertSame('completed', $appt->fresh()->status);
    }

    public function test_cancellation_requires_a_reason(): void
    {
        $appt = $this->book();
        $this->actingAs($this->secretary);

        $component = Livewire::test(AppointmentIndex::class)
            ->call('updateStatus', $appt->id, 'cancelled')
            ->assertSet('showCancelModal', true)
            ->assertSet('cancellingId', $appt->id)
            ->call('cancel')
            ->assertHasErrors('cancellation_reason');

        $this->assertSame('planned', $appt->fresh()->status);

        $component->set('cancellation_reason', 'Patient en voyage')
            ->call('cancel')
            ->assertHasNoErrors()
            ->assertSet('showCancelModal', false);

        $appt->refresh();
        $this->assertSame('cancelled', $appt->status);
        $this->assertSame('Patient en voyage', $appt->cancellation_reason);
        $this->assertSame(1, $this->doctor->notifications()->count());
    }

    public function test_patient_who_never_arrives_is_marked_no_show(): void
    {
        $appt = $this->book();
        $this->actingAs($this->secretary);

        Livewire::test(WaitingRoom::class)
            ->assertSee('Attendus aujourd')
            ->call('markNoShow', $appt->id);

        $this->assertSame('no_show', $appt->fresh()->status);

        // Un RDV absent ne bloque plus le créneau
        $this->assertTrue(Appointment::overlapping($this->doctor->id, today()->setTime(10, 0), 30)->isEmpty());
    }

    public function test_arrival_can_be_recorded_from_the_waiting_room(): void
    {
        $appt = $this->book();
        $this->actingAs($this->secretary);

        Livewire::test(WaitingRoom::class)
            ->call('markArrived', $appt->id)
            ->assertSet('todayStats.waiting', 1);

        $this->assertSame('waiting', $appt->fresh()->status);
        $this->assertNotNull($appt->fresh()->arrived_at);
    }

    public function test_doctor_only_sees_and_manages_own_appointments(): void
    {
        $colleague = $this->doctor(['name' => 'Tazi']);
        $mine = $this->book(['status' => 'waiting', 'arrived_at' => now()]);
        $theirs = $this->book(['doctor_id' => $colleague->id, 'status' => 'waiting', 'arrived_at' => now()]);

        $this->actingAs($this->doctor);

        $list = Livewire::test(AppointmentIndex::class);
        $this->assertSame([$mine->id], $list->instance()->appointments->getCollection()->modelKeys());

        $room = Livewire::test(WaitingRoom::class);
        $this->assertSame([$mine->id], $room->instance()->waitingPatients->modelKeys());

        // Appel direct d'une action Livewire sur le RDV d'un confrère
        Livewire::test(WaitingRoom::class)->call('callNext', $theirs->id)->assertForbidden();
        Livewire::test(AppointmentIndex::class)->call('openForm', $theirs->id)->assertForbidden();
        $this->assertSame('waiting', $theirs->fresh()->status);
    }

    public function test_doctor_cannot_delete_appointments_but_secretary_can(): void
    {
        $appt = $this->book();

        $this->actingAs($this->doctor);
        Livewire::test(AppointmentIndex::class)
            ->set('deletingId', $appt->id)
            ->call('delete')
            ->assertForbidden();
        $this->assertNotSoftDeleted($appt);

        $this->actingAs($this->secretary);
        Livewire::test(AppointmentIndex::class)
            ->call('confirmDelete', $appt->id)
            ->call('delete');
        $this->assertSoftDeleted($appt);
    }

    public function test_user_without_permission_cannot_book(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('appointments.index'))->assertForbidden();

        Livewire::test(AppointmentIndex::class)
            ->call('openForm')
            ->assertForbidden();
    }

    public function test_pages_render_for_each_role(): void
    {
        $this->book(['status' => 'waiting', 'arrived_at' => now()]);

        foreach ([$this->secretary, $this->doctor, $this->admin()] as $user) {
            $this->actingAs($user);
            $this->get(route('appointments.index'))->assertOk()->assertSee('Karim Bennani');
            $this->get(route('appointments.waiting-room'))->assertOk()->assertSee('Karim Bennani');
            $this->get(route('appointments.calendar'))->assertOk();
        }
    }
}
