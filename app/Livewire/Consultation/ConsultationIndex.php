<?php

namespace App\Livewire\Consultation;

use App\Models\Consultation;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ConsultationIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function consultations()
    {
        return Consultation::with(['patient', 'doctor'])
            ->when($this->search, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->search($this->search)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->orderByDesc('consultation_date')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.consultation.consultation-index', [
            'consultations' => $this->consultations,
        ])->layout('layouts.cabinet', [
            'title' => 'Consultations',
            'header' => 'Consultations',
            'headerIcon' => 'icon-activity',
            'breadcrumbs' => [['label' => 'Consultations']],
        ]);
    }
}
