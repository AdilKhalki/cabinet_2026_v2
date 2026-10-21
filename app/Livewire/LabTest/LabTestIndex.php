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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, ['requested', 'pending', 'completed', 'cancelled'], true)) {
            return;
        }

        $labTest = LabTest::findOrFail($id);
        $this->authorize('update', $labTest);

        $data = ['status' => $status];
        if ($status === 'completed' && ! $labTest->result_date) {
            $data['result_date'] = today();
        }
        $labTest->update($data);
    }

    public function delete(int $id): void
    {
        $labTest = LabTest::findOrFail($id);
        $this->authorize('delete', $labTest);
        $labTest->delete();
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
        return view('livewire.lab-test.lab-test-index', [
            'labTests' => $this->labTests,
            'colors' => [
                'pending' => 'warning',
                'completed' => 'success',
                'cancelled' => 'default',
            ],
        ])->layout('layouts.cabinet', [
            'title' => 'Analyses',
            'header' => 'Analyses & Résultats de Laboratoire',
            'headerIcon' => 'icon-thermometer',
            'breadcrumbs' => [['label' => 'Analyses']],
        ]);
    }
}
