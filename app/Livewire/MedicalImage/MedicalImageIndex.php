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

    public function mount(): void
    {
        $this->authorize('viewAny', MedicalImage::class);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    #[Computed]
    public function medicalImages()
    {
        return MedicalImage::with(['patient', 'doctor'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('body_part', 'like', "%{$this->search}%")
                  ->orWhereHas('patient', fn($pq) => $pq->search($this->search));
            }))
            ->when(array_key_exists($this->categoryFilter, MedicalImage::CATEGORY_LABELS),
                fn($q) => $q->where('category', $this->categoryFilter))
            // Un médecin ne voit dans la liste globale que ses propres examens
            // (le dossier patient affiche l'intégralité de l'imagerie du patient).
            ->when(auth()->user()->isMedecin(), fn($q) => $q->where('doctor_id', auth()->id()))
            ->orderByDesc('image_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    public function delete(int $id): void
    {
        $image = MedicalImage::findOrFail($id);
        $this->authorize('delete', $image);

        // Suppression logique : le fichier est conservé pour permettre une restauration
        $image->delete();

        session()->flash('success', 'Image supprimée.');
        unset($this->medicalImages);
    }

    public function render()
    {
        return view('livewire.medical-image.medical-image-index', [
            'medicalImages' => $this->medicalImages,
            'categories'    => MedicalImage::CATEGORY_LABELS,
        ])->layout('layouts.cabinet', [
            'title'      => 'Imagerie Médicale',
            'header'     => 'Imagerie Médicale',
            'headerIcon' => 'icon-image',
            'breadcrumbs' => [['label' => 'Imagerie Médicale']],
        ]);
    }
}
