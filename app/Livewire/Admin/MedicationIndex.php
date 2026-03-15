<?php

namespace App\Livewire\Admin;

use App\Models\Medication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MedicationIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public int $perPage = 15;

    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;

    public string $name = '';
    public string $generic_name = '';
    public string $dosage_form = '';
    public string $strength = '';
    public string $manufacturer = '';
    public string $description = '';
    public string $contraindications = '';
    public bool $is_active = true;

    public function updatingSearch(): void { $this->resetPage(); }

    #[Computed]
    public function medications()
    {
        return Medication::when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'generic_name', 'dosage_form', 'strength', 'manufacturer', 'description', 'contraindications']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $med = Medication::findOrFail($id);
        $this->editingId = $id;
        $this->name = $med->name;
        $this->generic_name = $med->generic_name ?? '';
        $this->dosage_form = $med->dosage_form ?? '';
        $this->strength = $med->strength ?? '';
        $this->manufacturer = $med->manufacturer ?? '';
        $this->description = $med->description ?? '';
        $this->contraindications = $med->contraindications ?? '';
        $this->is_active = $med->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:medications,name' . ($this->editingId ? ",{$this->editingId}" : ''),
            'generic_name' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'generic_name' => $this->generic_name ?: null,
            'dosage_form' => $this->dosage_form ?: null,
            'strength' => $this->strength ?: null,
            'manufacturer' => $this->manufacturer ?: null,
            'description' => $this->description ?: null,
            'contraindications' => $this->contraindications ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Medication::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Médicament mis à jour.');
        } else {
            Medication::create($data);
            session()->flash('success', 'Médicament créé.');
        }

        $this->showModal = false;
        unset($this->medications);
    }

    public function toggleActive(int $id): void
    {
        $med = Medication::findOrFail($id);
        $med->update(['is_active' => !$med->is_active]);
        unset($this->medications);
    }

    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            Medication::findOrFail($this->confirmingDeleteId)->delete();
            session()->flash('success', 'Médicament supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->medications);
        }
    }

    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    public function render()
    {
        return view('livewire.admin.medication-index', [
            'medications'        => $this->medications,
            'editingMedicationId'=> $this->editingId,
            'showModal'          => $this->showModal,
        ])->layout('layouts.cabinet', [
            'title'      => 'Médicaments',
            'header'     => 'Médicaments',
            'headerIcon' => 'icon-package',
            'breadcrumbs' => [['label' => 'Médicaments']],
        ]);
    }
}
