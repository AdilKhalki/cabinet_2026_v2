<?php

namespace App\Livewire\MedicalReport;

use App\Models\MedicalReport;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MedicalReportIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    public int $perPage = 15;
    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }

    #[Computed]
    public function reports()
    {
        return MedicalReport::with(['patient', 'doctor'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('report_number', 'like', "%{$this->search}%")
                  ->orWhereHas('patient', fn($pq) => $pq
                      ->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->when(auth()->user()->isMedecin(), fn($q) => $q->where('doctor_id', auth()->id()))
            ->orderByDesc('report_date')
            ->paginate($this->perPage);
    }

    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }
    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    public function delete(?int $id = null): void
    {
        // La vue appelle delete(id) directement (wire:confirm) : accepter l'id en paramètre
        if ($id) {
            $this->confirmingDeleteId = $id;
        }

        if ($this->confirmingDeleteId) {
            $report = MedicalReport::findOrFail($this->confirmingDeleteId);
            $this->authorize('delete', $report);
            $report->delete();
            session()->flash('success', 'Rapport supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->reports);
        }
    }

    public function render()
    {
        return view('livewire.medical-report.medical-report-index', [
            'reports' => $this->reports,
            'types'   => MedicalReport::TYPE_LABELS,
        ])->layout('layouts.cabinet', [
            'title'      => 'Rapports Médicaux',
            'header'     => 'Rapports Médicaux',
            'headerIcon' => 'icon-clipboard',
            'breadcrumbs' => [['label' => 'Rapports Médicaux']],
        ]);
    }
}
