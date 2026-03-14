<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-file-text"></i> Rapports Médicaux</h5>
            <div class="card-header-right">
                <a href="{{ route('medical-reports.create') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouveau rapport
                </a>
            </div>
        </div>
        <div class="card-block">

            {{-- Search & Filter --}}
            <div class="row m-b-20">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par patient, titre, numéro...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="typeFilter" class="form-control">
                        <option value="">-- Tous les types --</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-right">
                    <span class="text-muted">{{ $reports->total() }} rapport(s)</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>N° Rapport</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Titre</th>
                            <th>Date</th>
                            <th>Médecin</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            @php
                                $typeColors = [
                                    'sick_leave'          => 'label-warning',
                                    'fitness_certificate' => 'label-success',
                                    'medical_certificate' => 'label-info',
                                    'referral'            => 'label-primary',
                                    'prescription'        => 'label-default',
                                    'surgery_report'      => 'label-danger',
                                    'hospitalization'     => 'label-danger',
                                    'other'               => 'label-default',
                                ];
                                $typeColor = $typeColors[$report->type] ?? 'label-default';
                                $typeLabel = $types[$report->type] ?? $report->type;
                            @endphp
                            <tr>
                                <td><strong>{{ $report->report_number ?? ('RPT-' . str_pad($report->id, 5, '0', STR_PAD_LEFT)) }}</strong></td>
                                <td>{{ $report->patient->full_name ?? '—' }}</td>
                                <td><span class="label {{ $typeColor }}">{{ $typeLabel }}</span></td>
                                <td>{{ $report->title }}</td>
                                <td>{{ $report->report_date ? \Carbon\Carbon::parse($report->report_date)->format('d/m/Y') : '—' }}</td>
                                <td>{{ $report->doctor->full_name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('medical-reports.show', $report) }}" class="btn btn-info btn-xs" title="Voir">
                                        <i class="feather icon-eye"></i>
                                    </a>
                                    <a href="{{ route('medical-reports.pdf', $report) }}" target="_blank" class="btn btn-default btn-xs" title="Télécharger PDF">
                                        <i class="feather icon-file"></i> PDF
                                    </a>
                                    <a href="{{ route('medical-reports.edit', $report) }}" class="btn btn-warning btn-xs" title="Modifier">
                                        <i class="feather icon-edit"></i>
                                    </a>
                                    <button type="button"
                                        wire:click="delete({{ $report->id }})"
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce rapport ?"
                                        class="btn btn-danger btn-xs" title="Supprimer">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-20">
                                    <i class="feather icon-file-text" style="font-size: 2em;"></i>
                                    <p>Aucun rapport médical trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $reports->links() }}
            </div>

        </div>
    </div>
</div>
