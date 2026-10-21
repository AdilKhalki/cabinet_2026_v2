<?php

namespace Tests\Feature\LabTest;

use App\Livewire\LabTest\LabTestForm;
use App\Livewire\LabTest\LabTestIndex;
use App\Livewire\LabTest\LabTestShow;
use App\Models\LabTest;
use App\Models\LabTestType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class LabTestWorkflowTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->seedRoles();
    }

    private function makeLabTest($doctor, $patient): LabTest
    {
        return LabTest::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'requested_at' => today(),
            'status' => 'requested',
        ]);
    }

    public function test_compute_flag_rules(): void
    {
        $this->assertSame('normal', LabTestForm::computeFlag('1.0', '0.7', '1.1'));
        $this->assertSame('high', LabTestForm::computeFlag('1.3', '0.7', '1.1'));
        $this->assertSame('critical', LabTestForm::computeFlag('2.0', '0.7', '1.1'));
        $this->assertSame('low', LabTestForm::computeFlag('0.6', '0.7', '1.1'));
        $this->assertSame('critical', LabTestForm::computeFlag('0.3', '0.7', '1.1'));
        $this->assertSame('normal', LabTestForm::computeFlag('0,9', '0.7', '1.1'), 'virgule décimale acceptée');
        $this->assertSame('high', LabTestForm::computeFlag('6', null, '5'), 'borne max seule');
        $this->assertSame('critical', LabTestForm::computeFlag('8', null, '5'));
        $this->assertNull(LabTestForm::computeFlag('Positif', '0', '1'), 'valeur qualitative : pas de calcul');
        $this->assertNull(LabTestForm::computeFlag('5', null, null), 'sans bornes : pas de calcul');
        $this->assertNull(LabTestForm::computeFlag('', '1', '2'));
    }

    public function test_doctor_requests_lab_test_then_enters_results_and_prints_pdf(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $glycemie = LabTestType::create([
            'name' => 'Glycémie à jeun', 'unit' => 'g/L', 'normal_min' => 0.70, 'normal_max' => 1.10, 'is_active' => true,
        ]);

        $this->actingAs($doctor)->get(route('lab-tests.create', ['patient' => $patient->id]))->assertOk();

        // 1. Demande
        Livewire::actingAs($doctor)
            ->withQueryParams(['patient' => $patient->id])
            ->test(LabTestForm::class)
            ->assertSet('patient_id', $patient->id)
            ->set('laboratory_name', 'Laboratoire Ibn Sina')
            ->set('clinical_info', 'Bilan diabète')
            ->call('addResult')
            ->call('fillFromType', 0, (string) $glycemie->id)
            ->assertSet('results.0.parameter_name', 'Glycémie à jeun')
            ->assertSet('results.0.lab_test_type_id', $glycemie->id)
            ->call('addResult')
            ->set('results.1.parameter_name', 'ECBU')
            ->call('save')
            ->assertHasNoErrors();

        $labTest = LabTest::with('results')->sole();
        $this->assertMatchesRegularExpression('/^BIO-\d{4}-00001$/', $labTest->request_number);
        $this->assertSame($glycemie->id, $labTest->results[0]->lab_test_type_id);

        // PDF de la demande
        $response = $this->actingAs($doctor)->get(route('lab-tests.pdf', $labTest));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        // 2. Saisie des résultats (le statut est recalculé côté serveur, même sans autoFlag JS)
        Livewire::actingAs($doctor)
            ->test(LabTestForm::class, ['labTest' => $labTest])
            ->set('status', 'completed')
            ->set('result_date', today()->format('Y-m-d'))
            ->set('results.0.value', '1.45')
            ->set('results.0.flag', 'normal')
            ->set('results.1.value', 'Négatif')
            ->call('addResult')
            ->set('results.2.parameter_name', 'CRP')
            ->set('results.2.value', '0')
            ->set('results.2.normal_min', '0')
            ->set('results.2.normal_max', '6')
            ->call('autoFlag', 2)
            ->assertSet('results.2.flag', 'normal')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('lab-tests.show', $labTest));

        $results = $labTest->fresh()->results()->orderBy('id')->get();
        $this->assertCount(3, $results);
        $this->assertSame('high', $results[0]->flag);
        $this->assertSame('normal', $results[1]->flag);
        $this->assertSame('0', $results[2]->value, 'un résultat à 0 ne doit pas être perdu');
        $this->assertEquals(0, $results[2]->normal_min);
        $this->assertSame('completed', $labTest->fresh()->status);

        $this->actingAs($doctor)->get(route('lab-tests.show', $labTest))
            ->assertOk()
            ->assertSee('Glycémie à jeun');
    }

    public function test_validation_of_results_and_dates(): void
    {
        $doctor = $this->doctor();

        Livewire::actingAs($doctor)
            ->test(LabTestForm::class)
            ->set('patient_id', $this->makePatient()->id)
            ->set('requested_at', '2026-05-10')
            ->set('result_date', '2026-05-01')
            ->call('addResult')
            ->set('results.0.parameter_name', '')
            ->set('results.0.normal_min', 'abc')
            ->call('save')
            ->assertHasErrors(['result_date', 'results.0.parameter_name', 'results.0.normal_min']);

        $this->assertSame(0, LabTest::count());
    }

    public function test_fill_from_type_with_empty_value_does_not_crash(): void
    {
        Livewire::actingAs($this->doctor())
            ->test(LabTestForm::class)
            ->call('addResult')
            ->call('fillFromType', 0, '')
            ->assertSet('results.0.lab_test_type_id', '')
            ->assertHasNoErrors();
    }

    public function test_other_doctor_cannot_access_or_modify_lab_test(): void
    {
        $owner = $this->doctor();
        $other = $this->doctor();
        $labTest = $this->makeLabTest($owner, $this->makePatient());

        $this->actingAs($other)->get(route('lab-tests.pdf', $labTest))->assertForbidden();
        $this->actingAs($other)->get(route('lab-tests.show', $labTest))->assertForbidden();
        $this->actingAs($other)->get(route('lab-tests.edit', $labTest))->assertForbidden();

        Livewire::actingAs($other)->test(LabTestIndex::class)
            ->call('updateStatus', $labTest->id, 'completed')
            ->assertForbidden();
        Livewire::actingAs($other)->test(LabTestIndex::class)
            ->call('delete', $labTest->id)
            ->assertForbidden();

        $this->assertSame('requested', $labTest->fresh()->status);
        $this->assertNotSoftDeleted($labTest);
    }

    public function test_secretary_cannot_download_lab_test_pdf(): void
    {
        $labTest = $this->makeLabTest($this->doctor(), $this->makePatient());

        $this->actingAs($this->secretary())->get(route('lab-tests.pdf', $labTest))->assertForbidden();
    }

    public function test_owner_marks_completed_from_index_sets_result_date(): void
    {
        $doctor = $this->doctor();
        $labTest = $this->makeLabTest($doctor, $this->makePatient());

        Livewire::actingAs($doctor)->test(LabTestIndex::class)
            ->call('updateStatus', $labTest->id, 'completed')
            ->call('updateStatus', $labTest->id, 'bogus');

        $labTest->refresh();
        $this->assertSame('completed', $labTest->status);
        $this->assertNotNull($labTest->result_date);
    }

    public function test_evolution_chart_ignores_deleted_tests(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();

        foreach (['2026-01-10' => '1.0', '2026-02-10' => '1.2', '2026-03-10' => '9.9'] as $date => $value) {
            $t = LabTest::create([
                'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
                'requested_at' => $date, 'result_date' => $date, 'status' => 'completed',
            ]);
            $t->results()->create(['parameter_name' => 'Glycémie', 'value' => $value]);
            $last = $t;
        }
        $last->delete();

        $first = LabTest::orderBy('id')->first();
        $component = Livewire::actingAs($doctor)->test(LabTestShow::class, ['labTest' => $first]);

        $this->assertSame([1.0, 1.2], $component->instance()->evolutionData()['Glycémie']['values']);
    }
}
