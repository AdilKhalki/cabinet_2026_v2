<?php

namespace App\Livewire\LabTest;

use App\Models\LabTest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LabTestShow extends Component
{
    public LabTest $labTest;

    public function mount(LabTest $labTest): void
    {
        $this->authorize('view', $labTest);

        $this->labTest = $labTest->load(['patient', 'doctor', 'results.labTestType', 'consultation']);
    }

    /** Télécharge le PDF du laboratoire après vérification des droits. */
    public function downloadLabPdf()
    {
        $this->authorize('view', $this->labTest);

        $disk = $this->labTest->pdfDisk();
        abort_unless($this->labTest->pdf_path && Storage::disk($disk)->exists($this->labTest->pdf_path), 404);

        return Storage::disk($disk)->download($this->labTest->pdf_path, 'resultats-'.$this->labTest->request_number.'.pdf');
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
                ->whereNull('lab_tests.deleted_at') // exclure les analyses supprimées
                ->whereNotNull('lab_tests.result_date')
                ->orderByDesc('lab_tests.result_date')
                ->select('lab_tests.result_date as date', 'lab_test_results.value')
                ->take(12)
                ->get()
                ->filter(fn ($row) => is_numeric(str_replace(',', '.', (string) $row->value)))
                ->reverse()
                ->values();

            if ($history->count() > 1) {
                $data[$result->parameter_name] = [
                    'labels' => $history->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('d/m/Y'))->toArray(),
                    'values' => $history->pluck('value')->map(fn ($v) => (float) str_replace(',', '.', (string) $v))->toArray(),
                    'unit' => $result->unit,
                    'normal_min' => $result->normal_min,
                    'normal_max' => $result->normal_max,
                ];
            }
        }

        return $data;
    }

    public function render()
    {
        return view('livewire.lab-test.lab-test-show', [
            'labTest' => $this->labTest,
            'evolutionData' => $this->evolutionData,
        ])->layout('layouts.cabinet', [
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
