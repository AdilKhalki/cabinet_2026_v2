<?php

namespace App\Livewire\Patient;

use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PatientShow extends Component
{
    public Patient $patient;
    public string $activeTab = 'overview';

    /**
     * Onglets du dossier et permission requise pour chacun.
     * Le contrôle se fait côté serveur : un appel direct à setTab() ne
     * permet pas d'afficher un onglet non autorisé.
     */
    public const TABS = [
        'overview'      => ['icon' => 'icon-eye',         'label' => "Vue d'ensemble",  'permission' => null],
        'consultations' => ['icon' => 'icon-activity',    'label' => 'Consultations',   'permission' => 'view consultations'],
        'prescriptions' => ['icon' => 'icon-file-text',   'label' => 'Ordonnances',     'permission' => 'view prescriptions'],
        'lab-tests'     => ['icon' => 'icon-thermometer', 'label' => 'Analyses',        'permission' => 'view lab-tests'],
        'images'        => ['icon' => 'icon-image',       'label' => 'Imagerie',        'permission' => 'view medical-images'],
        'reports'       => ['icon' => 'icon-clipboard',   'label' => 'Rapports',        'permission' => 'view medical-reports'],
        'history'       => ['icon' => 'icon-clock',       'label' => 'Antécédents',     'permission' => 'view consultations'],
        'documents'     => ['icon' => 'icon-folder',      'label' => 'Documents',       'permission' => 'view documents'],
        'acts'          => ['icon' => 'icon-list',        'label' => 'Actes Médicaux',  'permission' => 'view payments'],
        'payments'      => ['icon' => 'icon-credit-card', 'label' => 'Paiements',       'permission' => 'view payments'],
    ];

    /** Relation comptée affichée dans le libellé de l'onglet */
    private const TAB_COUNTS = [
        'consultations' => 'consultations',
        'prescriptions' => 'prescriptions',
        'lab-tests'     => 'labTests',
        'images'        => 'medicalImages',
        'reports'       => 'medicalReports',
        'history'       => 'medicalHistories',
        'documents'     => 'documents',
        'payments'      => 'payments',
    ];

    // ── Formulaire d'antécédent ────────────────────────────────────
    public bool $showHistoryForm = false;
    public string $history_type = 'medical';
    public string $history_title = '';
    public string $history_description = '';
    public string $history_date_occurred = '';
    public string $history_relation = '';
    public string $history_habit_type = '';
    public string $history_habit_frequency = '';
    public string $history_vaccine_name = '';
    public string $history_vaccine_date = '';
    public string $history_vaccine_next_date = '';
    public string $history_vaccine_batch = '';

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient;
    }

    public function canSeeTab(string $tab): bool
    {
        if (! array_key_exists($tab, self::TABS)) {
            return false;
        }

        $permission = self::TABS[$tab]['permission'];

        return $permission === null || auth()->user()->can($permission);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $this->canSeeTab($tab) ? $tab : 'overview';
    }

    // ── Antécédents médicaux ──────────────────────────────────────
    protected function canManageHistory(): bool
    {
        return auth()->user()->can('create consultations');
    }

    public function openHistoryForm(): void
    {
        abort_unless($this->canManageHistory(), 403);
        $this->resetHistoryForm();
        $this->showHistoryForm = true;
    }

    public function cancelHistoryForm(): void
    {
        $this->resetHistoryForm();
        $this->showHistoryForm = false;
    }

    protected function resetHistoryForm(): void
    {
        $this->reset([
            'history_type', 'history_title', 'history_description', 'history_date_occurred',
            'history_relation', 'history_habit_type', 'history_habit_frequency',
            'history_vaccine_name', 'history_vaccine_date', 'history_vaccine_next_date', 'history_vaccine_batch',
        ]);
        $this->resetValidation();
    }

    public function saveHistory(): void
    {
        abort_unless($this->canManageHistory(), 403);

        $validated = $this->validate([
            'history_type'              => ['required', Rule::in(array_keys(MedicalHistory::TYPE_LABELS))],
            'history_title'             => 'required|string|max:200',
            'history_description'       => 'nullable|string|max:2000',
            'history_date_occurred'     => 'nullable|date|before_or_equal:today',
            'history_relation'          => 'nullable|string|max:50',
            'history_habit_type'        => ['nullable', Rule::in(array_keys(MedicalHistory::HABIT_LABELS))],
            'history_habit_frequency'   => 'nullable|string|max:100',
            'history_vaccine_name'      => 'nullable|string|max:150',
            'history_vaccine_date'      => 'nullable|date',
            'history_vaccine_next_date' => 'nullable|date|after_or_equal:history_vaccine_date',
            'history_vaccine_batch'     => 'nullable|string|max:50',
        ], [], [
            'history_type'              => 'type',
            'history_title'             => 'intitulé',
            'history_date_occurred'     => 'date',
            'history_vaccine_next_date' => 'date de rappel',
            'history_vaccine_date'      => 'date de vaccination',
        ]);

        $type = $validated['history_type'];
        $data = [
            'type'          => $type,
            'title'         => $validated['history_title'],
            'description'   => $validated['history_description'] ?: null,
            'date_occurred' => $validated['history_date_occurred'] ?: null,
            'created_by'    => auth()->id(),
        ];

        // Ne conserver que les champs spécifiques au type choisi
        if ($type === 'family') {
            $data['relation'] = $validated['history_relation'] ?: null;
        }
        if ($type === 'habit') {
            $data['habit_type'] = $validated['history_habit_type'] ?: null;
            $data['habit_frequency'] = $validated['history_habit_frequency'] ?: null;
        }
        if ($type === 'vaccination') {
            $data['vaccine_name'] = $validated['history_vaccine_name'] ?: null;
            $data['vaccine_date'] = $validated['history_vaccine_date'] ?: null;
            $data['vaccine_next_date'] = $validated['history_vaccine_next_date'] ?: null;
            $data['vaccine_batch'] = $validated['history_vaccine_batch'] ?: null;
        }

        $this->patient->medicalHistories()->create($data);

        $this->cancelHistoryForm();
        session()->flash('success', 'Antécédent ajouté au dossier.');
    }

    public function deleteHistory(int $id): void
    {
        abort_unless(auth()->user()->can('edit consultations'), 403);

        $this->patient->medicalHistories()->findOrFail($id)->delete();
        session()->flash('success', 'Antécédent supprimé.');
    }

    /** Téléchargement contrôlé (le fichier est stocké sur un disque privé). */
    public function downloadDocument(int $id)
    {
        $document = $this->patient->documents()->findOrFail($id);
        $this->authorize('view', $document);

        abort_unless($document->file_path && Storage::disk($document->storageDisk())->exists($document->file_path), 404);

        return Storage::disk($document->storageDisk())->download($document->file_path, $document->file_name);
    }

    /** Charge uniquement les données de l'onglet actif (évite de tout charger à chaque requête). */
    protected function loadActiveTab(): void
    {
        $patient = $this->patient;

        $counts = collect(self::TAB_COUNTS)
            ->filter(fn ($relation, $tab) => $this->canSeeTab($tab))
            ->values()
            ->all();
        $patient->loadCount($counts);
        $patient->loadCount([
            'appointments',
            'payments as unpaid_payments_count' => fn ($q) => $q->unpaid(),
        ]);

        match ($this->activeTab) {
            'overview'      => $patient->setRelation(
                'consultations',
                $this->canSeeTab('consultations')
                    ? $patient->consultations()->latest('consultation_date')->limit(1)->get()
                    : collect()
            ),
            'consultations' => $patient->load(['consultations' => fn ($q) => $q->with('doctor')->latest('consultation_date')]),
            'prescriptions' => $patient->load(['prescriptions' => fn ($q) => $q->with(['lines', 'doctor'])->latest('prescribed_at')]),
            'lab-tests'     => $patient->load(['labTests' => fn ($q) => $q->withCount('results')->latest('requested_at')]),
            'images'        => $patient->load(['medicalImages' => fn ($q) => $q->with('doctor')->latest('image_date')]),
            'reports'       => $patient->load(['medicalReports' => fn ($q) => $q->with('doctor')->latest('report_date')]),
            'history'       => $patient->load(['medicalHistories' => fn ($q) => $q->latest('date_occurred')->latest()]),
            'documents'     => $patient->load(['documents' => fn ($q) => $q->with('uploadedBy')->latest('document_date')]),
            'acts'          => $patient->load(['payments' => fn ($q) => $q->with('lines.medicalAct')->latest('payment_date')]),
            'payments'      => $patient->load(['payments' => fn ($q) => $q->latest('payment_date')]),
            default         => null,
        };
    }

    /**
     * Ne pas sérialiser les relations chargées pour l'onglet : sinon Livewire
     * les recharge intégralement (sans contraintes) à chaque requête.
     */
    public function dehydrate(): void
    {
        $this->patient->unsetRelations();
    }

    public function render()
    {
        if (! $this->canSeeTab($this->activeTab)) {
            $this->activeTab = 'overview';
        }

        $this->loadActiveTab();

        $tabs = collect(self::TABS)
            ->filter(fn ($info, $tab) => $this->canSeeTab($tab))
            ->map(function ($info, $tab) {
                $relation = self::TAB_COUNTS[$tab] ?? null;
                if ($relation && $tab !== 'history') {
                    $info['label'] .= ' ('.($this->patient->{\Illuminate\Support\Str::snake($relation).'_count'} ?? 0).')';
                }

                return $info;
            });

        return view('livewire.patient.patient-show', [
            'tabs'             => $tabs,
            'canManageHistory' => $this->canManageHistory(),
        ])
            ->layout('layouts.cabinet', [
                'title' => "Dossier — {$this->patient->full_name}",
                'header' => "Dossier Patient : {$this->patient->full_name}",
                'headerIcon' => 'icon-user',
                'subHeader' => "Dossier N° {$this->patient->dossier_number}",
                'breadcrumbs' => [
                    ['label' => 'Patients', 'url' => route('patients.index')],
                    ['label' => $this->patient->full_name],
                ],
            ]);
    }
}
