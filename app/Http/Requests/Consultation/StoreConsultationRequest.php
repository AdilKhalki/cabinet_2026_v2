<?php

namespace App\Http\Requests\Consultation;

use App\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Consultation::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'consultation_type' => ['required', 'in:general,specialized,followup,emergency,control,teleconsultation'],
            'consultation_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'symptoms' => ['nullable', 'string', 'max:2000'],
            'clinical_exam' => ['nullable', 'string', 'max:3000'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'diagnosis' => ['nullable', 'string', 'max:3000'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
            'treatment_plan' => ['nullable', 'string', 'max:3000'],
            'doctor_notes' => ['nullable', 'string', 'max:3000'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'followup_required' => ['boolean'],
            'followup_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
