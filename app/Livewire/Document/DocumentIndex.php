<?php

namespace App\Livewire\Document;

use App\Models\Document;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    public int $perPage = 15;
    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    public function getCategoriesArray(): array
    {
        return [
            'ordonnance'    => 'Ordonnance',
            'analyse'       => 'Résultat d\'analyse',
            'imagerie'      => 'Imagerie',
            'certificat'    => 'Certificat',
            'compte_rendu'  => 'Compte-rendu',
            'administratif' => 'Administratif',
            'autre'         => 'Autre',
        ];
    }

    #[Computed]
    public function documents()
    {
        return Document::with(['patient', 'uploadedBy'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhereHas('patient', fn($pq) => $pq
                      ->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->when(auth()->user()->isMedecin(), fn($q) => $q->where('uploaded_by', auth()->id()))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }
    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            $doc = Document::findOrFail($this->confirmingDeleteId);
            if ($doc->file_path) {
                \Illuminate\Support\Facades\Storage::delete($doc->file_path);
            }
            $doc->delete();
            session()->flash('success', 'Document supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->documents);
        }
    }

    public function render()
    {
        return view('livewire.document.document-index', [
            'documents'  => $this->documents,
            'categories' => $this->getCategoriesArray(),
        ])->layout('layouts.cabinet', [
            'title'      => 'Documents',
            'header'     => 'Documents',
            'headerIcon' => 'icon-folder',
            'breadcrumbs' => [['label' => 'Documents']],
        ]);
    }
}
