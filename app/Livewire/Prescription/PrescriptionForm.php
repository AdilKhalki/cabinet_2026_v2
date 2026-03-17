<?php

namespace App\Livewire\Prescription;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionLine;
use App\Models\User;
use App\Services\NotificationService;
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
        }

        // Mode édition
        if ($prescription) {
            $record = $prescription instanceof Prescription
                ? $prescription
                : Prescription::with('lines')->findOrFail($prescription);

            $this->prescriptionId = $record->id;
            $this->isEdit = true;
            $this->patient_id = $record->patient_id;
            $this->doctor_id = $record->doctor_id;
            $this->consultation_id = $record->consultation_id ?? '';
            $this->prescribed_at = $record->prescribed_at ? \Carbon\Carbon::parse($record->prescribed_at)->format('Y-m-d') : today()->format('Y-m-d');
            $this->valid_until = $record->valid_until ? \Carbon\Carbon::parse($record->valid_until)->format('Y-m-d') : '';
            $this->notes = $record->notes ?? '';

            $this->lines = [];
            foreach ($record->lines()->orderBy('sort_order')->get() as $line) {
                $this->lines[] = [
                    'medication_id'   => $line->medication_id ?? '',
                    'medication_name' => $line->medication_name ?? '',
                    'dosage'          => $line->dosage ?? '',
                    'frequency'       => $line->frequency ?? '',
                    'duration_days'   => $line->duration_days ?? '',
                    'route'           => $line->route ?? 'Oral',
                    'instructions'    => $line->instructions ?? '',
                    'quantity'        => $line->quantity ?? '',
                ];
            }

            if (empty($this->lines)) {
                $this->addLine();
            }
        } else {
            $this->addLine();
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

    public function selectMedication(int $index, string $medicationId): void
    {
        if ($medicationId) {
            $med = Medication::find((int) $medicationId);
            if ($med) {
                $name = $med->name;
                if ($med->strength) $name .= ' ' . $med->strength;
                $this->lines[$index]['medication_name'] = $name;
            }
        } else {
            $this->lines[$index]['medication_name'] = '';
        }
    }

    public function save(): void
    {
        $this->validate([
            'patient_id'  => 'required|exists:patients,id',
            'doctor_id'   => 'required|exists:users,id',
            'prescribed_at' => 'required|date',
            'lines'       => 'required|array|min:1',
            'lines.*.medication_name' => 'required|string',
        ]);

        $data = [
            'patient_id'      => $this->patient_id,
            'doctor_id'       => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'prescribed_at'   => $this->prescribed_at,
            'valid_until'     => $this->valid_until ?: null,
            'notes'           => $this->notes ?: null,
        ];

        if ($this->isEdit) {
            $prescription = Prescription::findOrFail($this->prescriptionId);
            $prescription->update($data);

            // Supprimer les anciennes lignes et recréer
            $prescription->lines()->delete();
            $message = 'Ordonnance mise à jour.';
        } else {
            $data['created_by'] = auth()->id();
            $prescription = Prescription::create($data);
            NotificationService::prescriptionCreated($prescription->id, $this->patient_id, $this->doctor_id);
            $message = 'Ordonnance créée.';
        }

        foreach ($this->lines as $i => $line) {
            PrescriptionLine::create([
                'prescription_id' => $prescription->id,
                'medication_id'   => $line['medication_id'] ?: null,
                'medication_name' => $line['medication_name'],
                'dosage'          => $line['dosage'] ?: null,
                'frequency'       => $line['frequency'] ?: null,
                'duration_days'   => $line['duration_days'] ?: null,
                'route'           => $line['route'] ?: null,
                'instructions'    => $line['instructions'] ?: null,
                'quantity'        => $line['quantity'] ?: null,
                'sort_order'      => $i,
            ]);
        }

        session()->flash('success', $message);
        $this->redirect(route('prescriptions.show', $prescription));
    }

    public function render()
    {
        $title = $this->isEdit ? 'Modifier l\'Ordonnance' : 'Nouvelle Ordonnance';

        return view('livewire.prescription.prescription-form', [
            'patients'    => Patient::active()->orderBy('last_name')->get(),
            'doctors'     => User::doctors()->active()->get(),
            'medications' => Medication::active()->orderBy('name')->get(),
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
