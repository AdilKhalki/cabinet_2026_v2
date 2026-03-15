<?php

namespace App\Livewire\LabTest;

use App\Models\LabTest;
use App\Models\LabTestResult;
use App\Models\LabTestType;
use App\Models\Patient;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class LabTestForm extends Component
{
    use WithFileUploads;

    public ?LabTest $labTest = null;
    public bool $isEdit = false;

    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public int|string $consultation_id = '';
    public string $requested_at = '';
    public string $result_date = '';
    public string $status = 'requested';
    public string $laboratory_name = '';
    public string $clinical_info = '';
    public string $doctor_interpretation = '';
    public $pdf_file = null;

    public array $results = [];

    protected function rules(): array
    {
        return [
            'patient_id'           => 'required|exists:patients,id',
            'doctor_id'            => 'required|exists:users,id',
            'requested_at'         => 'required|date',
            'status'               => 'required|in:requested,pending,completed,cancelled',
            'laboratory_name'      => 'nullable|string|max:150',
            'clinical_info'        => 'nullable|string|max:500',
            'doctor_interpretation'=> 'nullable|string|max:2000',
            'pdf_file'             => 'nullable|file|mimes:pdf|max:5120',
            'results.*.parameter_name' => 'required|string|max:150',
            'results.*.value'          => 'nullable|string|max:100',
        ];
    }

    public function mount(?LabTest $labTest = null): void
    {
        $this->requested_at = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($labTest && $labTest->exists) {
            $this->labTest = $labTest->load('results');
            $this->isEdit = true;
            $this->patient_id         = $labTest->patient_id;
            $this->doctor_id          = $labTest->doctor_id ?? '';
            $this->consultation_id    = $labTest->consultation_id ?? '';
            $this->requested_at       = $labTest->requested_at?->format('Y-m-d') ?? today()->format('Y-m-d');
            $this->result_date        = $labTest->result_date?->format('Y-m-d') ?? '';
            $this->status             = $labTest->status ?? 'requested';
            $this->laboratory_name    = $labTest->laboratory_name ?? '';
            $this->clinical_info      = $labTest->clinical_info ?? '';
            $this->doctor_interpretation = $labTest->doctor_interpretation ?? '';
            $this->results = $labTest->results->map(fn ($r) => [
                'id'             => $r->id,
                'lab_test_type_id' => $r->lab_test_type_id,
                'parameter_name' => $r->parameter_name,
                'value'          => $r->value,
                'unit'           => $r->unit,
                'normal_min'     => $r->normal_min,
                'normal_max'     => $r->normal_max,
                'flag'           => $r->flag,
                'notes'          => $r->notes,
            ])->toArray();
        }
    }

    public function addResult(): void
    {
        $this->results[] = [
            'id' => null, 'lab_test_type_id' => '', 'parameter_name' => '',
            'value' => '', 'unit' => '', 'normal_min' => '', 'normal_max' => '',
            'flag' => 'normal', 'notes' => '',
        ];
    }

    public function removeResult(int $i): void
    {
        unset($this->results[$i]);
        $this->results = array_values($this->results);
    }

    public function fillFromType(int $index, int $typeId): void
    {
        $type = LabTestType::find($typeId);
        if ($type) {
            $this->results[$index]['parameter_name'] = $type->name;
            $this->results[$index]['unit'] = $type->unit;
            $this->results[$index]['normal_min'] = $type->normal_min;
            $this->results[$index]['normal_max'] = $type->normal_max;
        }
    }

    public function autoFlag(int $index): void
    {
        $r = $this->results[$index];
        $val = (float) $r['value'];
        if ($r['value'] !== '' && $r['normal_min'] !== '' && $r['normal_max'] !== '') {
            $min = (float) $r['normal_min'];
            $max = (float) $r['normal_max'];
            if ($val < $min * 0.7 || $val > $max * 1.5) {
                $this->results[$index]['flag'] = 'critical';
            } elseif ($val < $min) {
                $this->results[$index]['flag'] = 'low';
            } elseif ($val > $max) {
                $this->results[$index]['flag'] = 'high';
            } else {
                $this->results[$index]['flag'] = 'normal';
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'patient_id'           => $this->patient_id,
            'doctor_id'            => $this->doctor_id,
            'consultation_id'      => $this->consultation_id ?: null,
            'requested_at'         => $this->requested_at,
            'result_date'          => $this->result_date ?: null,
            'status'               => $this->status,
            'laboratory_name'      => $this->laboratory_name ?: null,
            'clinical_info'        => $this->clinical_info ?: null,
            'doctor_interpretation'=> $this->doctor_interpretation ?: null,
            'created_by'           => auth()->id(),
        ];

        if ($this->pdf_file) {
            $data['pdf_path'] = $this->pdf_file->store('lab-tests', 'public');
        }

        if ($this->isEdit) {
            $this->labTest->update($data);
            $labTest = $this->labTest;
            $labTest->results()->delete();
        } else {
            $labTest = LabTest::create($data);
        }

        foreach ($this->results as $r) {
            LabTestResult::create([
                'lab_test_id'     => $labTest->id,
                'lab_test_type_id'=> $r['lab_test_type_id'] ?: null,
                'parameter_name'  => $r['parameter_name'],
                'value'           => $r['value'] ?: null,
                'unit'            => $r['unit'] ?: null,
                'normal_min'      => $r['normal_min'] ?: null,
                'normal_max'      => $r['normal_max'] ?: null,
                'flag'            => $r['flag'] ?? 'normal',
                'notes'           => $r['notes'] ?: null,
            ]);
        }

        session()->flash('success', 'Analyse enregistrée.');
        $this->redirect(route('lab-tests.show', $labTest));
    }

    #[Computed]
    public function labTestTypes()
    {
        return LabTestType::active()->orderBy('category')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.lab-test.lab-test-form', [
            'patients'     => Patient::active()->orderBy('last_name')->get(),
            'doctors'      => User::doctors()->active()->get(),
            'labTestTypes' => $this->labTestTypes,
            'isEdit'       => $this->isEdit,
        ])->layout('layouts.cabinet', [
            'title' => $this->isEdit ? 'Modifier Analyse' : 'Nouvelle Analyse',
            'header' => $this->isEdit ? 'Modifier l\'Analyse' : 'Nouvelle Analyse',
            'headerIcon' => 'icon-thermometer',
            'breadcrumbs' => [
                ['label' => 'Analyses', 'url' => route('lab-tests.index')],
                ['label' => $this->isEdit ? 'Modifier' : 'Nouvelle'],
            ],
        ]);
    }
}
