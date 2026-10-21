<?php

namespace App\Livewire\Admin;

use App\Models\MedicalAct;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MedicalActIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    public int $perPage = 15;

    public ?int $editingId = null;

    public bool $creatingNew = false;

    public array $editForm = [];

    public array $newForm = [];

    /** Vérifiée à chaque requête (rendu initial ET appels d'actions Livewire) */
    public function boot(): void
    {
        abort_unless(auth()->user()?->can('manage medications'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function acts()
    {
        return MedicalAct::when($this->search, fn ($q) => $q->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%");
        }))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('category')->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function categories()
    {
        return MedicalAct::distinct()->orderBy('category')->pluck('category')->filter();
    }

    public function addNew(): void
    {
        $this->cancelEdit();
        $this->creatingNew = true;
        $this->newForm = [
            'code' => '',
            'name' => '',
            'category' => '',
            'price' => '',
            'duration_minutes' => '30',
            'is_active' => '1',
        ];
    }

    public function openEdit(int $id): void
    {
        $this->creatingNew = false;
        $act = MedicalAct::findOrFail($id);
        $this->editingId = $id;
        $this->editForm = [
            'code' => $act->code ?? '',
            'name' => $act->name,
            'category' => $act->category ?? '',
            'price' => (string) $act->price,
            'duration_minutes' => (string) $act->duration_minutes,
            'is_active' => $act->is_active ? '1' : '0',
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editForm = [];
    }

    public function save(): void
    {
        $this->validate([
            'editForm.code' => 'nullable|string|max:20|unique:medical_acts,code,'.$this->editingId,
            'editForm.name' => 'required|string|max:255',
            'editForm.category' => 'nullable|string|max:100',
            'editForm.price' => 'required|numeric|min:0',
            'editForm.duration_minutes' => 'required|integer|min:5',
            'editForm.is_active' => 'boolean',
        ]);

        MedicalAct::findOrFail($this->editingId)->update([
            'code' => $this->editForm['code'] ?: null,
            'name' => $this->editForm['name'],
            'category' => $this->editForm['category'] ?: null,
            'price' => $this->editForm['price'],
            'duration_minutes' => $this->editForm['duration_minutes'],
            'is_active' => $this->editForm['is_active'],
        ]);

        session()->flash('success', 'Acte médical mis à jour.');
        $this->cancelEdit();
        unset($this->acts);
    }

    public function saveNew(): void
    {
        $this->validate([
            'newForm.code' => 'nullable|string|max:20|unique:medical_acts,code',
            'newForm.name' => 'required|string|max:255',
            'newForm.category' => 'nullable|string|max:100',
            'newForm.price' => 'required|numeric|min:0',
            'newForm.duration_minutes' => 'required|integer|min:5',
            'newForm.is_active' => 'boolean',
        ]);

        MedicalAct::create([
            'code' => $this->newForm['code'] ?: null,
            'name' => $this->newForm['name'],
            'category' => $this->newForm['category'] ?: null,
            'price' => $this->newForm['price'],
            'duration_minutes' => $this->newForm['duration_minutes'],
            'is_active' => $this->newForm['is_active'],
        ]);

        session()->flash('success', 'Acte médical créé.');
        $this->creatingNew = false;
        $this->newForm = [];
        unset($this->acts);
    }

    public function delete(int $id): void
    {
        MedicalAct::findOrFail($id)->delete();
        session()->flash('success', 'Acte supprimé.');
        unset($this->acts);
    }

    public function render()
    {
        return view('livewire.admin.medical-act-index', [
            'medicalActs' => $this->acts,
            'categories' => $this->categories,
            'editingId' => $this->editingId,
            'creatingNew' => $this->creatingNew,
        ])->layout('layouts.cabinet', [
            'title' => 'Actes Médicaux',
            'header' => 'Actes Médicaux',
            'headerIcon' => 'icon-list',
            'breadcrumbs' => [['label' => 'Actes Médicaux']],
        ]);
    }
}
