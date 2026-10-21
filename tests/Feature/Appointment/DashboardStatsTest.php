<?php

namespace Tests\Feature\Appointment;

use App\Livewire\Dashboard\DashboardStats;
use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(8, 0));
    }

    public function test_dashboard_renders_for_every_role_with_data(): void
    {
        $doctor = $this->doctor(['name' => 'Alami']);
        $colleague = $this->doctor(['name' => 'Tazi']);
        $patient = $this->makePatient(['first_name' => 'Youssef', 'last_name' => 'Amrani']);

        $base = ['patient_id' => $patient->id, 'duration_minutes' => 30, 'consultation_type' => 'general'];
        Appointment::create($base + ['doctor_id' => $doctor->id, 'scheduled_at' => today()->setTime(10, 0), 'status' => 'waiting']);
        Appointment::create($base + ['doctor_id' => $doctor->id, 'scheduled_at' => today()->setTime(11, 0), 'status' => 'confirmed']);
        Appointment::create($base + ['doctor_id' => $doctor->id, 'scheduled_at' => today()->setTime(12, 0), 'status' => 'cancelled']);
        Appointment::create($base + ['doctor_id' => $colleague->id, 'scheduled_at' => today()->setTime(10, 0), 'status' => 'planned']);
        Appointment::create($base + ['doctor_id' => $doctor->id, 'scheduled_at' => today()->subDay()->setTime(10, 0), 'status' => 'completed']);
        Consultation::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'consultation_date' => now()->subDays(2), 'reason' => 'Bilan annuel confidentiel',
        ]);

        // Admin : vue globale
        $this->actingAs($this->admin());
        $admin = Livewire::test(DashboardStats::class)->assertSee('Bilan annuel confidentiel');
        $this->assertSame(3, $admin->instance()->todayAppointments);
        $this->assertSame(1, $admin->instance()->todayWaiting);
        $this->assertSame(1, array_sum($admin->instance()->monthlyConsultationsChart['values']));
        $this->get(route('home'))->assertOk();

        // Médecin : uniquement son activité
        $this->actingAs($doctor);
        $doc = Livewire::test(DashboardStats::class)->assertSee('Bilan annuel confidentiel');
        $this->assertSame(2, $doc->instance()->todayAppointments);
        $this->get(route('home'))->assertOk();

        $this->actingAs($colleague);
        $this->assertSame(1, Livewire::test(DashboardStats::class)->instance()->todayAppointments);

        // Secrétaire : agenda oui, données médicales non
        $this->actingAs($this->secretary());
        Livewire::test(DashboardStats::class)
            ->assertSee('Youssef Amrani')
            ->assertDontSee('Bilan annuel confidentiel')
            ->assertDontSee(route('reports.financial'));
        $this->get(route('home'))->assertOk();
    }

    public function test_dashboard_query_count_is_bounded(): void
    {
        $doctor = $this->doctor();
        $this->actingAs($this->admin());

        foreach (range(1, 10) as $i) {
            $patient = $this->makePatient();
            Appointment::create([
                'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
                'scheduled_at' => now()->addDays($i), 'duration_minutes' => 30, 'consultation_type' => 'general',
            ]);
            Consultation::create([
                'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'consultation_date' => now()->subDays($i),
            ]);
        }

        \DB::enableQueryLog();
        Livewire::test(DashboardStats::class);
        $queries = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Pas de N+1 : le nombre de requêtes ne dépend pas du nombre de lignes affichées
        $this->assertLessThan(30, $queries);
    }
}
