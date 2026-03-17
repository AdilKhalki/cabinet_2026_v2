<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-thermometer"></i> Analyses & Résultats de Laboratoire</h5>
            <div class="card-header-right">
                <a href="{{ route('lab-tests.create') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouvelle demande
                </a>
            </div>
        </div>
        <div class="card-block">
            <div class="row m-b-20">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un patient...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="requested">Demandé</option>
                        <option value="pending">En attente</option>
                        <option value="completed">Complété</option>
                        <option value="cancelled">Annulé</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>N° Demande</th><th>Patient</th><th>Médecin</th>
                            <th>Date demande</th><th>Résultats</th><th>Statut</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->labTests as $test)
                            @php
                                $abnormal = $test->results->where('flag', '!=', 'normal')->count();
                                $critical = $test->results->where('flag', 'critical')->count();
                            @endphp
                            <tr>
                                <td><span class="label label-primary">{{ $test->request_number }}</span></td>
                                <td>
                                    <a href="{{ route('patients.show', $test->patient) }}">
                                        <strong>{{ $test->patient->full_name }}</strong>
                                    </a>
                                </td>
                                <td>{{ $test->doctor->name }}</td>
                                <td>{{ $test->requested_at->format('d/m/Y') }}</td>
                                <td>
                                    @if($test->results->count() > 0)
                                        <span class="label label-info" style="color: #000000;">{{ $test->results->count() }} param.</span>
                                        @if($critical > 0)
                                            <span class="label label-danger">{{ $critical }} critique(s)</span>
                                        @elseif($abnormal > 0)
                                            <span class="label label-warning">{{ $abnormal }} anormal(aux)</span>
                                        @else
                                            <span class="label label-success">Normaux</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php $colors = ['requested'=>'primary','pending'=>'warning','completed'=>'success','cancelled'=>'danger']; @endphp
                                    <span class="label label-{{ $colors[$test->status] ?? 'default' }}">
                                        {{ ['requested'=>'Demandé','pending'=>'En attente','completed'=>'Complété','cancelled'=>'Annulé'][$test->status] ?? $test->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('lab-tests.show', $test) }}" class="btn btn-info btn-sm" title="Voir">
                                        <i class="feather icon-eye"></i>
                                    </a>
                                    <a href="{{ route('lab-tests.pdf', $test) }}" class="btn btn-warning btn-sm" title="Imprimer demande" target="_blank">
                                        <i class="feather icon-printer"></i>
                                    </a>
                                    <a href="{{ route('lab-tests.create') }}?edit={{ $test->id }}" class="btn btn-primary btn-sm" title="Modifier">
                                        <i class="feather icon-edit"></i>
                                    </a>
                                    @if($test->status === 'requested')
                                        <button wire:click="updateStatus({{ $test->id }}, 'completed')"
                                                class="btn btn-success btn-sm" title="Marquer complété">
                                            <i class="feather icon-check"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted p-20">Aucune analyse trouvée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="m-t-15">{{ $this->labTests->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>
