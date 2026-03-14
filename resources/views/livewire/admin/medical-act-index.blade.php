<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-clipboard"></i> Actes Médicaux</h5>
            <div class="card-header-right">
                <button type="button" wire:click="addNew" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouvel acte
                </button>
            </div>
        </div>
        <div class="card-block">

            {{-- Search --}}
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un acte...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="categoryFilter" class="form-control">
                        <option value="">-- Toutes les catégories --</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <span class="text-muted">{{ $medicalActs->total() }} acte(s)</span>
                </div>
            </div>

            {{-- Table with Inline Editing --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Code</th>
                            <th>Nom</th>
                            <th style="width: 150px;">Catégorie</th>
                            <th style="width: 120px;">Prix (MAD)</th>
                            <th style="width: 110px;">Durée (min)</th>
                            <th style="width: 90px;">Statut</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($medicalActs as $act)
                            @if($editingId === $act->id)
                                {{-- Inline Edit Row --}}
                                <tr class="info">
                                    <td>
                                        <input type="text" wire:model="editForm.code" class="form-control input-sm" placeholder="Code">
                                        @error('editForm.code')<small class="text-danger">{{ $message }}</small>@enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model="editForm.name" class="form-control input-sm" placeholder="Nom de l'acte">
                                        @error('editForm.name')<small class="text-danger">{{ $message }}</small>@enderror
                                    </td>
                                    <td>
                                        <select wire:model="editForm.category" class="form-control input-sm">
                                            <option value="">--</option>
                                            @foreach($categories as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" wire:model="editForm.price" class="form-control input-sm" placeholder="0.00">
                                        @error('editForm.price')<small class="text-danger">{{ $message }}</small>@enderror
                                    </td>
                                    <td>
                                        <input type="number" wire:model="editForm.duration_minutes" class="form-control input-sm" placeholder="30">
                                    </td>
                                    <td>
                                        <select wire:model="editForm.is_active" class="form-control input-sm">
                                            <option value="1">Actif</option>
                                            <option value="0">Inactif</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" wire:click="saveInline" class="btn btn-success btn-xs" title="Sauvegarder">
                                            <i class="feather icon-check"></i>
                                        </button>
                                        <button type="button" wire:click="cancelEdit" class="btn btn-default btn-xs" title="Annuler">
                                            <i class="feather icon-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td><code>{{ $act->code ?? '—' }}</code></td>
                                    <td>{{ $act->name }}</td>
                                    <td>{{ $categories[$act->category] ?? $act->category ?? '—' }}</td>
                                    <td><strong>{{ number_format($act->price ?? 0, 2) }}</strong></td>
                                    <td>{{ $act->duration_minutes ?? '—' }} min</td>
                                    <td>
                                        @if($act->is_active ?? true)
                                            <span class="label label-success">Actif</span>
                                        @else
                                            <span class="label label-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" wire:click="edit({{ $act->id }})" class="btn btn-warning btn-xs" title="Modifier">
                                            <i class="feather icon-edit"></i>
                                        </button>
                                        <button type="button"
                                            wire:click="delete({{ $act->id }})"
                                            wire:confirm="Êtes-vous sûr de vouloir supprimer cet acte ?"
                                            class="btn btn-danger btn-xs" title="Supprimer">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-20">
                                    <i class="feather icon-clipboard" style="font-size: 2em;"></i>
                                    <p>Aucun acte médical trouvé.</p>
                                </td>
                            </tr>
                        @endforelse

                        {{-- New Row --}}
                        @if($creatingNew)
                            <tr class="success">
                                <td>
                                    <input type="text" wire:model="newForm.code" class="form-control input-sm" placeholder="Code">
                                    @error('newForm.code')<small class="text-danger">{{ $message }}</small>@enderror
                                </td>
                                <td>
                                    <input type="text" wire:model="newForm.name" class="form-control input-sm" placeholder="Nom de l'acte">
                                    @error('newForm.name')<small class="text-danger">{{ $message }}</small>@enderror
                                </td>
                                <td>
                                    <select wire:model="newForm.category" class="form-control input-sm">
                                        <option value="">--</option>
                                        @foreach($categories as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" wire:model="newForm.price" class="form-control input-sm" placeholder="0.00">
                                    @error('newForm.price')<small class="text-danger">{{ $message }}</small>@enderror
                                </td>
                                <td>
                                    <input type="number" wire:model="newForm.duration_minutes" class="form-control input-sm" placeholder="30">
                                </td>
                                <td>
                                    <select wire:model="newForm.is_active" class="form-control input-sm">
                                        <option value="1">Actif</option>
                                        <option value="0">Inactif</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" wire:click="saveNew" class="btn btn-success btn-xs" title="Créer">
                                        <i class="feather icon-check"></i>
                                    </button>
                                    <button type="button" wire:click="$set('creatingNew', false)" class="btn btn-default btn-xs" title="Annuler">
                                        <i class="feather icon-x"></i>
                                    </button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $medicalActs->links() }}
            </div>

        </div>
    </div>
</div>
