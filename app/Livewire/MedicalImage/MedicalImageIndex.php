<?php

namespace App\Livewire\MedicalImage;

use App\Models\MedicalImage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MedicalImageIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    public int $perPage = 12;
    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    public function getCategoriesArray(): array
    {
        return MedicalImage::CATEGORY_LABELS;
    }

    #[Computed]
    public function medicalImages()
    {
        return MedicalImage::with(['patient', 'doctor'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhereHas('patient', fn($pq) => $pq
                      ->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->when(auth()->user()->isMedecin(), fn($q) => $q->where('doctor_id', auth()->id()))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }
    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            $image = MedicalImage::findOrFail($this->confirmingDeleteId);
            if ($image->file_path) {
                \Illuminate\Support\Facades\Storage::delete($image->file_path);
            }
            $image->delete();
            session()->flash('success', 'Image supprimée.');
            $this->confirmingDeleteId = null;
            unset($this->medicalImages);
        }
    }

    public function render()
    {
        return view('livewire.medical-image.medical-image-index', [
            'medicalImages' => $this->medicalImages,
            'categories'    => $this->getCategoriesArray(),
        ])->layout('layouts.cabinet', [
            'title'      => 'Imagerie Médicale',
            'header'     => 'Imagerie Médicale',
            'headerIcon' => 'icon-image',
            'breadcrumbs' => [['label' => 'Imagerie Médicale']],
        ]);
    }
}
