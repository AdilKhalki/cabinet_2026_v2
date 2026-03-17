<?php

namespace App\Livewire\Document;

use App\Models\Consultation;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;
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
    public string $existingFilePath = '';

    public function mount(?Document $document = null): void
    {
        $this->document_date = today()->format('Y-m-d');

        if ($patientId = request()->query('patient')) {
            $this->patient_id = (int) $patientId;
        }

        if ($document && $document->exists) {
            $this->document         = $document;
            $this->isEdit           = true;
            $this->patient_id       = $document->patient_id;
            $this->consultation_id  = $document->consultation_id ?? '';
            $this->category         = $document->category;
            $this->title            = $document->title;
            $this->description      = $document->description ?? '';
            $this->source           = $document->source ?? '';
            $this->document_date    = $document->document_date?->format('Y-m-d') ?? today()->format('Y-m-d');
            $this->existingFileName = $document->file_name ?? '';
            $this->existingFilePath = $document->file_path ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'patient_id'      => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'category'        => 'required|in:' . implode(',', array_keys(Document::CATEGORY_LABELS)),
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string|max:1000',
            'source'          => 'nullable|string|max:255',
            'document_date'   => 'nullable|date',
            'file'            => ($this->isEdit ? 'nullable' : 'required') . '|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:20480',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'patient_id'      => $this->patient_id,
            'consultation_id' => $this->consultation_id ?: null,
            'category'        => $this->category,
            'title'           => $this->title,
            'description'     => $this->description ?: null,
            'source'          => $this->source ?: null,
            'document_date'   => $this->document_date ?: null,
            'uploaded_by'     => auth()->id(),
        ];

        if ($this->file) {
            if ($this->isEdit && $this->existingFilePath) {
                Storage::disk('public')->delete($this->existingFilePath);
            }
            $path = $this->file->store('documents', 'public');
            $data['file_path']  = $path;
            $data['file_name']  = $this->file->getClientOriginalName();
            $data['file_type']  = strtolower($this->file->extension());
            $data['file_size']  = $this->file->getSize();
        }

        if ($this->isEdit) {
            $this->document->update($data);
            session()->flash('success', 'Document mis à jour.');
            $this->redirect(route('documents.index'), navigate: true);
        } else {
            Document::create($data);
            session()->flash('success', 'Document ajouté avec succès.');
            $this->redirect(route('documents.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.document.document-form', [
            'patients'      => Patient::active()->orderBy('last_name')->get(),
            'consultations' => Consultation::with('patient')
                ->when($this->patient_id, fn($q) => $q->where('patient_id', $this->patient_id))
                ->orderByDesc('consultation_date')
                ->take(50)
                ->get(),
            'categories'       => Document::CATEGORY_LABELS,
            'isEdit'           => $this->isEdit,
            'file'             => $this->file,
            'existingFileName' => $this->existingFileName,
        ])->layout('layouts.cabinet', [
            'title'      => $this->isEdit ? 'Modifier Document' : 'Nouveau Document',
            'header'     => $this->isEdit ? 'Modifier le Document' : 'Nouveau Document',
            'headerIcon' => 'icon-folder',
            'breadcrumbs' => [
                ['label' => 'Documents', 'url' => route('documents.index')],
                ['label' => $this->isEdit ? 'Modifier' : 'Nouveau'],
            ],
        ]);
    }
}
