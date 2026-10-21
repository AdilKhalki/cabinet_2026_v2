<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'consultation_type' => ['required', 'in:general,specialized,followup,emergency,control,teleconsultation'],
            'notes' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }

    public function attributes(): array
    {
        return [
            'patient_id' => 'patient',
            'doctor_id' => 'médecin',
            'scheduled_at' => 'date et heure',
        ];
    }
}
