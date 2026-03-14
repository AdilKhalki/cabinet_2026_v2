<div>
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-activity"></i> Consultations</h5>
            <div class="card-header-right">
                <a href="{{ route('consultations.create') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouvelle consultation
                </a>
            </div>
        </div>
        <div class="card-block">
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="in_progress">En cours</option>
                        <option value="completed">Terminées</option>
                        <option value="archived">Archivées</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr><th>Patient</th><th>Médecin</th><th>Date</th><th>Type</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($this->consultations as $consult)
                            <tr>
                                <td>
                                    <a href="{{ route('patients.show', $consult->patient) }}"><strong>{{ $consult->patient->full_name }}</strong></a>
                                    <small class="text-muted d-block">{{ $consult->patient->dossier_number }}</small>
                                </td>
                                <td>{{ $consult->doctor->name }}</td>
                                <td>{{ $consult->consultation_date->format('d/m/Y H:i') }}</td>
                                <td><span class="label label-info">{{ $consult->consultation_type }}</span></td>
                                <td>{{ Str::limit($consult->reason, 50) ?? '&mdash;' }}</td>
                                <td>
                                    <span class="label label-{{ $consult->status === 'completed' ? 'success' : 'warning' }}">{{ $consult->status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('consultations.show', $consult) }}" class="btn btn-info btn-xs"><i class="feather icon-eye"></i></a>
                                    <a href="{{ route('consultations.edit', $consult) }}" class="btn btn-primary btn-xs"><i class="feather icon-edit"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted p-20">Aucune consultation trouvée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="m-t-15">{{ $this->consultations->links() }}</div>
        </div>
    </div>
</div>
