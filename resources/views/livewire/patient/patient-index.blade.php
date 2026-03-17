<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <i class="feather icon-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Card principal --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-users"></i> Liste des Patients</h5>
            <div class="card-header-right">
                @can('create', App\Models\Patient::class)
                    <a href="{{ route('patients.create') }}" class="btn btn-primary btn-sm">
                        <i class="feather icon-user-plus"></i> Nouveau Patient
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-block">

            {{-- Filtres --}}
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                               class="form-control"
                               placeholder="Rechercher par nom, téléphone, dossier, CIN...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="genderFilter" class="form-control">
                        <option value="">Tous les sexes</option>
                        <option value="male">Homme</option>
                        <option value="female">Femme</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="perPage" class="form-control">
                        <option value="15">15 par page</option>
                        <option value="25">25 par page</option>
                        <option value="50">50 par page</option>
                    </select>
                </div>
            </div>

            {{-- Tableau --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a wire:click="sort('dossier_number')" href="#" class="text-dark">
                                    Dossier
                                    @if($sortBy === 'dossier_number')
                                        <i class="feather icon-chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a wire:click="sort('last_name')" href="#" class="text-dark">
                                    Patient
                                    @if($sortBy === 'last_name')
                                        <i class="feather icon-chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Sexe</th>
                            <th>Âge</th>
                            <th>Téléphone</th>
                            <th>Groupe sanguin</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->patients as $patient)
                            <tr>
                                <td>
                                    <span class="label label-primary">{{ $patient->dossier_number }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($patient->photo_path)
                                            <img src="{{ $patient->photo_url }}"
                                                 class="img-radius m-r-10"
                                                 style="width:35px;height:35px;object-fit:cover;"
                                                 alt="{{ $patient->full_name }}">
                                        @else
                                            <div class="img-radius m-r-10"
                                                 style="width:35px;height:35px;background:{{ $patient->gender === 'male' ? '#01a9ac' : '#ff5370' }};display:inline-flex;align-items:center;justify-content:center;border-radius:50%;color:#fff;font-weight:600;font-size:14px;">
                                                {{ mb_substr($patient->first_name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <strong>{{ $patient->full_name }}</strong>
                                            @if($patient->cin)
                                                <small class="text-muted d-block">CIN: {{ $patient->cin }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($patient->gender === 'male')
                                        <span class="label label-info">Homme</span>
                                    @else
                                        <span class="label label-warning">Femme</span>
                                    @endif
                                </td>
                                <td>{{ $patient->age }} ans</td>
                                <td>
                                    <a href="tel:{{ $patient->phone }}">{{ $patient->phone }}</a>
                                </td>
                                <td>
                                    @if($patient->blood_group)
                                        <span class="label label-danger">{{ $patient->blood_group }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($patient->is_active)
                                        <span class="label label-success">Actif</span>
                                    @else
                                        <span class="label label-default">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('patients.show', $patient) }}"
                                           class="btn btn-info btn-sm" title="Voir dossier">
                                            <i class="feather icon-eye"></i>
                                        </a>
                                        @can('update', $patient)
                                            <a href="{{ route('patients.edit', $patient) }}"
                                               class="btn btn-primary btn-sm" title="Modifier">
                                                <i class="feather icon-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $patient)
                                            <button wire:click="confirmDelete({{ $patient->id }})"
                                                    class="btn btn-danger btn-sm" title="Supprimer">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted p-20">
                                    <i class="feather icon-users" style="font-size:2em;"></i>
                                    <p>Aucun patient trouvé.</p>
                                    @can('create', App\Models\Patient::class)
                                        <a href="{{ route('patients.create') }}" class="btn btn-primary btn-sm">
                                            Ajouter le premier patient
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center m-t-15">
                <p class="text-muted">
                    {{ $this->patients->total() }} patient(s) au total
                </p>
                <div class="d-flex justify-content-center">

                    {{ $this->patients->links('pagination::bootstrap-5') }}
                </div>
            </div>

        </div>
    </div>

    {{-- Modal Confirmation suppression --}}
    @if($showDeleteModal)
        <div class="modal fade in" style="display:block; background:rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmer la suppression</h4>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer ce patient ? Cette action est réversible (soft delete).</p>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="cancelDelete" class="btn btn-default btn-sm">
                            <i class="feather icon-x"></i> Annuler
                        </button>
                        <button wire:click="delete" class="btn btn-danger btn-sm">
                            <i class="feather icon-trash-2"></i> Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading indicator --}}
    <div wire:loading.flex class="alert alert-info" style="position:fixed;bottom:20px;right:20px;z-index:9999;">
        <i class="feather icon-loader" style="animation:spin 1s linear infinite;"></i>
        &nbsp; Chargement...
    </div>
</div>
