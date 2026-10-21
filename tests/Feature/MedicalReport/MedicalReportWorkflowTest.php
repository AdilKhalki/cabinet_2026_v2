<?php

namespace Tests\Feature\MedicalReport;

use App\Livewire\MedicalReport\MedicalReportForm;
use App\Livewire\MedicalReport\MedicalReportIndex;
use App\Models\MedicalReport;
use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class MedicalReportWorkflowTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->seedRoles();
    }

    private function makeReport($doctor, $patient, array $attrs = []): MedicalReport
    {
        return MedicalReport::create(array_merge([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'type' => 'medical_report',
            'title' => 'Compte-rendu',
            'content' => 'Contenu',
            'report_date' => today(),
        ], $attrs));
    }

    public function test_doctor_writes_sick_leave_and_prints_pdf(): void
    {
        $doctor = $this->doctor(['name' => 'Karim Bennani', 'license_number' => 'INPE-998877']);
        $patient = $this->makePatient(['first_name' => 'Amina', 'last_name' => 'Tazi']);

        $this->actingAs($doctor)->get(route('medical-reports.create', ['patient' => $patient->id]))->assertOk();

        Livewire::actingAs($doctor)
            ->withQueryParams(['patient' => $patient->id])
            ->test(MedicalReportForm::class)
            ->set('type', 'sick_leave')
            ->assertSet('valid_from', now()->format('Y-m-d'))
            ->set('valid_from', '2026-10-01')
            ->set('sick_leave_days', 5)
            ->call('applyTemplate', 'sick_leave')
            ->assertSet('title', 'Certificat d\'arrêt de travail')
            ->call('save')
            ->assertHasNoErrors();

        $report = MedicalReport::sole();
        $this->assertMatchesRegularExpression('/^RPT-\d{4}-00001$/', $report->report_number);
        $this->assertSame('sick_leave', $report->type);
        $this->assertSame('2026-10-05', $report->valid_until->format('Y-m-d'), 'date de fin déduite du nombre de jours');
        $this->assertSame(5, $report->sick_leave_days);
        $this->assertStringContainsString('Amina', $report->content);
        $this->assertSame($doctor->id, $report->created_by);

        $response = $this->actingAs($doctor)->get(route('medical-reports.pdf', $report));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertTrue($report->fresh()->is_printed);
    }

    public function test_pdf_view_shows_report_doctor_not_settings_doctor_and_escapes_content(): void
    {
        $doctor = $this->doctor(['name' => 'Dr. Karim Bennani', 'license_number' => '123456']);
        $report = $this->makeReport($doctor, $this->makePatient(), [
            'content' => "<script>alert(1)</script>\nLigne 2",
        ]);
        $report->load(['patient', 'doctor']);

        $html = view('pdf.medical-report', [
            'report' => $report,
            'patient' => $report->patient,
            'settings' => ['doctor_name' => 'Autre Médecin', 'cabinet_name' => 'Cabinet Test'],
        ])->render();

        $this->assertStringContainsString('Dr. Karim Bennani', $html);
        $this->assertStringNotContainsString('Dr. Dr.', $html);
        $this->assertStringNotContainsString('Autre Médecin', $html);
        $this->assertStringContainsString('INPE : 123456', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_sick_leave_days_must_match_period(): void
    {
        Livewire::actingAs($this->doctor())
            ->test(MedicalReportForm::class)
            ->set('patient_id', $this->makePatient()->id)
            ->set('type', 'sick_leave')
            ->set('title', 'Arrêt')
            ->set('content', 'Texte')
            ->set('valid_from', '2026-10-01')
            ->set('valid_until', '2026-10-03')
            ->set('sick_leave_days', 10)
            ->call('save')
            ->assertHasErrors(['sick_leave_days']);

        Livewire::actingAs($this->doctor())
            ->test(MedicalReportForm::class)
            ->set('patient_id', $this->makePatient()->id)
            ->set('type', 'sick_leave')
            ->set('title', 'Arrêt')
            ->set('content', 'Texte')
            ->set('valid_from', '2026-10-05')
            ->set('valid_until', '2026-10-01')
            ->set('sick_leave_days', '')
            ->call('save')
            ->assertHasErrors(['valid_until']);

        $this->assertSame(0, MedicalReport::count());
    }

    public function test_templates_without_dedicated_type_can_be_saved(): void
    {
        foreach (['medical_certificate' => 'other', 'surgery_report' => 'medical_report'] as $template => $expectedType) {
            Livewire::actingAs($this->doctor())
                ->test(MedicalReportForm::class)
                ->set('patient_id', $this->makePatient()->id)
                ->call('applyTemplate', $template)
                ->assertSet('type', $expectedType)
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(2, MedicalReport::count());
    }

    public function test_edit_keeps_original_author(): void
    {
        $doctor = $this->doctor();
        $report = $this->makeReport($doctor, $this->makePatient(), ['created_by' => $doctor->id]);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(MedicalReportForm::class, ['medicalReport' => $report])
            ->set('title', 'Titre corrigé')
            ->call('save')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertSame('Titre corrigé', $report->title);
        $this->assertSame($doctor->id, $report->created_by);
        $this->assertSame($doctor->id, $report->doctor_id);
    }

    public function test_other_doctor_cannot_view_edit_or_print_report(): void
    {
        $report = $this->makeReport($this->doctor(), $this->makePatient());
        $other = $this->doctor();

        $this->actingAs($other)->get(route('medical-reports.pdf', $report))->assertForbidden();
        $this->actingAs($other)->get(route('medical-reports.show', $report))->assertForbidden();
        $this->actingAs($other)->get(route('medical-reports.edit', $report))->assertForbidden();
        $this->assertFalse($report->fresh()->is_printed);
    }

    public function test_secretary_cannot_download_report(): void
    {
        $report = $this->makeReport($this->doctor(), $this->makePatient());

        $this->actingAs($this->secretary())->get(route('medical-reports.pdf', $report))->assertForbidden();
    }

    public function test_delete_from_index(): void
    {
        $doctor = $this->doctor();
        $report = $this->makeReport($doctor, $this->makePatient());

        // Le médecin n'a pas la permission « delete medical-reports »
        Livewire::actingAs($doctor)->test(MedicalReportIndex::class)
            ->call('delete', $report->id)
            ->assertForbidden();
        $this->assertNotSoftDeleted($report);

        // L'admin supprime via le bouton (delete(id) appelé directement par la vue)
        Livewire::actingAs($this->admin())->test(MedicalReportIndex::class)
            ->call('delete', $report->id)
            ->assertHasNoErrors();
        $this->assertSoftDeleted($report);
    }

    public function test_medication_search_api_returns_json(): void
    {
        Medication::create(['name' => 'Doliprane 1000mg', 'generic_name' => 'Paracétamol', 'strength' => '1000mg', 'is_active' => true]);
        Medication::create(['name' => 'Efferalgan', 'generic_name' => 'Paracétamol', 'is_active' => true]);
        Medication::create(['name' => 'Ancien Doliprane', 'generic_name' => 'Paracétamol', 'is_active' => false]);
        Medication::create(['name' => 'Ibuprofène 400mg', 'generic_name' => 'Ibuprofène', 'is_active' => true]);

        $this->getJson(route('api.medications.search', ['q' => 'doli']))->assertUnauthorized();

        $doctor = $this->doctor();

        $this->actingAs($doctor)->getJson(route('api.medications.search', ['q' => 'doli']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Doliprane 1000mg')
            ->assertJsonStructure([['id', 'name', 'generic_name', 'dosage_form', 'strength']]);

        $this->actingAs($doctor)->getJson(route('api.medications.search', ['q' => 'paracét']))
            ->assertOk()
            ->assertJsonCount(2);

        $this->actingAs($doctor)->getJson(route('api.medications.search'))
            ->assertOk()
            ->assertJsonCount(3);

        $this->actingAs($doctor)->getJson(route('api.medications.search', ['q' => ['x']]))
            ->assertOk();
    }
}
