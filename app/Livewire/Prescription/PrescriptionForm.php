<?php

namespace App\Livewire\Prescription;

use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionLine;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PrescriptionForm extends Component
{
    public ?int $prescriptionId = null;

    public bool $isEdit = false;

    public int|string $patient_id = '';

    public int|string $doctor_id = '';

    public int|string $consultation_id = '';

    public string $prescribed_at = '';

    public string $valid_until = '';

    public string $notes = '';

    public array $lines = [];

    public function mount($prescription = null): void
    {
        $this->prescribed_at = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($patientId = request()->query('patient')) {
            $this->patient_id = (int) $patientId;
        }

        if ($doctorId = request()->query('doctor')) {
            $this->doctor_id = (int) $doctorId;
        }

        if ($consultationId = request()->query('consultation')) {
            $this->consultation_id = (int) $consultationId;

            // Pré-remplir le patient depuis la consultation si absent
            if (! $this->patient_id && ($consultation = Consultation::find($this->consultation_id))) {
                $this->patient_id = $consultation->patient_id;
            }
        }

        // Mode édition
        if ($prescription) {
            $record = $prescription instanceof Prescription
                ? $prescription
                : Prescription::with('lines')->findOrFail($prescription);

            $this->authorize('update', $record);

            $this->prescriptionId = $record->id;
            $this->isEdit = true;
            $this->patient_id = $record->patient_id;
            $this->doctor_id = $record->doctor_id;
            $this->consultation_id = $record->consultation_id ?? '';
            $this->prescribed_at = $record->prescribed_at ? Carbon::parse($record->prescribed_at)->format('Y-m-d') : today()->format('Y-m-d');
            $this->valid_until = $record->valid_until ? Carbon::parse($record->valid_until)->format('Y-m-d') : '';
            $this->notes = $record->notes ?? '';

            $this->lines = [];
            foreach ($record->lines()->orderBy('sort_order')->get() as $line) {
                $this->lines[] = [
                    'medication_id' => $line->medication_id ?? '',
                    'medication_name' => $line->medication_name ?? '',
                    'dosage' => $line->dosage ?? '',
                    'frequency' => $line->frequency ?? '',
                    'duration_days' => $line->duration_days ?? '',
                    'route' => $line->route ?? 'Oral',
                    'instructions' => $line->instructions ?? '',
                    'quantity' => $line->quantity ?? '',
                ];
            }

            if (empty($this->lines)) {
                $this->addLine();
            }
        } else {
            $this->authorize('create', Prescription::class);
            $this->addLine();
        }

        $this->enforceOwnDoctor();
    }

    /** Un médecin (non admin) prescrit toujours en son propre nom. */
    protected function enforceOwnDoctor(): void
    {
        $user = auth()->user();
        if ($user->isMedecin() && ! $user->isAdmin()) {
            $this->doctor_id = $user->id;
        }
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'medication_id' => '',
            'medication_name' => '',
            'dosage' => '',
            'frequency' => '',
            'duration_days' => '',
            'route' => 'Oral',
            'instructions' => '',
            'quantity' => '',
        ];
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function selectMedication(int $index, $medicationId = ''): void
    {
        if (! array_key_exists($index, $this->lines)) {
            return;
        }

        $this->lines[$index]['medication_id'] = $medicationId ? (int) $medicationId : '';

        if ($medicationId) {
            $med = Medication::find((int) $medicationId);
            if ($med) {
                $name = $med->name;
                if ($med->strength) {
                    $name .= ' '.$med->strength;
                }
                $this->lines[$index]['medication_name'] = $name;
            }
        } else {
            $this->lines[$index]['medication_name'] = '';
        }
    }

    public function save(): void
    {
        $existing = $this->isEdit ? Prescription::findOrFail($this->prescriptionId) : null;
        $existing ? $this->authorize('update', $existing) : $this->authorize('create', Prescription::class);

        $this->enforceOwnDoctor();

        $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'consultation_id' => [
                'nullable',
                'exists:consultations,id',
                function ($attribute, $value, $fail) {
                    if ($value && Consultation::whereKey($value)->value('patient_id') != $this->patient_id) {
                        $fail('La consultation sélectionnée n\'appartient pas à ce patient.');
                    }
                },
            ],
            'prescribed_at' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:prescribed_at',
            'notes' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.medication_id' => 'nullable|exists:medications,id',
            'lines.*.medication_name' => 'required|string|max:200',
            'lines.*.dosage' => 'nullable|string|max:150',
            'lines.*.frequency' => 'nullable|string|max:100',
            'lines.*.duration_days' => 'nullable|integer|min:1|max:3650',
            'lines.*.route' => 'nullable|string|max:80',
            'lines.*.instructions' => 'nullable|string|max:1000',
            'lines.*.quantity' => 'nullable|integer|min:1|max:999',
        ], [
            'lines.required' => 'Ajoutez au moins un médicament.',
            'lines.min' => 'Ajoutez au moins un médicament.',
            'lines.*.medication_name.required' => 'Le nom du médicament est obligatoire.',
            'lines.*.duration_days.integer' => 'La durée doit être un nombre de jours.',
            'lines.*.quantity.integer' => 'La quantité doit être un nombre entier (nombre de boîtes).',
            'valid_until.after_or_equal' => 'La date de validité doit être postérieure ou égale à la date de prescription.',
        ]);

        $data = [
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'prescribed_at' => $this->prescribed_at,
            'valid_until' => $this->valid_until ?: null,
            'notes' => $this->notes ?: null,
        ];

        $prescription = DB::transaction(function () use ($existing, $data) {
            if ($existing) {
                $existing->update($data);
                // Supprimer les anciennes lignes et recréer
                $existing->lines()->delete();
                $prescription = $existing;
            } else {
                $data['created_by'] = auth()->id();
                $prescription = Prescription::create($data);
            }

            foreach (array_values($this->lines) as $i => $line) {
                PrescriptionLine::create([
                    'prescription_id' => $prescription->id,
                    'medication_id' => ($line['medication_id'] ?? '') ?: null,
                    'medication_name' => trim($line['medication_name']),
                    'dosage' => ($line['dosage'] ?? '') ?: null,
                    'frequency' => ($line['frequency'] ?? '') ?: null,
                    'duration_days' => ($line['duration_days'] ?? '') ?: null,
                    'route' => ($line['route'] ?? '') ?: null,
                    'instructions' => ($line['instructions'] ?? '') ?: null,
                    'quantity' => ($line['quantity'] ?? '') ?: null,
                    'sort_order' => $i,
                ]);
            }

            return $prescription;
        });

        if ($existing) {
            $message = 'Ordonnance mise à jour.';
        } else {
            NotificationService::prescriptionCreated($prescription->id, (int) $this->patient_id, (int) $this->doctor_id);
            $message = 'Ordonnance créée.';
        }

        session()->flash('success', $message);
        $this->redirect(route('prescriptions.show', $prescription));
    }

    public function render()
    {
        $title = $this->isEdit ? 'Modifier l\'Ordonnance' : 'Nouvelle Ordonnance';

        return view('livewire.prescription.prescription-form', [
            'patients' => Patient::active()->orderBy('last_name')->get(),
            'doctors' => User::doctors()->active()->get(),
            'medications' => Medication::active()->orderBy('name')->get(),
            'patientAllergies' => $this->patient_id
                ? Patient::whereKey($this->patient_id)->value('known_allergies')
                : null,
        ])->layout('layouts.cabinet', [
            'title' => $title,
            'header' => $title,
            'headerIcon' => 'icon-file-text',
            'breadcrumbs' => [
                ['label' => 'Ordonnances', 'url' => route('prescriptions.index')],
                ['label' => $this->isEdit ? 'Modifier' : 'Nouvelle'],
            ],
        ]);
    }
}
