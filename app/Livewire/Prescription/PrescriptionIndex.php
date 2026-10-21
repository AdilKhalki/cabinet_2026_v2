<?php

namespace App\Livewire\Prescription;

use App\Models\Prescription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PrescriptionIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    public int $perPage = 15;
    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void { $this->resetPage(); }

    #[Computed]
    public function prescriptions()
    {
        return Prescription::with(['patient', 'doctor', 'lines'])
            ->when($this->search, fn($q) => $q->whereHas('patient', fn($pq) => $pq->search($this->search)))
            ->when(auth()->user()->isMedecin(), fn($q) => $q->where('doctor_id', auth()->id()))
            ->orderByDesc('prescribed_at')
            ->paginate($this->perPage);
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Prescription::class);
    }

    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }
    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            $prescription = Prescription::findOrFail($this->confirmingDeleteId);
            $this->authorize('delete', $prescription);
            $prescription->delete();
            session()->flash('success', 'Ordonnance supprimée.');
            $this->confirmingDeleteId = null;
            unset($this->prescriptions);
        }
    }

    public function render()
    {
        return view('livewire.prescription.prescription-index', [
            'prescriptions' => $this->prescriptions,
        ])->layout('layouts.cabinet', [
                'title' => 'Ordonnances',
                'header' => 'Ordonnances',
                'headerIcon' => 'icon-file-text',
                'breadcrumbs' => [['label' => 'Ordonnances']],
            ]);
    }
}
