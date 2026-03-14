<?php

namespace App\Livewire\LabTest;

use App\Models\LabTest;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LabTestIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public int $perPage = 15;

    public function updatedSearch(): void { $this->resetPage(); }

    public function updateStatus(int $id, string $status): void
    {
        LabTest::findOrFail($id)->update(['status' => $status]);
    }

    public function delete(int $id): void
    {
        LabTest::findOrFail($id)->delete();
        session()->flash('success', 'Analyse supprimée.');
    }

    #[Computed]
    public function labTests()
    {
        return LabTest::with(['patient', 'doctor', 'results'])
            ->when($this->search, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->search($this->search)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->where('doctor_id', auth()->id()))
            ->orderByDesc('requested_at')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.lab-test.lab-test-index')
            ->layout('layouts.cabinet', [
                'title' => 'Analyses',
                'header' => 'Analyses & Résultats de Laboratoire',
                'headerIcon' => 'icon-thermometer',
                'breadcrumbs' => [['label' => 'Analyses']],
            ]);
    }
}
