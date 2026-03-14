<?php

namespace App\Livewire\Prescription;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionLine;
use App\Models\User;
use Livewire\Component;

class PrescriptionForm extends Component
{
    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public int|string $consultation_id = '';
    public string $prescribed_at = '';
    public string $valid_until = '';
    public string $notes = '';
    public array $lines = [];

    public function mount(): void
    {
        $this->prescribed_at = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';
        $this->addLine();
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

    public function save(): void
    {
        $this->validate([
            'patient_id'  => 'required|exists:patients,id',
            'doctor_id'   => 'required|exists:users,id',
            'prescribed_at' => 'required|date',
            'lines'       => 'required|array|min:1',
            'lines.*.medication_name' => 'required|string',
        ]);

        $prescription = Prescription::create([
            'patient_id'      => $this->patient_id,
            'doctor_id'       => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'prescribed_at'   => $this->prescribed_at,
            'valid_until'     => $this->valid_until ?: null,
            'notes'           => $this->notes ?: null,
            'created_by'      => auth()->id(),
        ]);

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

        session()->flash('success', 'Ordonnance créée.');
        $this->redirect(route('prescriptions.show', $prescription));
    }

    public function render()
    {
        return view('livewire.prescription.prescription-form', [
            'patients'  => Patient::active()->orderBy('last_name')->get(),
            'doctors'   => User::doctors()->active()->get(),
            'medications' => Medication::active()->orderBy('name')->get(),
        ])->layout('layouts.cabinet', [
            'title' => 'Nouvelle Ordonnance',
            'header' => 'Nouvelle Ordonnance',
            'headerIcon' => 'icon-file-text',
            'breadcrumbs' => [
                ['label' => 'Ordonnances', 'url' => route('prescriptions.index')],
                ['label' => 'Nouvelle'],
            ],
        ]);
    }
}
