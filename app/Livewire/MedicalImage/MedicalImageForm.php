<?php

namespace App\Livewire\MedicalImage;

use App\Models\Consultation;
use App\Models\MedicalImage;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Validation\Rule;
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

    public string $existingFileUrl = '';

    public function mount(?MedicalImage $medicalImage = null): void
    {
        $this->image_date = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($medicalImage && $medicalImage->exists) {
            $this->authorize('update', $medicalImage);

            $this->medicalImage = $medicalImage;
            $this->isEdit = true;
            $this->patient_id = $medicalImage->patient_id;
            $this->doctor_id = $medicalImage->doctor_id ?? '';
            $this->consultation_id = $medicalImage->consultation_id ?? '';
            $this->category = $medicalImage->category;
            $this->title = $medicalImage->title;
            $this->description = $medicalImage->description ?? '';
            $this->body_part = $medicalImage->body_part ?? '';
            $this->image_date = $medicalImage->image_date?->format('Y-m-d') ?? today()->format('Y-m-d');
            $this->radiological_report = $medicalImage->radiological_report ?? '';
            $this->existingFilePath = $medicalImage->file_path ?? '';
            $this->existingFileUrl = $medicalImage->is_image ? (string) $medicalImage->file_url : '';
        } else {
            $this->authorize('create', MedicalImage::class);

            if ($patientId = request()->query('patient')) {
                $this->patient_id = (int) $patientId;
            }
            if ($consultationId = request()->query('consultation')) {
                $this->consultation_id = (int) $consultationId;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            // doctor_id est NOT NULL en base : obligatoire, et doit être un médecin
            'doctor_id' => ['required', Rule::in(User::doctors()->pluck('id')->all())],
            'consultation_id' => [
                'nullable',
                Rule::exists('consultations', 'id')->where('patient_id', (int) $this->patient_id),
            ],
            'category' => 'required|in:'.implode(',', array_keys(MedicalImage::CATEGORY_LABELS)),
            'title' => 'required|string|max:200',
            'body_part' => 'nullable|string|max:100',
            // image_date est NOT NULL en base
            'image_date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:1000',
            'radiological_report' => 'nullable|string|max:10000',
            // file_path est NOT NULL en base : fichier obligatoire à la création
            'imageFile' => ($this->isEdit ? 'nullable' : 'required').'|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:10240',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'patient_id' => 'patient',
            'doctor_id' => 'médecin',
            'category' => 'catégorie',
            'title' => 'titre',
            'image_date' => "date de l'image",
            'imageFile' => 'fichier',
        ];
    }

    public function save(): void
    {
        $this->isEdit
            ? $this->authorize('update', $this->medicalImage)
            : $this->authorize('create', MedicalImage::class);

        $this->validate();

        $data = [
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'body_part' => $this->body_part ?: null,
            'image_date' => $this->image_date,
            'radiological_report' => $this->radiological_report ?: null,
        ];

        if (! $this->isEdit) {
            $data['created_by'] = auth()->id();
        }

        if ($this->imageFile) {
            // Disque privé : l'imagerie médicale n'est jamais exposée publiquement
            $data['file_path'] = $this->imageFile->store('medical-images', MedicalImage::DISK);
            $data['file_type'] = strtolower($this->imageFile->guessExtension() ?: $this->imageFile->getClientOriginalExtension());
            $data['file_size'] = $this->imageFile->getSize();
        }

        if ($this->isEdit) {
            $old = $this->imageFile ? clone $this->medicalImage : null;
            $this->medicalImage->update($data);
            $old?->deleteFile();
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
            // Patients actifs + le patient de l'image en cours d'édition (même inactif)
            'patients' => Patient::where(fn ($q) => $q->where('is_active', true)
                ->when($this->isEdit, fn ($q) => $q->orWhere('id', $this->patient_id)))
                ->orderBy('last_name')->get(),
            'doctors' => User::doctors()->active()->orderBy('name')->get(),
            'consultations' => $this->patient_id
                ? Consultation::where('patient_id', $this->patient_id)->orderByDesc('consultation_date')->take(50)->get()
                : collect(),
            'categoryLabels' => MedicalImage::CATEGORY_LABELS,
            'isEdit' => $this->isEdit,
            'imageFile' => $this->imageFile,
            'existingFilePath' => $this->existingFilePath,
        ])->layout('layouts.cabinet', [
            'title' => $this->isEdit ? 'Modifier Image' : 'Nouvelle Image',
            'header' => $this->isEdit ? 'Modifier Image Médicale' : 'Nouvelle Image Médicale',
            'headerIcon' => 'icon-image',
            'breadcrumbs' => [
                ['label' => 'Imagerie Médicale', 'url' => route('medical-images.index')],
                ['label' => $this->isEdit ? 'Modifier' : 'Nouvelle'],
            ],
        ]);
    }
}
