<?php

namespace App\Livewire\LabTest;

use App\Models\LabTest;
use App\Models\LabTestResult;
use App\Models\LabTestType;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class LabTestForm extends Component
{
    use WithFileUploads;

    public ?int $labTestId = null;

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
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'requested_at' => 'required|date',
            'result_date' => 'nullable|date|after_or_equal:requested_at',
            'status' => 'required|in:requested,pending,completed,cancelled',
            'laboratory_name' => 'nullable|string|max:150',
            'clinical_info' => 'nullable|string|max:500',
            'doctor_interpretation' => 'nullable|string|max:2000',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'results.*.parameter_name' => 'required|string|max:150',
            'results.*.value' => 'nullable|string|max:100',
            'results.*.lab_test_type_id' => 'nullable|exists:lab_test_types,id',
            'results.*.unit' => 'nullable|string|max:50',
            'results.*.normal_min' => 'nullable|numeric',
            'results.*.normal_max' => 'nullable|numeric',
            'results.*.flag' => 'nullable|in:normal,low,high,critical',
            'results.*.notes' => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'result_date.after_or_equal' => 'La date du résultat doit être postérieure ou égale à la date de demande.',
            'results.*.parameter_name.required' => 'Le nom du paramètre est obligatoire.',
            'results.*.normal_min.numeric' => 'La valeur normale minimale doit être numérique.',
            'results.*.normal_max.numeric' => 'La valeur normale maximale doit être numérique.',
        ];
    }

    public function mount($labTest = null): void
    {
        $this->requested_at = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($patientId = request()->query('patient')) {
            $this->patient_id = (int) $patientId;
        }

        if ($consultationId = request()->query('consultation')) {
            $this->consultation_id = (int) $consultationId;
        }

        if ($labTest) {
            $record = $labTest instanceof LabTest
                ? $labTest->load('results')
                : LabTest::with('results')->findOrFail($labTest);

            $this->authorize('update', $record);

            $this->labTestId = $record->id;
            $this->isEdit = true;
            $this->patient_id = $record->patient_id;
            $this->doctor_id = $record->doctor_id ?? '';
            $this->consultation_id = $record->consultation_id ?? '';
            $this->requested_at = $record->requested_at?->format('Y-m-d') ?? today()->format('Y-m-d');
            $this->result_date = $record->result_date?->format('Y-m-d') ?? '';
            $this->status = $record->status ?? 'requested';
            $this->laboratory_name = $record->laboratory_name ?? '';
            $this->clinical_info = $record->clinical_info ?? '';
            $this->doctor_interpretation = $record->doctor_interpretation ?? '';
            $this->results = $record->results->map(fn ($r) => [
                'id' => $r->id,
                'lab_test_type_id' => $r->lab_test_type_id,
                'parameter_name' => $r->parameter_name,
                'value' => $r->value,
                'unit' => $r->unit,
                'normal_min' => $r->normal_min,
                'normal_max' => $r->normal_max,
                'flag' => $r->flag,
                'notes' => $r->notes,
            ])->toArray();
        } else {
            $this->authorize('create', LabTest::class);
        }

        $this->enforceOwnDoctor();
    }

    /** Un médecin (non admin) demande toujours les analyses en son propre nom. */
    protected function enforceOwnDoctor(): void
    {
        $user = auth()->user();
        if ($user->isMedecin() && ! $user->isAdmin()) {
            $this->doctor_id = $user->id;
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

    public function fillFromType(int $index, $typeId = ''): void
    {
        if (! array_key_exists($index, $this->results)) {
            return;
        }

        $this->results[$index]['lab_test_type_id'] = $typeId ? (int) $typeId : '';

        $type = $typeId ? LabTestType::find((int) $typeId) : null;
        if ($type) {
            $this->results[$index]['parameter_name'] = $type->name;
            $this->results[$index]['unit'] = $type->unit;
            $this->results[$index]['normal_min'] = $type->normal_min;
            $this->results[$index]['normal_max'] = $type->normal_max;
        }
    }

    public function autoFlag(int $index): void
    {
        if (! array_key_exists($index, $this->results)) {
            return;
        }

        $flag = static::computeFlag(
            $this->results[$index]['value'] ?? null,
            $this->results[$index]['normal_min'] ?? null,
            $this->results[$index]['normal_max'] ?? null,
        );

        if ($flag !== null) {
            $this->results[$index]['flag'] = $flag;
        }
    }

    /**
     * Calcule le statut d'un résultat numérique par rapport aux valeurs normales.
     * Retourne null si le calcul est impossible (valeur non numérique ou aucune borne) :
     * le statut saisi manuellement est alors conservé.
     */
    public static function computeFlag($value, $min, $max): ?string
    {
        $value = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
        $hasMin = $min !== null && $min !== '' && is_numeric($min);
        $hasMax = $max !== null && $max !== '' && is_numeric($max);

        if ($value === null || $value === '' || ! is_numeric($value) || (! $hasMin && ! $hasMax)) {
            return null;
        }

        $val = (float) $value;
        $min = $hasMin ? (float) $min : null;
        $max = $hasMax ? (float) $max : null;

        // Seuils critiques : < 70 % du min ou > 150 % du max
        if (($min !== null && $val < $min * 0.7) || ($max !== null && $val > $max * 1.5)) {
            return 'critical';
        }
        if ($min !== null && $val < $min) {
            return 'low';
        }
        if ($max !== null && $val > $max) {
            return 'high';
        }

        return 'normal';
    }

    public function save(): void
    {
        $existing = $this->isEdit ? LabTest::findOrFail($this->labTestId) : null;
        $existing ? $this->authorize('update', $existing) : $this->authorize('create', LabTest::class);

        $this->enforceOwnDoctor();

        $this->validate();

        $data = [
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'requested_at' => $this->requested_at,
            'result_date' => $this->result_date ?: null,
            'status' => $this->status,
            'laboratory_name' => $this->laboratory_name ?: null,
            'clinical_info' => $this->clinical_info ?: null,
            'doctor_interpretation' => $this->doctor_interpretation ?: null,
        ];

        if ($this->pdf_file) {
            $data['pdf_path'] = $this->pdf_file->store('lab-tests', 'public');
        }

        // Les valeurs "0" sont des résultats valides : ne pas utiliser ?: (qui les transformerait en null)
        $blankToNull = fn ($v) => ($v === null || $v === '') ? null : $v;

        $labTest = DB::transaction(function () use ($existing, $data, $blankToNull) {
            if ($existing) {
                $existing->update($data);
                $existing->results()->delete();
                $labTest = $existing;
            } else {
                $data['created_by'] = auth()->id();
                $labTest = LabTest::create($data);
            }

            foreach ($this->results as $r) {
                $value = $blankToNull($r['value'] ?? null);
                $min = $blankToNull($r['normal_min'] ?? null);
                $max = $blankToNull($r['normal_max'] ?? null);

                LabTestResult::create([
                    'lab_test_id' => $labTest->id,
                    'lab_test_type_id' => ($r['lab_test_type_id'] ?? '') ?: null,
                    'parameter_name' => $r['parameter_name'],
                    'value' => $value,
                    'unit' => $blankToNull($r['unit'] ?? null),
                    'normal_min' => $min,
                    'normal_max' => $max,
                    // Recalcul côté serveur : le statut ne dépend pas d'un événement JS
                    'flag' => static::computeFlag($value, $min, $max) ?? (($r['flag'] ?? '') ?: 'normal'),
                    'notes' => $blankToNull($r['notes'] ?? null),
                ]);
            }

            return $labTest;
        });

        if ($existing) {
            $message = 'Analyse mise à jour.';
        } else {
            NotificationService::labTestCreated($labTest->id, (int) $this->patient_id, (int) $this->doctor_id);
            $message = 'Analyse enregistrée.';
        }

        session()->flash('success', $message);
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
            'patients' => Patient::active()->orderBy('last_name')->get(),
            'doctors' => User::doctors()->active()->get(),
            'labTestTypes' => $this->labTestTypes,
            'isEdit' => $this->isEdit,
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
