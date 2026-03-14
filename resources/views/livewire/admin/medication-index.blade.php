<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-package"></i> Médicaments</h5>
            <div class="card-header-right">
                <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouveau médicament
                </button>
            </div>
        </div>
        <div class="card-block">

            {{-- Search Bar --}}
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par nom, DCI, forme...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-control">
                        <option value="">-- Tous les statuts --</option>
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
                <div class="col-md-3 text-right">
                    <span class="text-muted">{{ $medications->total() }} médicament(s)</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nom commercial</th>
                            <th>DCI (générique)</th>
                            <th>Forme</th>
                            <th>Dosage</th>
                            <th style="width: 90px;">Statut</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($medications as $medication)
                            <tr>
                                <td><strong>{{ $medication->name }}</strong></td>
                                <td>{{ $medication->generic_name ?? '—' }}</td>
                                <td>{{ $medication->form ?? '—' }}</td>
                                <td>{{ $medication->dosage ?? '—' }}</td>
                                <td>
                                    @if($medication->is_active ?? true)
                                        <span class="label label-success">Actif</span>
                                    @else
                                        <span class="label label-danger">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" wire:click="openEditModal({{ $medication->id }})" class="btn btn-warning btn-xs" title="Modifier">
                                        <i class="feather icon-edit"></i>
                                    </button>
                                    <button type="button"
                                        wire:click="delete({{ $medication->id }})"
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce médicament ?"
                                        class="btn btn-danger btn-xs" title="Supprimer">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-20">
                                    <i class="feather icon-package" style="font-size: 2em;"></i>
                                    <p>Aucun médicament trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $medications->links() }}
            </div>

        </div>
    </div>

    {{-- Edit/Create Modal --}}
    @if($showModal)
        <div class="modal fade in" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="feather icon-package"></i>
                            {{ $editingMedicationId ? 'Modifier le médicament' : 'Nouveau médicament' }}
                        </h4>
                        <button type="button" class="close" wire:click="closeModal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group {{ $errors->has('medForm.name') ? 'has-error' : '' }}">
                            <label>Nom commercial <span class="text-danger">*</span></label>
                            <input type="text" wire:model="medForm.name" class="form-control" placeholder="Ex: Doliprane">
                            @error('medForm.name')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group {{ $errors->has('medForm.generic_name') ? 'has-error' : '' }}">
                            <label>DCI (Dénomination Commune Internationale)</label>
                            <input type="text" wire:model="medForm.generic_name" class="form-control" placeholder="Ex: Paracétamol">
                            @error('medForm.generic_name')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('medForm.form') ? 'has-error' : '' }}">
                                    <label>Forme galénique</label>
                                    <select wire:model="medForm.form" class="form-control">
                                        <option value="">-- Forme --</option>
                                        <option value="Comprimé">Comprimé</option>
                                        <option value="Gélule">Gélule</option>
                                        <option value="Sirop">Sirop</option>
                                        <option value="Injectable">Injectable</option>
                                        <option value="Pommade">Pommade</option>
                                        <option value="Suppositoire">Suppositoire</option>
                                        <option value="Patch">Patch</option>
                                        <option value="Collyre">Collyre</option>
                                        <option value="Sachet">Sachet</option>
                                        <option value="Spray">Spray</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                    @error('medForm.form')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('medForm.dosage') ? 'has-error' : '' }}">
                                    <label>Dosage</label>
                                    <input type="text" wire:model="medForm.dosage" class="form-control" placeholder="Ex: 500mg, 250mg/5ml">
                                    @error('medForm.dosage')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" wire:model="medForm.is_active"> Médicament actif
                            </label>
                        </div>
                        <div class="form-group {{ $errors->has('medForm.notes') ? 'has-error' : '' }}">
                            <label>Notes / Remarques</label>
                            <textarea wire:model="medForm.notes" class="form-control" rows="2" placeholder="Informations complémentaires..."></textarea>
                            @error('medForm.notes')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" wire:click="closeModal">
                            <i class="feather icon-x"></i> Annuler
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveMedication" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveMedication">
                                <i class="feather icon-save"></i> Enregistrer
                            </span>
                            <span wire:loading wire:target="saveMedication">
                                <i class="feather icon-loader"></i> Enregistrement...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
