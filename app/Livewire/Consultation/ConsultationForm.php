<?php

namespace App\Livewire\Consultation;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Livewire\Component;

class ConsultationForm extends Component
{
    public ?int $consultationId = null;
    public ?int $appointmentId = null;
    public bool $isEdit = false;

    // Champs
    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public int|string|null $appointment_id = null;
    public string $consultation_type = 'general';
    public string $consultation_date = '';
    public string $reason = '';
    public string $symptoms = '';
    public string $clinical_exam = '';
    public string $weight_kg = '';
    public string $height_cm = '';
    public string $blood_pressure = '';
    public string $heart_rate = '';
    public string $temperature = '';
    public string $spo2 = '';
    public string $diagnosis = '';
    public string $icd10_code = '';
    public string $treatment_plan = '';
    public string $doctor_notes = '';
    public string $summary = '';
    public bool $followup_required = false;
    public string $followup_date = '';

    protected function rules(): array
    {
        return [
            'patient_id'        => 'required|exists:patients,id',
            'doctor_id'         => 'required|exists:users,id',
            'consultation_type' => 'required|in:general,specialized,followup,emergency,control,teleconsultation',
            'consultation_date' => 'required|date',
            'reason'            => 'nullable|string|max:500',
            'symptoms'          => 'nullable|string|max:2000',
            'clinical_exam'     => 'nullable|string|max:3000',
            'weight_kg'         => 'nullable|numeric|min:0|max:500',
            'height_cm'         => 'nullable|numeric|min:0|max:300',
            'blood_pressure'    => 'nullable|string|max:20',
            'heart_rate'        => 'nullable|integer|min:0|max:300',
            'temperature'       => 'nullable|numeric|min:30|max:45',
            'spo2'              => 'nullable|integer|min:0|max:100',
            'diagnosis'         => 'nullable|string|max:3000',
            'icd10_code'        => 'nullable|string|max:20',
            'treatment_plan'    => 'nullable|string|max:3000',
            'doctor_notes'      => 'nullable|string|max:3000',
            'summary'           => 'nullable|string|max:3000',
            'followup_required' => 'boolean',
            'followup_date'     => 'nullable|date|after:today',
        ];
    }

    public function mount($consultation = null, ?int $appointmentId = null): void
    {
        $this->consultation_date = now()->format('Y-m-d\TH:i');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($patientId = request()->query('patient')) {
            $this->patient_id = (int) $patientId;
        }

        if ($appointmentId) {
            $appt = \App\Models\Appointment::findOrFail($appointmentId);
            $this->appointmentId = $appointmentId;
            $this->appointment_id = $appointmentId;
            $this->patient_id = $appt->patient_id;
            $this->doctor_id = $appt->doctor_id;
            $this->consultation_type = $appt->consultation_type;
        }

        if ($consultation) {
            $record = $consultation instanceof Consultation
                ? $consultation
                : Consultation::findOrFail($consultation);

            $this->consultationId = $record->id;
            $this->isEdit = true;
            $this->patient_id = $record->patient_id;
            $this->doctor_id = $record->doctor_id;
            $this->appointment_id = $record->appointment_id;
            $this->consultation_type = $record->consultation_type ?? 'general';
            $this->consultation_date = $record->consultation_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
            $this->reason = $record->reason ?? '';
            $this->symptoms = $record->symptoms ?? '';
            $this->clinical_exam = $record->clinical_exam ?? '';
            $this->weight_kg = $record->weight_kg ?? '';
            $this->height_cm = $record->height_cm ?? '';
            $this->blood_pressure = $record->blood_pressure ?? '';
            $this->heart_rate = $record->heart_rate ?? '';
            $this->temperature = $record->temperature ?? '';
            $this->spo2 = $record->spo2 ?? '';
            $this->diagnosis = $record->diagnosis ?? '';
            $this->icd10_code = $record->icd10_code ?? '';
            $this->treatment_plan = $record->treatment_plan ?? '';
            $this->doctor_notes = $record->doctor_notes ?? '';
            $this->summary = $record->summary ?? '';
            $this->followup_required = (bool) $record->followup_required;
            $this->followup_date = $record->followup_date?->format('Y-m-d') ?? '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'patient_id'        => $this->patient_id,
            'doctor_id'         => $this->doctor_id,
            'appointment_id'    => $this->appointment_id ?: null,
            'consultation_type' => $this->consultation_type,
            'consultation_date' => $this->consultation_date,
            'reason'            => $this->reason ?: null,
            'symptoms'          => $this->symptoms ?: null,
            'clinical_exam'     => $this->clinical_exam ?: null,
            'weight_kg'         => $this->weight_kg ?: null,
            'height_cm'         => $this->height_cm ?: null,
            'blood_pressure'    => $this->blood_pressure ?: null,
            'heart_rate'        => $this->heart_rate ?: null,
            'temperature'       => $this->temperature ?: null,
            'spo2'              => $this->spo2 ?: null,
            'diagnosis'         => $this->diagnosis ?: null,
            'icd10_code'        => $this->icd10_code ?: null,
            'treatment_plan'    => $this->treatment_plan ?: null,
            'doctor_notes'      => $this->doctor_notes ?: null,
            'summary'           => $this->summary ?: null,
            'followup_required' => $this->followup_required,
            'followup_date'     => $this->followup_required ? $this->followup_date : null,
            'created_by'        => auth()->id(),
        ];

        if ($this->isEdit) {
            $consultation = Consultation::findOrFail($this->consultationId);
            $consultation->update($data);
            session()->flash('success', 'Consultation mise à jour.');
        } else {
            $consultation = Consultation::create($data);
            if ($this->appointment_id) {
                \App\Models\Appointment::findOrFail($this->appointment_id)
                    ->update(['status' => 'completed', 'ended_at' => now()]);
            }
            session()->flash('success', 'Consultation créée.');
        }

        $this->redirect(route('consultations.show', $consultation), navigate: true);
    }

    public function render()
    {
        return view('livewire.consultation.consultation-form', [
            'patients' => Patient::active()->orderBy('last_name')->get(),
            'doctors'  => User::doctors()->active()->get(),
        ])
            ->layout('layouts.cabinet', [
                'title' => $this->isEdit ? 'Modifier Consultation' : 'Nouvelle Consultation',
                'header' => $this->isEdit ? 'Modifier la Consultation' : 'Nouvelle Consultation',
                'headerIcon' => 'icon-activity',
                'breadcrumbs' => [
                    ['label' => 'Consultations', 'url' => route('consultations.index')],
                    ['label' => $this->isEdit ? 'Modifier' : 'Nouvelle'],
                ],
            ]);
    }
}
