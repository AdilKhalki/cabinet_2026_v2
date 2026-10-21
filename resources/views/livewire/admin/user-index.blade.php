<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-users"></i> Gestion des Utilisateurs</h5>
            <div class="card-header-right">
                <button type="button" wire:click="openCreate" class="btn btn-primary btn-sm">
                    <i class="feather icon-user-plus"></i> Nouvel utilisateur
                </button>
            </div>
        </div>
        <div class="card-block">

            {{-- Search & Filter --}}
            <div class="row m-b-20">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par nom, email...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="roleFilter" class="form-control">
                        <option value="">-- Tous les rôles --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <span class="text-muted">{{ $users->total() }} utilisateur(s)</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Téléphone</th>
                            <th>Spécialité</th>
                            <th>Statut</th>
                            <th style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width:34px; height:34px; border-radius:50%; background:#4099ff; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:14px; flex-shrink:0;">
                                            {{ strtoupper(substr($user->name ?? $user->first_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $user->full_name ?? $user->name }}</strong><br>
                                            @if($user->license_number)
                                                <small class="text-muted">{{ $user->license_number }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @php
                                        $roleColors = ['admin'=>'bg-danger','medecin'=>'bg-warning','secretaire'=>'bg-info','nurse'=>'bg-success'];
                                        $roleName = $user->roles->first()->name ?? '—';
                                        $roleColor = $roleColors[$roleName] ?? 'bg-dark';
                                    @endphp
                                    <span class="badge {{ $roleColor }}">{{ $roleName }}</span>
                                </td>
                                <td>{{ $user->phone ?? '—' }}</td>
                                <td>{{ $user->speciality ?? '—' }}</td>
                                <td>
                                    @if($user->is_active ?? true)
                                        <span class="label label-success">Actif</span>
                                    @else
                                        <span class="label label-danger">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" wire:click="openEdit({{ $user->id }})" class="btn btn-warning btn-xs" title="Modifier">
                                        <i class="feather icon-edit"></i>
                                    </button>
                                    @if($user->id !== auth()->id())
                                    <button type="button"
                                        wire:click="toggleActive({{ $user->id }})"
                                        class="btn btn-xs {{ ($user->is_active ?? true) ? 'btn-default' : 'btn-success' }}"
                                        title="{{ ($user->is_active ?? true) ? 'Désactiver' : 'Activer' }}">
                                        <i class="feather {{ ($user->is_active ?? true) ? 'icon-user-x' : 'icon-user-check' }}"></i>
                                    </button>
                                    <button type="button"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer cet utilisateur ?"
                                        class="btn btn-danger btn-xs" title="Supprimer">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-20">
                                    <i class="feather icon-users" style="font-size: 2em;"></i>
                                    <p>Aucun utilisateur trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

    {{-- Modal création / édition --}}
    @if($showModal)
        <div class="modal fade in" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h4 class="modal-title">
                                <i class="feather icon-user"></i>
                                {{ $editingId ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
                            </h4>
                            <button type="button" class="close" wire:click="closeModal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                                        <label>Nom complet <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="name" class="form-control" placeholder="Ex: Dr. Mohamed Alami">
                                        @error('name')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                                        <label>Email <span class="text-danger">*</span></label>
                                        <input type="email" wire:model="email" class="form-control">
                                        @error('email')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                                        <label>Téléphone</label>
                                        <input type="text" wire:model="phone" class="form-control">
                                        @error('phone')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('role') ? 'has-error' : '' }}">
                                        <label>Rôle <span class="text-danger">*</span></label>
                                        <select wire:model="role" class="form-control">
                                            @foreach($roles as $r)
                                                <option value="{{ $r->name }}">{{ $r->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('role')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('speciality') ? 'has-error' : '' }}">
                                        <label>Spécialité</label>
                                        <input type="text" wire:model="speciality" class="form-control" placeholder="Ex: Médecine générale">
                                        @error('speciality')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('password') ? 'has-error' : '' }}">
                                        <label>
                                            Mot de passe
                                            @if(!$editingId)<span class="text-danger">*</span>@else<small class="text-muted">(laisser vide pour ne pas changer)</small>@endif
                                        </label>
                                        <input type="password" wire:model="password" class="form-control" autocomplete="new-password">
                                        @error('password')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirmer le mot de passe</label>
                                        <input type="password" wire:model="password_confirmation" class="form-control" autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group {{ $errors->has('is_active') ? 'has-error' : '' }}">
                                <label>
                                    <input type="checkbox" wire:model="is_active"> Compte actif
                                </label>
                                @error('is_active')<span class="help-block text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" wire:click="closeModal">
                                <i class="feather icon-x"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">
                                    <i class="feather icon-save"></i> Enregistrer
                                </span>
                                <span wire:loading wire:target="save">
                                    <i class="feather icon-loader"></i> Enregistrement...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
