<?php

namespace App\Livewire\MedicalImage;

use App\Models\MedicalImage;
use Livewire\Component;

class MedicalImageShow extends Component
{
    public MedicalImage $medicalImage;
    public bool $editing = false;
    public string $radiologicalReport = '';

    public function mount(MedicalImage $medicalImage): void
    {
        $this->medicalImage      = $medicalImage->load(['patient', 'doctor', 'consultation']);
        $this->radiologicalReport = $medicalImage->radiological_report ?? '';
    }

    public function saveReport(): void
    {
        $this->validate(['radiologicalReport' => 'nullable|string']);
        $this->medicalImage->update(['radiological_report' => $this->radiologicalReport ?: null]);
        $this->editing = false;
        session()->flash('success', 'Compte-rendu mis à jour.');
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
