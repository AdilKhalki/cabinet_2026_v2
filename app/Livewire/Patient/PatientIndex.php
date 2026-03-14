<?php

namespace App\Livewire\Patient;

use App\Models\Patient;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PatientIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $genderFilter = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public int $perPage = 15;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    protected $queryString = ['search', 'genderFilter', 'sortBy', 'sortDir'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGenderFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Patient::findOrFail($id));
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $patient = Patient::findOrFail($this->deletingId);
        $this->authorize('delete', $patient);
        $patient->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
        session()->flash('success', 'Patient supprimé avec succès.');
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    #[Computed]
    public function patients()
    {
        return Patient::query()
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->genderFilter, fn ($q) => $q->where('gender', $this->genderFilter))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.patient.patient-index')
            ->layout('layouts.cabinet', [
                'title' => 'Patients',
                'header' => 'Gestion des Patients',
                'headerIcon' => 'icon-users',
                'subHeader' => 'Liste complète des patients du cabinet',
                'breadcrumbs' => [
                    ['label' => 'Patients'],
                ],
            ]);
    }
}
