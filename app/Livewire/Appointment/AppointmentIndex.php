<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
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
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Champs formulaire
    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public string $scheduled_at = '';
    public int $duration_minutes = 30;
    public string $consultation_type = 'general';
    public string $notes = '';
    public string $color = '#01a9ac';

    protected $queryString = ['search', 'statusFilter', 'doctorFilter', 'dateFilter'];

    public function updatedSearch(): void { $this->resetPage(); }

    public function openForm(?int $id = null): void
    {
        $this->resetForm();
        if ($id) {
            $appt = Appointment::findOrFail($id);
            $this->editingId = $id;
            $this->patient_id = $appt->patient_id;
            $this->doctor_id = $appt->doctor_id;
            $this->scheduled_at = $appt->scheduled_at->format('Y-m-d\TH:i');
            $this->duration_minutes = $appt->duration_minutes;
            $this->consultation_type = $appt->consultation_type;
            $this->notes = $appt->notes ?? '';
            $this->color = $appt->color ?? '#01a9ac';
        } else {
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
        $rules = [
            'patient_id'        => 'required|exists:patients,id',
            'doctor_id'         => 'required|exists:users,id',
            'scheduled_at'      => 'required|date',
            'duration_minutes'  => 'required|integer|min:5|max:480',
            'consultation_type' => 'required|in:general,specialized,followup,emergency,control,teleconsultation',
            'notes'             => 'nullable|string|max:500',
        ];
        $this->validate($rules);

        $data = [
            'patient_id'        => $this->patient_id,
            'doctor_id'         => $this->doctor_id,
            'scheduled_at'      => $this->scheduled_at,
            'duration_minutes'  => $this->duration_minutes,
            'consultation_type' => $this->consultation_type,
            'notes'             => $this->notes ?: null,
            'color'             => $this->color,
            'created_by'        => auth()->id(),
        ];

        if ($this->editingId) {
            $appt = Appointment::findOrFail($this->editingId);
            $appt->update($data);
            session()->flash('success', 'Rendez-vous modifié.');
        } else {
            Appointment::create($data);
            session()->flash('success', 'Rendez-vous créé.');
        }

        $this->closeForm();
    }

    public function updateStatus(int $id, string $status): void
    {
        $appt = Appointment::findOrFail($id);
        $appt->update(['status' => $status]);

        if ($status === 'waiting') {
            $appt->update(['arrived_at' => now()]);
        } elseif ($status === 'in_progress') {
            $appt->update(['started_at' => now()]);
        } elseif ($status === 'completed') {
            $appt->update(['ended_at' => now()]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Appointment::findOrFail($this->deletingId)->delete();
        $this->showDeleteModal = false;
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
        return Appointment::with(['patient', 'doctor'])
            ->when($this->search, function ($q) {
                $q->whereHas('patient', function ($pq) {
                    $pq->search($this->search);
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->doctorFilter, fn ($q) => $q->where('doctor_id', $this->doctorFilter))
            ->when($this->dateFilter, fn ($q) => $q->whereDate('scheduled_at', $this->dateFilter))
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
        return view('livewire.appointment.appointment-index')
            ->layout('layouts.cabinet', [
                'title' => 'Rendez-vous',
                'header' => 'Gestion des Rendez-vous',
                'headerIcon' => 'icon-calendar',
                'breadcrumbs' => [['label' => 'Rendez-vous']],
            ]);
    }
}
