<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AppointmentIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $doctorFilter = '';
    public string $dateFilter = '';
    public int $perPage = 15;

    public bool $showForm = false;
    public bool $showDeleteModal = false;
    public bool $showCancelModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public ?int $cancellingId = null;
    public string $cancellation_reason = '';

    // Champs formulaire
    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public string $scheduled_at = '';
    public int $duration_minutes = 30;
    public string $consultation_type = 'general';
    public string $notes = '';
    public string $color = '#01a9ac';

    protected $queryString = ['search', 'statusFilter', 'doctorFilter', 'dateFilter'];

    protected array $validationAttributes = [
        'patient_id'          => 'patient',
        'doctor_id'           => 'médecin',
        'scheduled_at'        => 'date et heure',
        'duration_minutes'    => 'durée',
        'consultation_type'   => 'type',
        'cancellation_reason' => "motif d'annulation",
    ];

    public function mount(): void
    {
        // Ouverture directe du formulaire : /appointments/create?patient=ID&date=...
        $patientId = request()->query('patient');
        $date = request()->query('date');

        if ((request()->routeIs('appointments.create') || $patientId || $date)
            && auth()->user()->can('create', Appointment::class)) {
            $this->openForm();

            if ($patientId) {
                $this->patient_id = (int) $patientId;
            }
            if ($date) {
                try {
                    $parsed = Date::parse($date);
                    // Clic sur un jour entier (vue mois) : heure par défaut 09:00
                    if (strlen($date) === 10) {
                        $parsed = $parsed->setTime(9, 0);
                    }
                    $this->scheduled_at = $parsed->format('Y-m-d\TH:i');
                } catch (\Throwable) {
                    // Date invalide ignorée
                }
            }
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'doctorFilter', 'dateFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openForm(?int $id = null): void
    {
        $this->resetForm();
        if ($id) {
            $appt = Appointment::findOrFail($id);
            $this->authorize('update', $appt);
            $this->editingId = $id;
            $this->patient_id = $appt->patient_id;
            $this->doctor_id = $appt->doctor_id;
            $this->scheduled_at = $appt->scheduled_at->format('Y-m-d\TH:i');
            $this->duration_minutes = $appt->duration_minutes;
            $this->consultation_type = $appt->consultation_type;
            $this->notes = $appt->notes ?? '';
            $this->color = $appt->color ?? '#01a9ac';
        } else {
            $this->authorize('create', Appointment::class);
            $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';
        }
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $appt = $this->editingId ? Appointment::findOrFail($this->editingId) : null;
        $appt ? $this->authorize('update', $appt) : $this->authorize('create', Appointment::class);

        if ($appt && in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
            $this->addError('scheduled_at', 'Ce rendez-vous est clôturé et ne peut plus être modifié.');

            return;
        }

        // Un médecin (non admin) ne planifie que dans son propre agenda
        $user = auth()->user();
        if ($user->isMedecin() && ! $user->isAdmin()) {
            $this->doctor_id = $user->id;
        }

        $this->validate([
            'patient_id'        => 'required|exists:patients,id',
            'doctor_id'         => ['required', Rule::in(User::doctors()->active()->pluck('id')->all())],
            'scheduled_at'      => $appt ? 'required|date' : 'required|date|after_or_equal:today',
            'duration_minutes'  => 'required|integer|min:5|max:'.Appointment::MAX_DURATION,
            'consultation_type' => 'required|in:'.implode(',', array_keys(Appointment::TYPE_LABELS)),
            'notes'             => 'nullable|string|max:500',
            'color'             => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'doctor_id.in'                => 'Veuillez sélectionner un médecin actif.',
            'scheduled_at.after_or_equal' => 'Impossible de créer un rendez-vous dans le passé.',
            'color.regex'                 => 'Couleur invalide.',
        ]);

        // Anti double-réservation : le créneau du médecin doit être libre
        $conflict = Appointment::overlapping((int) $this->doctor_id, $this->scheduled_at, $this->duration_minutes, $this->editingId)->first();
        if ($conflict) {
            $this->addError('scheduled_at', sprintf(
                'Créneau indisponible : le médecin a déjà un rendez-vous de %s à %s (%s).',
                $conflict->scheduled_at->format('H:i'),
                $conflict->scheduled_end->format('H:i'),
                $conflict->patient?->full_name ?? 'patient'
            ));

            return;
        }

        $data = [
            'patient_id'        => $this->patient_id,
            'doctor_id'         => $this->doctor_id,
            'scheduled_at'      => $this->scheduled_at,
            'duration_minutes'  => $this->duration_minutes,
            'consultation_type' => $this->consultation_type,
            'notes'             => $this->notes ?: null,
            'color'             => $this->color ?: '#01a9ac',
        ];

        if ($appt) {
            $appt->update($data);
            session()->flash('success', 'Rendez-vous modifié.');
        } else {
            Appointment::create($data + ['created_by' => auth()->id()]);
            NotificationService::appointmentCreated((int) $this->patient_id, (int) $this->doctor_id, $this->scheduled_at);
            session()->flash('success', 'Rendez-vous créé.');
        }

        $this->closeForm();
    }

    public function updateStatus(int $id, string $status): void
    {
        $appt = Appointment::findOrFail($id);
        $this->authorize('update', $appt);

        // L'annulation passe par la modale (motif obligatoire)
        if ($status === 'cancelled') {
            $this->confirmCancel($id);

            return;
        }

        if (! $appt->canTransitionTo($status)) {
            session()->flash('error', 'Changement de statut impossible depuis « '.$appt->status_label.' ».');

            return;
        }

        $appt->transitionTo($status);
    }

    public function confirmCancel(int $id): void
    {
        $appt = Appointment::findOrFail($id);
        $this->authorize('update', $appt);

        $this->cancellingId = $id;
        $this->cancellation_reason = '';
        $this->resetValidation('cancellation_reason');
        $this->showCancelModal = true;
    }

    public function cancel(): void
    {
        $appt = Appointment::findOrFail($this->cancellingId);
        $this->authorize('update', $appt);

        $this->validate(['cancellation_reason' => 'required|string|max:255']);

        if (! $appt->canTransitionTo('cancelled')) {
            $this->addError('cancellation_reason', 'Ce rendez-vous ne peut plus être annulé.');

            return;
        }

        $appt->transitionTo('cancelled', ['cancellation_reason' => $this->cancellation_reason]);
        NotificationService::appointmentCancelled($appt->patient_id, $appt->doctor_id, $appt->scheduled_at->toDateTimeString());

        $this->showCancelModal = false;
        $this->cancellingId = null;
        $this->cancellation_reason = '';
        session()->flash('success', 'Rendez-vous annulé.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Appointment::findOrFail($id));
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $appt = Appointment::findOrFail($this->deletingId);
        $this->authorize('delete', $appt);
        $appt->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
        session()->flash('success', 'Rendez-vous supprimé.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->patient_id = '';
        $this->doctor_id = '';
        $this->scheduled_at = '';
        $this->duration_minutes = 30;
        $this->consultation_type = 'general';
        $this->notes = '';
        $this->color = '#01a9ac';
        $this->resetValidation();
    }

    #[Computed]
    public function appointments()
    {
        return Appointment::with(['patient', 'doctor', 'consultation:id,appointment_id'])
            ->when($this->search, function ($q) {
                $q->whereHas('patient', function ($pq) {
                    $pq->search($this->search);
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->doctorFilter, fn ($q) => $q->where('doctor_id', $this->doctorFilter))
            ->when($this->dateFilter, fn ($q) => $q->whereBetween('scheduled_at', [
                Date::parse($this->dateFilter)->startOfDay(),
                Date::parse($this->dateFilter)->endOfDay(),
            ]))
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->orderBy('scheduled_at', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function doctors()
    {
        return User::doctors()->active()->get();
    }

    #[Computed]
    public function patients()
    {
        return Patient::active()->orderBy('last_name')->get();
    }

    public function render()
    {
        return view('livewire.appointment.appointment-index', [
            'appointments' => $this->appointments,
            'doctors'      => $this->doctors,
            // La liste complète des patients n'est chargée que si le formulaire est ouvert
            'patients'     => $this->showForm ? $this->patients : collect(),
        ])->layout('layouts.cabinet', [
                'title' => 'Rendez-vous',
                'header' => 'Gestion des Rendez-vous',
                'headerIcon' => 'icon-calendar',
                'breadcrumbs' => [['label' => 'Rendez-vous']],
            ]);
    }
}
