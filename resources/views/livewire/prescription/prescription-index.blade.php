<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5>Ordonnances</h5>
            <div class="card-header-right">
                <a href="{{ route('prescriptions.create') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouvelle Ordonnance
                </a>
            </div>
        </div>
        <div class="card-block">
            {{-- Recherche --}}
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" class="form-control" placeholder="Rechercher par patient..."
                               wire:model.live.debounce.300ms="search">
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>N° Ordonnance</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Date</th>
                            <th>Médicaments</th>
                            <th>Imprimée</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->prescriptions as $p)
                            <tr>
                                <td>{{ $loop->iteration + ($this->prescriptions->currentPage() - 1) * $this->prescriptions->perPage() }}</td>
                                <td>
                                    <a href="{{ route('prescriptions.show', $p->id) }}">
                                        <strong>{{ $p->prescription_number }}</strong>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('patients.show', $p->patient_id) }}">
                                        {{ $p->patient->full_name }}
                                    </a>
                                    <br><small class="text-muted">{{ $p->patient->dossier_number }}</small>
                                </td>
                                <td>Dr. {{ $p->doctor->name ?? '-' }}</td>
                                <td>{{ $p->prescribed_at ? \Carbon\Carbon::parse($p->prescribed_at)->format('d/m/Y') : $p->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <span class="label label-info">{{ $p->lines->count() }} méd.</span>
                                </td>
                                <td class="text-center">
                                    @if($p->is_printed)
                                        <span class="label label-success"><i class="feather icon-check"></i> Oui</span>
                                    @else
                                        <span class="label label-default">Non</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('prescriptions.show', $p->id) }}"
                                       class="btn btn-info btn-xs" title="Voir">
                                        <i class="feather icon-eye"></i>
                                    </a>
                                    <a href="{{ route('prescriptions.pdf', $p->id) }}"
                                       target="_blank"
                                       class="btn btn-primary btn-xs" title="PDF">
                                        <i class="feather icon-printer"></i>
                                    </a>
                                    <button wire:click="confirmDelete({{ $p->id }})"
                                            class="btn btn-danger btn-xs" title="Supprimer">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted p-20">
                                    <i class="feather icon-file-text" style="font-size:2em;"></i><br>
                                    Aucune ordonnance trouvée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="m-t-10">
                {{ $this->prescriptions->links() }}
            </div>
        </div>
    </div>

    {{-- Modal confirmation suppression --}}
    @if($confirmingDeleteId)
    <div class="modal fade in" style="display:block;background:rgba(0,0,0,0.5);" wire:ignore.self>
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmer la suppression</h4>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cette ordonnance ?</p>
                </div>
                <div class="modal-footer">
                    <button wire:click="cancelDelete" class="btn btn-default">Annuler</button>
                    <button wire:click="delete" class="btn btn-danger">
                        <i class="feather icon-trash-2"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
