<?php

namespace App\Livewire\Document;

use App\Models\Consultation;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentForm extends Component
{
    use WithFileUploads;

    public ?Document $document = null;

    public bool $isEdit = false;

    public int|string $patient_id = '';

    public int|string $consultation_id = '';

    public string $category = '';

    public string $title = '';

    public string $description = '';

    public string $source = '';

    public string $document_date = '';

    public $file = null;

    public string $existingFileName = '';

    public function mount(?Document $document = null): void
    {
        $this->document_date = today()->format('Y-m-d');

        if ($document && $document->exists) {
            $this->authorize('update', $document);

            $this->document = $document;
            $this->isEdit = true;
            $this->patient_id = $document->patient_id;
            $this->consultation_id = $document->consultation_id ?? '';
            $this->category = $document->category;
            $this->title = $document->title;
            $this->description = $document->description ?? '';
            $this->source = $document->source ?? '';
            $this->document_date = $document->document_date?->format('Y-m-d') ?? today()->format('Y-m-d');
            $this->existingFileName = $document->file_name ?? '';
        } else {
            $this->authorize('create', Document::class);

            if ($patientId = request()->query('patient')) {
                $this->patient_id = (int) $patientId;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            // La consultation liée doit appartenir au même patient
            'consultation_id' => [
                'nullable',
                Rule::exists('consultations', 'id')->where('patient_id', (int) $this->patient_id),
            ],
            'category' => 'required|in:'.implode(',', array_keys(Document::CATEGORY_LABELS)),
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:150',
            'document_date' => 'nullable|date|before_or_equal:today',
            // 10 Mo : limite des fichiers temporaires Livewire (12 Mo par défaut)
            'file' => ($this->isEdit ? 'nullable' : 'required').'|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'patient_id' => 'patient',
            'consultation_id' => 'consultation',
            'category' => 'catégorie',
            'title' => 'titre',
            'document_date' => 'date du document',
            'file' => 'fichier',
        ];
    }

    public function save(): void
    {
        $this->isEdit
            ? $this->authorize('update', $this->document)
            : $this->authorize('create', Document::class);

        $this->validate();

        $data = [
            'patient_id' => $this->patient_id,
            'consultation_id' => $this->consultation_id ?: null,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'source' => $this->source ?: null,
            'document_date' => $this->document_date ?: null,
        ];

        if (! $this->isEdit) {
            $data['uploaded_by'] = auth()->id();
        }

        if ($this->file) {
            // Disque privé : jamais d'URL publique pour un document médical
            $data['file_path'] = $this->file->store('documents', Document::DISK);
            $data['file_name'] = mb_substr(basename($this->file->getClientOriginalName()), 0, 255);
            $data['file_type'] = strtolower($this->file->guessExtension() ?: $this->file->getClientOriginalExtension());
            $data['file_size'] = $this->file->getSize();
        }

        if ($this->isEdit) {
            $oldDocument = $this->file ? clone $this->document : null;
            $this->document->update($data);
            // Supprimer l'ancien fichier seulement après l'enregistrement réussi
            $oldDocument?->deleteFile();
            session()->flash('success', 'Document mis à jour.');
        } else {
            Document::create($data);
            session()->flash('success', 'Document ajouté avec succès.');
        }

        $this->redirect(route('documents.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.document.document-form', [
            // Patients actifs + le patient du document en cours d'édition (même inactif)
            'patients' => Patient::where(fn ($q) => $q->where('is_active', true)
                ->when($this->isEdit, fn ($q) => $q->orWhere('id', $this->patient_id)))
                ->orderBy('last_name')->get(),
            'consultations' => $this->patient_id
                ? Consultation::where('patient_id', $this->patient_id)
                    ->orderByDesc('consultation_date')
                    ->take(50)
                    ->get()
                : collect(),
            'categories' => Document::CATEGORY_LABELS,
            'isEdit' => $this->isEdit,
            'file' => $this->file,
            'existingFileName' => $this->existingFileName,
        ])->layout('layouts.cabinet', [
            'title' => $this->isEdit ? 'Modifier Document' : 'Nouveau Document',
            'header' => $this->isEdit ? 'Modifier le Document' : 'Nouveau Document',
            'headerIcon' => 'icon-folder',
            'breadcrumbs' => [
                ['label' => 'Documents', 'url' => route('documents.index')],
                ['label' => $this->isEdit ? 'Modifier' : 'Nouveau'],
            ],
        ]);
    }
}
