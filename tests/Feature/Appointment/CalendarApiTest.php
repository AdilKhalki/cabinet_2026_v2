<?php

namespace Tests\Feature\Appointment;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(8, 0));
    }

    private function appointment(User $doctor, string $at, array $attrs = []): Appointment
    {
        return Appointment::create(array_merge([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => $at,
            'duration_minutes' => 30,
            'consultation_type' => 'general',
        ], $attrs));
    }

    public function test_returns_events_within_the_requested_range_with_fullcalendar_shape(): void
    {
        $doctor = $this->doctor(['name' => 'Alami']);
        $inside = $this->appointment($doctor, '2026-10-20 09:00:00', ['consultation_type' => 'followup']);
        $this->appointment($doctor, '2026-10-18 23:30:00');  // avant la plage
        $this->appointment($doctor, '2026-10-26 00:00:00');  // fin exclusive

        $response = $this->actingAs($this->secretary())
            ->getJson(route('api.appointments.calendar', [
                // Format envoyé par FullCalendar (avec décalage du navigateur)
                'start' => '2026-10-19T00:00:00+01:00',
                'end' => '2026-10-26T00:00:00+01:00',
            ]))
            ->assertOk()
            ->assertJsonCount(1);

        $response->assertJsonPath('0.id', $inside->id)
            ->assertJsonPath('0.start', '2026-10-20T09:00:00')
            ->assertJsonPath('0.end', '2026-10-20T09:30:00')
            ->assertJsonPath('0.extendedProps.status', 'planned')
            ->assertJsonPath('0.extendedProps.status_label', 'Planifié')
            ->assertJsonPath('0.extendedProps.type', 'Suivi')
            ->assertJsonPath('0.extendedProps.doctor', 'Alami')
            ->assertJsonStructure([['id', 'title', 'start', 'end', 'backgroundColor', 'borderColor', 'url',
                'extendedProps' => ['status', 'status_label', 'doctor', 'type', 'dossier', 'phone']]]);
    }

    public function test_doctor_filter_and_doctor_isolation(): void
    {
        $alami = $this->doctor();
        $tazi = $this->doctor();
        $a = $this->appointment($alami, '2026-10-21 10:00:00');
        $t = $this->appointment($tazi, '2026-10-21 10:00:00');
        $range = ['start' => '2026-10-19', 'end' => '2026-10-26'];

        $this->actingAs($this->secretary())
            ->getJson(route('api.appointments.calendar', $range + ['doctor_id' => $tazi->id]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $t->id);

        // Un médecin ne voit que son agenda, même s'il demande celui d'un confrère
        $this->actingAs($alami)
            ->getJson(route('api.appointments.calendar', $range))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $a->id);
    }

    public function test_cancelled_events_are_greyed_out_and_soft_deleted_patients_do_not_crash(): void
    {
        $doctor = $this->doctor();
        $appt = $this->appointment($doctor, '2026-10-21 11:00:00', ['status' => 'cancelled']);
        $appt->patient->delete(); // dossier archivé (soft delete)

        $this->actingAs($this->admin())
            ->getJson(route('api.appointments.calendar', ['start' => '2026-10-19', 'end' => '2026-10-26']))
            ->assertOk()
            ->assertJsonPath('0.backgroundColor', '#9e9e9e');
    }

    public function test_requires_appointment_permission_and_valid_dates(): void
    {
        $this->seedRoles();
        $this->getJson(route('api.appointments.calendar'))->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->getJson(route('api.appointments.calendar'))
            ->assertForbidden();

        $this->actingAs($this->secretary())
            ->getJson(route('api.appointments.calendar', ['start' => 'pas-une-date']))
            ->assertUnprocessable();
    }
}
