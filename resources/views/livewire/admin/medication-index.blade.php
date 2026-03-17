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
                <button type="button" wire:click="openCreate" class="btn btn-primary btn-sm">
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
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par nom, DCI...">
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
                                <td>{{ $medication->dosage_form ?? '—' }}</td>
                                <td>{{ $medication->strength ?? '—' }}</td>
                                <td>
                                    @if($medication->is_active)
                                        <span class="label label-success">Actif</span>
                                    @else
                                        <span class="label label-danger">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" wire:click="openEdit({{ $medication->id }})" class="btn btn-warning btn-xs" title="Modifier">
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
                {{ $medications->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

    {{-- Modal Créer/Modifier --}}
    @if($showModal)
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:6px;width:550px;max-width:95%;max-height:90vh;overflow-y:auto;">
                <div style="padding:15px 20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                    <h5 style="margin:0;">
                        <i class="feather icon-package"></i>
                        {{ $editingId ? 'Modifier le médicament' : 'Nouveau médicament' }}
                    </h5>
                    <button type="button" wire:click="closeModal" style="border:none;background:none;font-size:20px;cursor:pointer;">×</button>
                </div>
                <div style="padding:20px;">
                    <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                        <label>Nom commercial <span class="text-danger">*</span></label>
                        <input type="text" wire:model="name" class="form-control" placeholder="Ex: Doliprane">
                        @error('name')<span class="help-block text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group {{ $errors->has('generic_name') ? 'has-error' : '' }}">
                        <label>DCI (Dénomination Commune Internationale)</label>
                        <input type="text" wire:model="generic_name" class="form-control" placeholder="Ex: Paracétamol">
                        @error('generic_name')<span class="help-block text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Forme galénique</label>
                                <select wire:model="dosage_form" class="form-control">
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
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Dosage</label>
                                <input type="text" wire:model="strength" class="form-control" placeholder="Ex: 500mg, 250mg/5ml">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" wire:model="is_active"> Médicament actif
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Notes / Remarques</label>
                        <textarea wire:model="description" class="form-control" rows="2" placeholder="Informations complémentaires..."></textarea>
                    </div>
                </div>
                <div style="padding:15px 20px;border-top:1px solid #eee;text-align:right;">
                    <button type="button" class="btn btn-default" wire:click="closeModal">
                        <i class="feather icon-x"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="save">
                        <i class="feather icon-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
