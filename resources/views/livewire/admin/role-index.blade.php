<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <i class="feather icon-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <i class="feather icon-alert-triangle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- ── Formulaire créer / modifier ─────────────────────────────── --}}
    @if($showForm)
    <div class="card" style="border-left: 4px solid #4099ff;">
        <div class="card-header">
            <h5>
                <i class="feather icon-shield"></i>
                {{ $editingId ? 'Modifier le rôle' : 'Nouveau rôle' }}
            </h5>
            <div class="card-header-right">
                <button wire:click="closeForm" class="btn btn-default btn-sm">
                    <i class="feather icon-x"></i> Fermer
                </button>
            </div>
        </div>
        <div class="card-block">
            <form wire:submit="save">
                {{-- Nom du rôle --}}
                <div class="row m-b-20">
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label>Nom du rôle <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" class="form-control"
                                   placeholder="ex: infirmier"
                                   {{ in_array($name, ['admin','medecin','secretaire']) ? 'readonly' : '' }}>
                            @error('name') <span class="help-block text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Permissions groupées --}}
                <h6 class="text-muted m-b-15"><i class="feather icon-lock"></i> Permissions</h6>
                <div class="row">
                    @foreach($groups as $groupName => $groupPerms)
                        @php
                            $existingInGroup = $allPermissions->whereIn('name', $groupPerms)->pluck('name');
                            $allChecked = $existingInGroup->count() > 0 &&
                                          $existingInGroup->every(fn($p) => in_array($p, $selectedPermissions));
                        @endphp
                        <div class="col-md-3 m-b-20">
                            <div class="card" style="border: 1px solid #e0e0e0;">
                                <div class="card-header" style="padding: 10px 15px; background: #f8f9fa;">
                                    <div class="checkbox-fade fade-in-primary m-0">
                                        <label style="font-weight: 600; font-size: 13px;">
                                            <input type="checkbox"
                                                   wire:click="toggleGroupPermissions('{{ $groupName }}')"
                                                   {{ $allChecked ? 'checked' : '' }}>
                                            <span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span>
                                            {{ $groupName }}
                                        </label>
                                    </div>
                                </div>
                                <div class="card-block" style="padding: 10px 15px;">
                                    @foreach($groupPerms as $perm)
                                        @if($allPermissions->contains('name', $perm))
                                        <div class="checkbox-fade fade-in-primary m-b-5">
                                            <label style="font-size: 12px; font-weight: normal;">
                                                <input type="checkbox"
                                                       wire:model="selectedPermissions"
                                                       value="{{ $perm }}">
                                                <span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span>
                                                {{ $perm }}
                                            </label>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row m-t-10">
                    <div class="col-md-12 text-right">
                        <button type="button" wire:click="closeForm" class="btn btn-default m-r-10">
                            <i class="feather icon-x"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin"></i></span>
                            <i class="feather icon-save"></i>
                            {{ $editingId ? 'Mettre à jour' : 'Créer le rôle' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Liste des rôles ──────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-shield"></i> Rôles & Permissions</h5>
            <div class="card-header-right">
                <button wire:click="openForm()" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouveau rôle
                </button>
            </div>
        </div>
        <div class="card-block">
            <div class="row">
                @foreach($roles as $role)
                @php
                    $roleColors = ['admin' => 'bg-c-pink', 'medecin' => 'bg-c-blue', 'secretaire' => 'bg-c-green'];
                    $roleColor  = $roleColors[$role->name] ?? 'bg-c-yellow';
                    $isSystem   = in_array($role->name, ['admin','medecin','secretaire']);
                @endphp
                <div class="col-md-4 m-b-20">
                    <div class="card" style="border-top: 3px solid {{ ['admin'=>'#ff5370','medecin'=>'#4099ff','secretaire'=>'#2ed8b6'][$role->name] ?? '#ffb64d' }};">
                        <div class="card-block">
                            {{-- En-tête rôle --}}
                            <div class="row align-items-center m-b-15">
                                <div class="col-auto">
                                    <div class="{{ $roleColor }} rounded" style="width:45px;height:45px;display:flex;align-items:center;justify-content:center;">
                                        <i class="feather icon-shield text-white" style="font-size:20px;"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <h5 class="m-0" style="text-transform: capitalize;">{{ $role->name }}</h5>
                                    <small class="text-muted">
                                        <i class="feather icon-users"></i>
                                        {{ $role->users_count }} utilisateur(s)
                                        @if($isSystem)
                                            · <span class="label label-success" style="font-size:9px;">Système</span>
                                        @endif
                                    </small>
                                </div>
                            </div>

                            {{-- Permissions --}}
                            <div style="min-height: 80px;">
                                @if($role->permissions->count())
                                    @foreach($role->permissions->take(8) as $perm)
                                        <span class="label label-primary m-b-3" style="font-size:10px; display:inline-block; margin:2px 2px 2px 0; color:#000;">
                                            {{ $perm->name }}
                                        </span>
                                    @endforeach
                                    @if($role->permissions->count() > 8)
                                        <span class="text-muted" style="font-size:11px;">
                                            +{{ $role->permissions->count() - 8 }} autres...
                                        </span>
                                    @endif
                                @else
                                    <p class="text-muted" style="font-size:12px;">Aucune permission assignée.</p>
                                @endif
                            </div>

                            {{-- Total permissions --}}
                            <div class="m-t-10 m-b-10">
                                <small class="text-muted">
                                    <i class="feather icon-lock"></i>
                                    {{ $role->permissions->count() }} permission(s) au total
                                </small>
                            </div>

                            {{-- Actions --}}
                            <div class="text-right" style="border-top: 1px solid #f0f0f0; padding-top: 10px; margin-top: 5px;">
                                <button wire:click="openForm({{ $role->id }})"
                                        class="btn btn-warning btn-sm">
                                    <i class="feather icon-edit"></i> Modifier
                                </button>
                                @if(!$isSystem)
                                    <button wire:click="confirmDelete({{ $role->id }})"
                                            class="btn btn-danger btn-sm m-l-5">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Tableau récapitulatif des permissions ────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-grid"></i> Matrice des permissions</h5>
        </div>
        <div class="card-block">
            <div class="table-responsive">
                <table class="table table-bordered" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Permission</th>
                            @foreach($roles as $role)
                                <th class="text-center" style="text-transform: capitalize;">{{ $role->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $groupName => $groupPerms)
                            <tr style="background: #f8f9fa;">
                                <td colspan="{{ $roles->count() + 1 }}" style="font-weight: 600; color: #555;">
                                    <i class="feather icon-folder" style="font-size:11px;"></i> {{ $groupName }}
                                </td>
                            </tr>
                            @foreach($groupPerms as $perm)
                                @if($allPermissions->contains('name', $perm))
                                <tr>
                                    <td class="text-muted">{{ $perm }}</td>
                                    @foreach($roles as $role)
                                        <td class="text-center">
                                            @if($role->permissions->contains('name', $perm))
                                                <i class="feather icon-check-circle text-success" style="font-size:16px;"></i>
                                            @else
                                                <i class="feather icon-minus text-muted" style="font-size:14px;"></i>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Modal suppression ────────────────────────────────────────── --}}
    @if($showDeleteModal)
    <div class="modal fade show" style="display:block; background:rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="feather icon-alert-triangle text-danger"></i> Supprimer le rôle
                    </h5>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer ce rôle ? Les utilisateurs associés perdront ce rôle.</p>
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('showDeleteModal', false)" class="btn btn-default">
                        <i class="feather icon-x"></i> Annuler
                    </button>
                    <button wire:click="delete" class="btn btn-danger">
                        <span wire:loading wire:target="delete"><i class="fa fa-spinner fa-spin"></i></span>
                        <i class="feather icon-trash-2"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
