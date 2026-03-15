<?php

namespace App\Livewire\MedicalImage;

use App\Models\MedicalImage;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MedicalImageForm extends Component
{
    use WithFileUploads;

    public ?MedicalImage $medicalImage = null;
    public bool $isEdit = false;

    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public int|string $consultation_id = '';
    public string $category = '';
    public string $title = '';
    public string $description = '';
    public string $body_part = '';
    public string $image_date = '';
    public string $radiological_report = '';
    public $imageFile = null;
    public string $existingFilePath = '';

    public function mount(?MedicalImage $medicalImage = null): void
    {
        $this->image_date = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($medicalImage && $medicalImage->exists) {
            $this->medicalImage      = $medicalImage;
            $this->isEdit            = true;
            $this->patient_id        = $medicalImage->patient_id;
            $this->doctor_id         = $medicalImage->doctor_id ?? '';
            $this->consultation_id   = $medicalImage->consultation_id ?? '';
            $this->category          = $medicalImage->category;
            $this->title             = $medicalImage->title;
            $this->description       = $medicalImage->description ?? '';
            $this->body_part         = $medicalImage->body_part ?? '';
            $this->image_date        = $medicalImage->image_date?->format('Y-m-d') ?? today()->format('Y-m-d');
            $this->radiological_report = $medicalImage->radiological_report ?? '';
            $this->existingFilePath  = $medicalImage->file_path ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'patient_id'          => 'required|exists:patients,id',
            'doctor_id'           => 'nullable|exists:users,id',
            'category'            => 'required|in:' . implode(',', array_keys(MedicalImage::CATEGORY_LABELS)),
            'title'               => 'required|string|max:255',
            'body_part'           => 'nullable|string|max:100',
            'image_date'          => 'nullable|date',
            'description'         => 'nullable|string|max:1000',
            'radiological_report' => 'nullable|string',
            'imageFile'           => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:10240',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'patient_id'          => $this->patient_id,
            'doctor_id'           => $this->doctor_id ?: null,
            'consultation_id'     => $this->consultation_id ?: null,
            'category'            => $this->category,
            'title'               => $this->title,
            'description'         => $this->description ?: null,
            'body_part'           => $this->body_part ?: null,
            'image_date'          => $this->image_date ?: null,
            'radiological_report' => $this->radiological_report ?: null,
            'created_by'          => auth()->id(),
        ];

        if ($this->imageFile) {
            if ($this->isEdit && $this->existingFilePath) {
                Storage::disk('public')->delete($this->existingFilePath);
            }
            $path = $this->imageFile->store('medical-images', 'public');
            $data['file_path']  = $path;
            $data['file_type']  = strtolower(pathinfo($this->imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
            $data['file_size']  = $this->imageFile->getSize();
        }

        if ($this->isEdit) {
            $this->medicalImage->update($data);
            $image = $this->medicalImage;
            session()->flash('success', 'Image mise à jour.');
        } else {
            $image = MedicalImage::create($data);
            session()->flash('success', 'Image ajoutée avec succès.');
        }

        $this->redirect(route('medical-images.show', $image), navigate: true);
    }

    public function render()
    {
        return view('livewire.medical-image.medical-image-form', [
            'patients'         => Patient::active()->orderBy('last_name')->get(),
            'doctors'          => User::doctors()->active()->get(),
            'categoryLabels'   => MedicalImage::CATEGORY_LABELS,
            'isEdit'           => $this->isEdit,
            'imageFile'        => $this->imageFile,
            'existingFilePath' => $this->existingFilePath,
        ])->layout('layouts.cabinet', [
            'title'      => $this->isEdit ? 'Modifier Image' : 'Nouvelle Image',
            'header'     => $this->isEdit ? 'Modifier Image Médicale' : 'Nouvelle Image Médicale',
            'headerIcon' => 'icon-image',
            'breadcrumbs' => [
                ['label' => 'Imagerie Médicale', 'url' => route('medical-images.index')],
                ['label' => $this->isEdit ? 'Modifier' : 'Nouvelle'],
            ],
        ]);
    }
}
