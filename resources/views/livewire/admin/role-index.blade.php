<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="feather icon-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
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
                    <div class="col-md-8">
                        <label>&nbsp;</label>
                        <div>
                            <span class="label label-info" style="font-size: 12px;">
                                <i class="feather icon-check-circle"></i>
                                {{ count($selectedPermissions) }} permission(s) sélectionnée(s)
                                sur {{ $allPermissions->count() }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Permissions groupées --}}
                <h6 class="text-muted m-b-15"><i class="feather icon-lock"></i> Permissions</h6>
                <div class="row">
                    @foreach($groups as $groupName => $groupPerms)
                        @php
                            $existingInGroup = $allPermissions->whereIn('name', $groupPerms)->pluck('name')->toArray();
                            $allChecked = count($existingInGroup) > 0 &&
                                          count(array_intersect($existingInGroup, $selectedPermissions)) === count($existingInGroup);
                        @endphp
                        <div class="col-md-3 m-b-20">
                            <div class="card" style="border: 1px solid #e0e0e0; margin-bottom: 0;">
                                <div style="padding: 10px 15px; background: {{ $allChecked ? '#e8f5e9' : '#f8f9fa' }}; border-bottom: 1px solid #e0e0e0; cursor: pointer;"
                                     wire:click="toggleGroupPermissions('{{ $groupName }}')">
                                    <label style="font-weight: 600; font-size: 13px; margin: 0; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        @if($allChecked)
                                            <span style="color: #2ed8b6; font-size: 16px;"><i class="feather icon-check-square"></i></span>
                                        @else
                                            <span style="color: #999; font-size: 16px;"><i class="feather icon-square"></i></span>
                                        @endif
                                        {{ $groupName }}
                                        <span class="label {{ $allChecked ? 'label-success' : 'label-default' }}" style="font-size: 9px; margin-left: auto;">
                                            {{ count(array_intersect($existingInGroup, $selectedPermissions)) }}/{{ count($existingInGroup) }}
                                        </span>
                                    </label>
                                </div>
                                <div style="padding: 10px 15px;">
                                    @foreach($groupPerms as $perm)
                                        @if($allPermissions->contains('name', $perm))
                                        @php $isChecked = in_array($perm, $selectedPermissions); @endphp
                                        <div style="padding: 4px 0;">
                                            <label style="font-size: 12px; font-weight: normal; margin: 0; cursor: pointer; display: flex; align-items: center; gap: 6px; width: 100%;">
                                                <input type="checkbox"
                                                       wire:model.live="selectedPermissions"
                                                       value="{{ $perm }}"
                                                       style="width: 16px; height: 16px; accent-color: #01a9ac; cursor: pointer;">
                                                <span style="{{ $isChecked ? 'color: #333; font-weight: 500;' : 'color: #888;' }}">
                                                    {{ $perm }}
                                                </span>
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
                    $borderColor = ['admin'=>'#ff5370','medecin'=>'#4099ff','secretaire'=>'#2ed8b6'][$role->name] ?? '#ffb64d';
                @endphp
                <div class="col-md-4 m-b-20">
                    <div class="card" style="border-top: 3px solid {{ $borderColor }};">
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

                            {{-- Permissions groupées --}}
                            <div style="min-height: 80px;">
                                @foreach($groups as $groupName => $groupPerms)
                                    @php
                                        $roleGroupPerms = $role->permissions->whereIn('name', $groupPerms);
                                    @endphp
                                    @if($roleGroupPerms->isNotEmpty())
                                        <div class="m-b-5">
                                            <small style="font-weight: 600; color: #555;">{{ $groupName }}:</small><br>
                                            @foreach($roleGroupPerms as $perm)
                                                <span class="label label-primary m-b-2" style="font-size:10px; display:inline-block; margin:1px;">
                                                    {{ str_replace(['view ','create ','edit ','delete ','print ','upload ','generate ','manage '], '', $perm->name) }}
                                                    <small>({{ explode(' ', $perm->name)[0] }})</small>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                                @if($role->permissions->isEmpty())
                                    <p class="text-muted" style="font-size:12px;">Aucune permission assignée.</p>
                                @endif
                            </div>

                            {{-- Total permissions --}}
                            <div class="m-t-10 m-b-10">
                                <small class="text-muted">
                                    <i class="feather icon-lock"></i>
                                    {{ $role->permissions->count() }} / {{ $allPermissions->count() }} permission(s)
                                </small>
                                <div style="background: #eee; border-radius: 4px; height: 6px; margin-top: 5px;">
                                    <div style="background: {{ $borderColor }}; border-radius: 4px; height: 6px; width: {{ ($role->permissions->count() / max($allPermissions->count(), 1)) * 100 }}%;"></div>
                                </div>
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

    {{-- ── Matrice des permissions ────────────────────────────────── --}}
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
                                                <i class="feather icon-x-circle text-danger" style="font-size:14px; opacity: 0.3;"></i>
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
