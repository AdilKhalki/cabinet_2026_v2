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

    /** Colonnes autorisées pour le tri (évite les erreurs SQL sur une valeur d'URL arbitraire) */
    private const SORTABLE = ['created_at', 'dossier_number', 'last_name', 'birth_date'];

    private const PER_PAGE_OPTIONS = [15, 25, 50];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $genderFilter = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    /** Afficher les dossiers archivés (supprimés logiquement) */
    #[Url]
    public bool $showArchived = false;

    public int $perPage = 15;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGenderFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedShowArchived(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

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
        session()->flash('success', 'Patient archivé avec succès.');
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function restore(int $id): void
    {
        $patient = Patient::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $patient);
        $patient->restore();
        session()->flash('success', 'Dossier patient restauré.');
    }

    #[Computed]
    public function patients()
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 15;

        $canSeeArchived = $this->showArchived && auth()->user()->can('delete patients');

        return Patient::query()
            ->when($canSeeArchived, fn ($q) => $q->onlyTrashed())
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when(in_array($this->genderFilter, ['male', 'female'], true), fn ($q) => $q->where('gender', $this->genderFilter))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    public function render()
    {
        return view('livewire.patient.patient-index', [
            'patients' => $this->patients,
        ])->layout('layouts.cabinet', [
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
