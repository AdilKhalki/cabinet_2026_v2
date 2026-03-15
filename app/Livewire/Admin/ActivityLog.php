<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $subjectFilter = '';

    #[Url]
    public string $causerFilter = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSubjectFilter(): void { $this->resetPage(); }
    public function updatingCauserFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    #[Computed]
    public function logs()
    {
        return \Spatie\Activitylog\Models\Activity::with(['causer', 'subject'])
            ->when($this->search, fn($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->when($this->subjectFilter, fn($q) => $q->where('subject_type', 'like', "%{$this->subjectFilter}%"))
            ->when($this->causerFilter, fn($q) => $q->where('causer_id', $this->causerFilter)->where('causer_type', 'App\Models\User'))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function causers()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->subjectFilter = '';
        $this->causerFilter = '';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.activity-log', [
            'logs'             => $this->logs,
            'causers'          => $this->causers,
            'users'            => $this->causers,
            'availableActions' => [
                ''         => 'Toutes les actions',
                'created'  => 'Créé',
                'updated'  => 'Modifié',
                'deleted'  => 'Supprimé',
                'restored' => 'Restauré',
            ],
            'actionColors'     => [
                'created'  => 'label-success',
                'updated'  => 'label-warning',
                'deleted'  => 'label-danger',
                'restored' => 'label-info',
            ],
        ])->layout('layouts.cabinet', [
            'title'      => 'Journal d\'Audit',
            'header'     => 'Journal d\'Audit',
            'headerIcon' => 'icon-eye',
            'breadcrumbs' => [['label' => 'Journal d\'Audit']],
        ]);
    }
}
