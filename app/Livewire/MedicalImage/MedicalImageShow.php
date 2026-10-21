<?php

namespace App\Livewire\MedicalImage;

use App\Models\MedicalImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class MedicalImageShow extends Component
{
    public MedicalImage $medicalImage;
    public bool $editing = false;
    public string $radiologicalReport = '';

    public function mount(MedicalImage $medicalImage): void
    {
        $this->authorize('view', $medicalImage);

        $this->medicalImage      = $medicalImage->load(['patient', 'doctor', 'consultation']);
        $this->radiologicalReport = $medicalImage->radiological_report ?? '';
    }

    public function startEditing(): void
    {
        $this->authorize('update', $this->medicalImage);
        $this->radiologicalReport = $this->medicalImage->radiological_report ?? '';
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function saveReport(): void
    {
        $this->authorize('update', $this->medicalImage);

        $this->validate(['radiologicalReport' => 'nullable|string|max:10000']);
        $this->medicalImage->update(['radiological_report' => $this->radiologicalReport ?: null]);
        $this->editing = false;
        session()->flash('success', 'Compte-rendu mis à jour.');
    }

    public function download()
    {
        $this->authorize('view', $this->medicalImage);

        $image = $this->medicalImage;
        $disk = $image->storageDisk();
        abort_unless($image->file_path && Storage::disk($disk)->exists($image->file_path), 404);

        $name = \Illuminate\Support\Str::slug($image->title ?: 'imagerie').'.'.($image->file_type ?: pathinfo($image->file_path, PATHINFO_EXTENSION));

        return Storage::disk($disk)->download($image->file_path, $name);
    }

    public function render()
    {
        return view('livewire.medical-image.medical-image-show', [
            'medicalImage'   => $this->medicalImage,
            'categoryLabels' => MedicalImage::CATEGORY_LABELS,
            'editing'        => $this->editing,
        ])->layout('layouts.cabinet', [
            'title'      => $this->medicalImage->title,
            'header'     => 'Image Médicale',
            'headerIcon' => 'icon-image',
            'breadcrumbs' => [
                ['label' => 'Imagerie Médicale', 'url' => route('medical-images.index')],
                ['label' => $this->medicalImage->title],
            ],
        ]);
    }
}
