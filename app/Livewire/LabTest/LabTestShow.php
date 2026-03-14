<?php

namespace App\Livewire\LabTest;

use App\Models\LabTest;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LabTestShow extends Component
{
    public LabTest $labTest;

    public function mount(LabTest $labTest): void
    {
        $this->labTest = $labTest->load(['patient', 'doctor', 'results.labTestType', 'consultation']);
    }

    #[Computed]
    public function evolutionData(): array
    {
        // Courbe d'évolution pour les paramètres répétitifs
        $data = [];
        foreach ($this->labTest->results as $result) {
            $history = DB::table('lab_test_results')
                ->join('lab_tests', 'lab_tests.id', '=', 'lab_test_results.lab_test_id')
                ->where('lab_tests.patient_id', $this->labTest->patient_id)
                ->where('lab_test_results.parameter_name', $result->parameter_name)
                ->where('lab_tests.status', 'completed')
                ->orderBy('lab_tests.result_date')
                ->select('lab_tests.result_date as date', 'lab_test_results.value')
                ->take(12)
                ->get();

            if ($history->count() > 1) {
                $data[$result->parameter_name] = [
                    'labels' => $history->pluck('date')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m/Y'))->toArray(),
                    'values' => $history->pluck('value')->map(fn ($v) => (float) $v)->toArray(),
                    'unit'   => $result->unit,
                    'normal_min' => $result->normal_min,
                    'normal_max' => $result->normal_max,
                ];
            }
        }
        return $data;
    }

    public function render()
    {
        return view('livewire.lab-test.lab-test-show')
            ->layout('layouts.cabinet', [
                'title' => "Analyse {$this->labTest->request_number}",
                'header' => "Résultats : {$this->labTest->request_number}",
                'headerIcon' => 'icon-thermometer',
                'subHeader' => "Patient : {$this->labTest->patient->full_name}",
                'breadcrumbs' => [
                    ['label' => 'Analyses', 'url' => route('lab-tests.index')],
                    ['label' => $this->labTest->request_number],
                ],
            ]);
    }
}
