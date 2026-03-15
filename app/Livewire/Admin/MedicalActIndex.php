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

    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;

    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $category = '';
    public string $price = '';
    public string $duration_minutes = '30';
    public bool $is_active = true;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    #[Computed]
    public function acts()
    {
        return MedicalAct::when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('category')->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function categories()
    {
        return MedicalAct::distinct()->orderBy('category')->pluck('category')->filter();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'code', 'name', 'description', 'category', 'price']);
        $this->duration_minutes = '30';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $act = MedicalAct::findOrFail($id);
        $this->editingId = $id;
        $this->code = $act->code ?? '';
        $this->name = $act->name;
        $this->description = $act->description ?? '';
        $this->category = $act->category ?? '';
        $this->price = (string) $act->price;
        $this->duration_minutes = (string) $act->duration_minutes;
        $this->is_active = $act->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'code'             => 'nullable|string|max:20|unique:medical_acts,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'name'             => 'required|string|max:255',
            'category'         => 'nullable|string|max:100',
            'price'            => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:5',
            'is_active'        => 'boolean',
        ]);

        $data = [
            'code'             => $this->code ?: null,
            'name'             => $this->name,
            'description'      => $this->description ?: null,
            'category'         => $this->category ?: null,
            'price'            => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'is_active'        => $this->is_active,
        ];

        if ($this->editingId) {
            MedicalAct::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Acte médical mis à jour.');
        } else {
            MedicalAct::create($data);
            session()->flash('success', 'Acte médical créé.');
        }

        $this->showModal = false;
        unset($this->acts);
    }

    public function toggleActive(int $id): void
    {
        $act = MedicalAct::findOrFail($id);
        $act->update(['is_active' => !$act->is_active]);
        unset($this->acts);
    }

    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            MedicalAct::findOrFail($this->confirmingDeleteId)->delete();
            session()->flash('success', 'Acte supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->acts);
        }
    }

    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    public function render()
    {
        return view('livewire.admin.medical-act-index', [
            'medicalActs' => $this->acts,
            'categories'  => $this->categories,
            'editingId'   => $this->editingId,
            'creatingNew' => $this->showModal && !$this->editingId,
        ])->layout('layouts.cabinet', [
            'title'      => 'Actes Médicaux',
            'header'     => 'Actes Médicaux',
            'headerIcon' => 'icon-list',
            'breadcrumbs' => [['label' => 'Actes Médicaux']],
        ]);
    }
}
