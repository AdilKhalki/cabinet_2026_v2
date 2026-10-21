<?php

namespace App\Livewire\Admin;

use App\Models\Medication;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MedicationIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public int $perPage = 15;

    // Modal state
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields (flat properties — no array)
    public string $name = '';
    public string $generic_name = '';
    public string $dosage_form = '';
    public string $strength = '';
    public string $description = '';
    public bool $is_active = true;

    /** Vérifiée à chaque requête (rendu initial ET appels d'actions Livewire) */
    public function boot(): void
    {
        abort_unless(auth()->user()?->can('manage medications'), 403);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->resetFormFields();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetValidation();
        $med = Medication::findOrFail($id);
        $this->editingId = $id;
        $this->name = $med->name;
        $this->generic_name = $med->generic_name ?? '';
        $this->dosage_form = $med->dosage_form ?? '';
        $this->strength = $med->strength ?? '';
        $this->description = $med->description ?? '';
        $this->is_active = (bool) $med->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'         => 'required|string|max:255|unique:medications,name' . ($this->editingId ? ",{$this->editingId}" : ''),
            'generic_name' => 'nullable|string|max:255',
            'dosage_form'  => 'nullable|string|max:100',
            'strength'     => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:1000',
            'is_active'    => 'boolean',
        ]);

        $data = [
            'name'         => $this->name,
            'generic_name' => $this->generic_name ?: null,
            'dosage_form'  => $this->dosage_form ?: null,
            'strength'     => $this->strength ?: null,
            'description'  => $this->description ?: null,
            'is_active'    => $this->is_active,
        ];

        if ($this->editingId) {
            Medication::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Médicament mis à jour.');
        } else {
            Medication::create($data);
            session()->flash('success', 'Médicament créé.');
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->resetFormFields();
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        Medication::findOrFail($id)->delete();
        session()->flash('success', 'Médicament supprimé.');
    }

    private function resetFormFields(): void
    {
        $this->name = '';
        $this->generic_name = '';
        $this->dosage_form = '';
        $this->strength = '';
        $this->description = '';
        $this->is_active = true;
    }

    public function render()
    {
        $medications = Medication::when($this->search, fn($q) => $q->search($this->search))
            ->when($this->statusFilter !== '', fn($q) => $q->where('is_active', (bool) $this->statusFilter))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.medication-index', [
            'medications' => $medications,
        ])->layout('layouts.cabinet', [
            'title'      => 'Médicaments',
            'header'     => 'Médicaments',
            'headerIcon' => 'icon-package',
            'breadcrumbs' => [['label' => 'Médicaments']],
        ]);
    }
}
