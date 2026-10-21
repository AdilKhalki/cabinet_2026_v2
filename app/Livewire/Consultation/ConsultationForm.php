<?php

namespace App\Livewire\Consultation;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ConsultationForm extends Component
{
    public ?int $consultationId = null;

    public ?int $appointmentId = null;

    public bool $isEdit = false;

    public string $status = 'in_progress';

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

    protected array $validationAttributes = [
        'patient_id' => 'patient',
        'doctor_id' => 'médecin',
        'consultation_date' => 'date de consultation',
        'weight_kg' => 'poids',
        'height_cm' => 'taille',
        'heart_rate' => 'fréquence cardiaque',
        'temperature' => 'température',
        'spo2' => 'SpO2',
        'followup_date' => 'date de suivi',
    ];

    protected function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => ['required', Rule::in(User::doctors()->pluck('id')->all())],
            'appointment_id' => 'nullable|exists:appointments,id',
            'consultation_type' => 'required|in:'.implode(',', array_keys(Appointment::TYPE_LABELS)),
            'consultation_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'symptoms' => 'nullable|string|max:2000',
            'clinical_exam' => 'nullable|string|max:3000',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:300',
            'blood_pressure' => ['nullable', 'string', 'max:20', 'regex:/^\d{2,3}\s*\/\s*\d{2,3}$/'],
            'heart_rate' => 'nullable|integer|min:0|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'spo2' => 'nullable|integer|min:0|max:100',
            'diagnosis' => 'nullable|string|max:3000',
            'icd10_code' => 'nullable|string|max:20',
            'treatment_plan' => 'nullable|string|max:3000',
            'doctor_notes' => 'nullable|string|max:3000',
            'summary' => 'nullable|string|max:3000',
            'followup_required' => 'boolean',
            // Sur une consultation existante, une date de suivi passée reste acceptée
            'followup_date' => $this->isEdit
                ? 'nullable|required_if:followup_required,true|date'
                : 'nullable|required_if:followup_required,true|date|after:today',
        ];
    }

    protected function messages(): array
    {
        return [
            'doctor_id.in' => 'Veuillez sélectionner un médecin.',
            'blood_pressure.regex' => 'Format de tension attendu : 120/80.',
            'followup_date.required_if' => 'La date de suivi est obligatoire lorsque le suivi est requis.',
            'followup_date.after' => 'La date de suivi doit être postérieure à aujourd\'hui.',
        ];
    }

    public function mount($consultation = null, ?int $appointmentId = null): void
    {
        $this->consultation_date = now()->format('Y-m-d\TH:i');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($consultation) {
            $record = $consultation instanceof Consultation
                ? $consultation
                : Consultation::findOrFail($consultation);

            $this->authorize('update', $record);
            $this->fillFromRecord($record);

            return;
        }

        $this->authorize('create', Consultation::class);

        if ($patientId = request()->query('patient')) {
            $this->patient_id = (int) $patientId;
        }

        // Démarrage depuis un RDV : /consultations/create?appointmentId=ID (ou ?appointment=ID)
        $appointmentId ??= request()->integer('appointmentId') ?: request()->integer('appointment') ?: null;

        if ($appointmentId) {
            $appt = Appointment::findOrFail($appointmentId);
            $this->authorize('update', $appt);

            // Une consultation existe déjà pour ce RDV : on la reprend au lieu d'en créer un doublon
            if ($existing = $appt->consultation) {
                $this->redirect(route('consultations.edit', $existing), navigate: true);

                return;
            }

            $this->appointmentId = $appointmentId;
            $this->appointment_id = $appointmentId;
            $this->patient_id = $appt->patient_id;
            $this->doctor_id = $appt->doctor_id;
            $this->consultation_type = $appt->consultation_type;
            if ($appt->notes) {
                $this->reason = $appt->notes;
            }
        }
    }

    private function fillFromRecord(Consultation $record): void
    {
        $this->consultationId = $record->id;
        $this->isEdit = true;
        $this->status = $record->status ?? 'in_progress';
        $this->patient_id = $record->patient_id;
        $this->doctor_id = $record->doctor_id;
        $this->appointment_id = $record->appointment_id;
        $this->appointmentId = $record->appointment_id;
        $this->consultation_type = $record->consultation_type ?? 'general';
        $this->consultation_date = $record->consultation_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->reason = $record->reason ?? '';
        $this->symptoms = $record->symptoms ?? '';
        $this->clinical_exam = $record->clinical_exam ?? '';
        $this->weight_kg = (string) ($record->weight_kg ?? '');
        $this->height_cm = (string) ($record->height_cm ?? '');
        $this->blood_pressure = $record->blood_pressure ?? '';
        $this->heart_rate = (string) ($record->heart_rate ?? '');
        $this->temperature = (string) ($record->temperature ?? '');
        $this->spo2 = (string) ($record->spo2 ?? '');
        $this->diagnosis = $record->diagnosis ?? '';
        $this->icd10_code = $record->icd10_code ?? '';
        $this->treatment_plan = $record->treatment_plan ?? '';
        $this->doctor_notes = $record->doctor_notes ?? '';
        $this->summary = $record->summary ?? '';
        $this->followup_required = (bool) $record->followup_required;
        $this->followup_date = $record->followup_date?->format('Y-m-d') ?? '';
    }

    /**
     * @param  bool  $finish  true = « Enregistrer et terminer » (consultation et RDV clôturés),
     *                        false = enregistrer en cours (brouillon).
     */
    public function save(bool $finish = true): void
    {
        $record = $this->isEdit ? Consultation::findOrFail($this->consultationId) : null;
        $record ? $this->authorize('update', $record) : $this->authorize('create', Consultation::class);

        // Un médecin (non admin) ne crée des consultations qu'en son nom ; en édition, ces champs sont figés
        $user = auth()->user();
        if ($record) {
            $this->patient_id = $record->patient_id;
            $this->doctor_id = $record->doctor_id;
            $this->appointment_id = $record->appointment_id;
        } elseif ($user->isMedecin() && ! $user->isAdmin()) {
            $this->doctor_id = $user->id;
        }

        $this->validate();

        $appt = null;
        if (! $record && $this->appointment_id) {
            $appt = Appointment::findOrFail($this->appointment_id);
            $this->authorize('update', $appt);

            if ((int) $appt->patient_id !== (int) $this->patient_id) {
                $this->addError('patient_id', 'Le patient ne correspond pas au rendez-vous.');

                return;
            }
            if ($appt->consultation()->exists()) {
                $this->addError('appointment_id', 'Une consultation existe déjà pour ce rendez-vous.');

                return;
            }
            if (in_array($appt->status, ['cancelled', 'no_show', 'completed'], true)) {
                $this->addError('appointment_id', 'Ce rendez-vous est clôturé (« '.$appt->status_label.' »).');

                return;
            }
        }

        $status = $finish ? 'completed' : ($record?->status ?? 'in_progress');

        $data = [
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'appointment_id' => $this->appointment_id ?: null,
            'consultation_type' => $this->consultation_type,
            'status' => $status,
            'consultation_date' => $this->consultation_date,
            'reason' => $this->reason ?: null,
            'symptoms' => $this->symptoms ?: null,
            'clinical_exam' => $this->clinical_exam ?: null,
            'weight_kg' => $this->nullableNumber($this->weight_kg),
            'height_cm' => $this->nullableNumber($this->height_cm),
            'blood_pressure' => $this->blood_pressure ?: null,
            'heart_rate' => $this->nullableNumber($this->heart_rate),
            'temperature' => $this->nullableNumber($this->temperature),
            'spo2' => $this->nullableNumber($this->spo2),
            'diagnosis' => $this->diagnosis ?: null,
            'icd10_code' => $this->icd10_code ? strtoupper(trim($this->icd10_code)) : null,
            'treatment_plan' => $this->treatment_plan ?: null,
            'doctor_notes' => $this->doctor_notes ?: null,
            'summary' => $this->summary ?: null,
            'followup_required' => $this->followup_required,
            'followup_date' => $this->followup_required ? $this->followup_date : null,
        ];

        $consultation = DB::transaction(function () use ($record, $data, $appt, $status) {
            if ($record) {
                $record->update($data);
                $consultation = $record;
                $appt = $record->appointment;
            } else {
                $consultation = Consultation::create($data + ['created_by' => auth()->id()]);
            }

            // Synchroniser le RDV lié : en cours pendant la consultation, terminé à sa clôture
            if ($appt) {
                if (in_array($appt->status, ['planned', 'confirmed', 'waiting'], true)) {
                    $appt->transitionTo('in_progress');
                }
                if ($status === 'completed' && $appt->canTransitionTo('completed')) {
                    $appt->transitionTo('completed');
                }
            }

            return $consultation;
        });

        if ($record) {
            session()->flash('success', $finish ? 'Consultation mise à jour et terminée.' : 'Consultation mise à jour.');
        } else {
            NotificationService::consultationCreated($consultation->id, (int) $this->patient_id, (int) $this->doctor_id);
            session()->flash('success', $finish ? 'Consultation enregistrée et terminée.' : 'Consultation enregistrée (en cours).');
        }

        $this->redirect(route('consultations.show', $consultation), navigate: true);
    }

    /** '' → null, mais conserve la valeur 0 (contrairement à ?:). */
    private function nullableNumber(string $value): ?string
    {
        return trim($value) === '' ? null : $value;
    }

    public function render()
    {
        // En édition le patient est figé : inutile de charger toute la patientèle
        $patients = $this->isEdit
            ? Patient::withTrashed()->whereKey($this->patient_id)->get()
            : Patient::active()->orderBy('last_name')->get();

        return view('livewire.consultation.consultation-form', [
            'patients' => $patients,
            'doctors' => User::doctors()->active()->get(),
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
