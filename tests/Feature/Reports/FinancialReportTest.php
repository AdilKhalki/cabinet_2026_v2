<?php

namespace Tests\Feature\Reports;

use App\Livewire\Reports\CabinetStats;
use App\Livewire\Reports\FinancialReport;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        Carbon::setTestNow('2026-10-21 10:00:00');
        $this->seedRoles();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pay(User $doctor, string $date, float $total, float $paid, string $status, array $extra = []): Payment
    {
        return Payment::create(array_merge([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'payment_method' => 'cash',
            'subtotal' => $total, 'discount' => 0, 'insurance_coverage' => 0,
            'total_amount' => $total, 'amount_paid' => $paid, 'amount_due' => $total - $paid,
            'status' => $status,
            'payment_date' => $date,
        ], $extra));
    }

    /** Jeu de données d'octobre 2026 : CA attendu = 300 + 150 + 80 = 530 MAD */
    private function seedOctober(User $doctorA, User $doctorB): void
    {
        $this->pay($doctorA, '2026-10-01', 300, 300, 'paid');
        $this->pay($doctorA, '2026-10-15', 200, 150, 'partial');
        $this->pay($doctorB, '2026-10-31', 80, 80, 'paid', ['payment_method' => 'bank_transfer']); // dernier jour inclus
        $this->pay($doctorB, '2026-10-10', 100, 0, 'pending');
        $this->pay($doctorA, '2026-10-12', 500, 0, 'cancelled');   // exclu
        $this->pay($doctorA, '2026-10-13', 400, 400, 'refunded');  // exclu
        $this->pay($doctorA, '2026-09-30', 999, 999, 'paid');      // hors période

        // Saisie en octobre d'un paiement daté de septembre : la date de paiement fait foi
        $late = $this->pay($doctorB, '2026-09-15', 700, 700, 'paid');
        $late->forceFill(['created_at' => '2026-10-05 09:00:00'])->save();
    }

    public function test_period_totals_equal_sum_of_paid_amounts_excluding_cancelled_and_refunded(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $this->seedOctober($doctorA, $doctorB);

        $component = Livewire::actingAs($this->admin())->test(FinancialReport::class)
            ->assertSet('dateFrom', '2026-10-01')
            ->assertSet('dateTo', '2026-10-31');

        $stats = $component->get('stats');
        $this->assertEquals(530.0, $stats['total_revenue']);
        $this->assertEquals(150.0, $stats['total_pending']); // 50 partiel + 100 impayé
        $this->assertSame(4, $stats['total_invoices']);
        $this->assertSame(2, $stats['paid_count']);
        $this->assertSame(1, $stats['partial_count']);
        $this->assertSame(1, $stats['pending_count']);

        $expected = (float) Payment::revenue()->forPeriod('2026-10-01', '2026-10-31')->sum('amount_paid');
        $this->assertEquals($expected, $stats['total_revenue']);

        // Graphique : une valeur par jour, somme = CA
        $series = $component->get('revenueSeries');
        $this->assertCount(31, $series['labels']);
        $this->assertCount(31, $series['data']);
        $this->assertEquals(530.0, array_sum($series['data']));
        $this->assertEquals(80.0, end($series['data']));

        $byMethod = collect($component->get('paymentsByMethod'))->keyBy('label');
        $this->assertEquals(80.0, $byMethod['Virement']['total']);

        $component->assertSee('530,00 MAD');
    }

    public function test_doctor_and_period_filters(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $this->seedOctober($doctorA, $doctorB);

        $component = Livewire::actingAs($this->admin())->test(FinancialReport::class)
            ->set('doctorFilter', (string) $doctorA->id);
        $this->assertEquals(450.0, $component->get('stats')['total_revenue']);

        $component->set('doctorFilter', '')
            ->set('dateFrom', '2026-09-01')
            ->set('dateTo', '2026-09-30')
            ->assertSet('periodType', 'custom');
        $this->assertEquals(1699.0, $component->get('stats')['total_revenue']);

        $component->set('periodType', 'year')->assertSet('dateFrom', '2026-01-01')->assertSet('dateTo', '2026-12-31');
        $series = $component->get('revenueSeries');
        $this->assertCount(12, $series['data']); // agrégé par mois
        $this->assertEquals(2229.0, array_sum($series['data']));

        $component->set('periodType', 'today')->assertSet('dateFrom', '2026-10-21')->assertSet('dateTo', '2026-10-21');
        $this->assertEquals(0.0, $component->get('stats')['total_revenue']);
    }

    public function test_doctor_only_sees_own_revenue(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $this->seedOctober($doctorA, $doctorB);

        $component = Livewire::actingAs($doctorA)->test(FinancialReport::class)
            ->assertSet('doctorFilter', (string) $doctorA->id)
            ->set('doctorFilter', (string) $doctorB->id) // tentative de contournement
            ->assertSet('doctorFilter', (string) $doctorA->id);

        $this->assertEquals(450.0, $component->get('stats')['total_revenue']);
    }

    public function test_excel_export(): void
    {
        $doctor = $this->doctor();
        $this->seedOctober($doctor, $doctor);

        Livewire::actingAs($this->admin())->test(FinancialReport::class)
            ->call('export')
            ->assertFileDownloaded('rapport-financier-20261001-20261031.xlsx');
    }

    public function test_report_pages_require_view_reports_permission(): void
    {
        $secretary = $this->secretary(); // pas de « view reports »

        $this->actingAs($secretary)->get(route('reports.financial'))->assertForbidden();
        $this->actingAs($secretary)->get(route('reports.stats'))->assertForbidden();
    }

    public function test_pages_render_with_data(): void
    {
        $doctor = $this->doctor();
        $this->seedOctober($doctor, $doctor);
        $patient = $this->makePatient();
        Consultation::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'consultation_date' => '2026-10-20 09:00:00']);
        Appointment::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'scheduled_at' => '2026-10-20 09:00:00', 'duration_minutes' => 20, 'status' => 'cancelled',
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('reports.financial'))->assertOk()->assertSee('Rapport Financier');
        $this->actingAs($admin)->get(route('reports.stats'))->assertOk()->assertSee('Statistiques du Cabinet');

        $stats = Livewire::actingAs($admin)->test(CabinetStats::class)
            ->assertViewHas('consultationsThisMonth', 1)
            ->assertViewHas('appointmentsThisMonth', 1)
            ->assertViewHas('cancellationRate', 100.0);

        $monthly = $stats->instance()->monthlyConsultations();
        $this->assertSame(1, $monthly['data'][9]);
        $this->assertEquals(
            (float) Payment::whereNotIn('status', ['cancelled', 'refunded'])->sum('amount_paid'),
            (float) $stats->instance()->globalStats()['total_revenue']
        );
    }
}
