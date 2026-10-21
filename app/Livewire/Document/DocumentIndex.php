<?php

namespace App\Livewire\Document;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
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

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
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
    public function documents()
    {
        return Document::with(['patient', 'uploadedBy'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->search($this->search));
            }))
            ->when(array_key_exists($this->categoryFilter, Document::CATEGORY_LABELS),
                fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function download(int $id)
    {
        $doc = Document::findOrFail($id);
        $this->authorize('view', $doc);

        $disk = $doc->storageDisk();
        abort_unless($doc->file_path && Storage::disk($disk)->exists($doc->file_path), 404);

        return Storage::disk($disk)->download($doc->file_path, $doc->file_name);
    }

    public function delete(int $id): void
    {
        $doc = Document::findOrFail($id);
        $this->authorize('delete', $doc);

        // Suppression logique : le fichier est conservé pour permettre une restauration
        // (obligation de conservation du dossier médical).
        $doc->delete();

        session()->flash('success', 'Document supprimé.');
        unset($this->documents);
    }

    public function render()
    {
        return view('livewire.document.document-index', [
            'documents' => $this->documents,
            'categories' => Document::CATEGORY_LABELS,
        ])->layout('layouts.cabinet', [
            'title' => 'Documents',
            'header' => 'Documents',
            'headerIcon' => 'icon-folder',
            'breadcrumbs' => [['label' => 'Documents']],
        ]);
    }
}
