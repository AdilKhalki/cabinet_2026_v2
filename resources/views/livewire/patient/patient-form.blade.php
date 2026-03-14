<div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" enctype="multipart/form-data">
        <div class="row">

            {{-- Informations personnelles --}}
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-user"></i> Informations Personnelles</h5>
                    </div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('last_name') ? 'has-error' : '' }}">
                                    <label class="control-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="last_name" class="form-control"
                                           placeholder="Nom de famille">
                                    @error('last_name') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('first_name') ? 'has-error' : '' }}">
                                    <label class="control-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="first_name" class="form-control"
                                           placeholder="Prénom">
                                    @error('first_name') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('cin') ? 'has-error' : '' }}">
                                    <label class="control-label">CIN</label>
                                    <input type="text" wire:model="cin" class="form-control"
                                           placeholder="Carte Identité Nationale">
                                    @error('cin') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">N° IP</label>
                                    <input type="text" wire:model="ip_number" class="form-control"
                                           placeholder="Numéro IPP">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('birth_date') ? 'has-error' : '' }}">
                                    <label class="control-label">Date de naissance <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="birth_date" class="form-control">
                                    @error('birth_date') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('gender') ? 'has-error' : '' }}">
                                    <label class="control-label">Sexe <span class="text-danger">*</span></label>
                                    <select wire:model="gender" class="form-control">
                                        <option value="">Sélectionner...</option>
                                        <option value="male">Homme</option>
                                        <option value="female">Femme</option>
                                    </select>
                                    @error('gender') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('blood_group') ? 'has-error' : '' }}">
                                    <label class="control-label">Groupe sanguin</label>
                                    <select wire:model="blood_group" class="form-control">
                                        <option value="">Inconnu</option>
                                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                                            <option value="{{ $bg }}">{{ $bg }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Profession</label>
                                    <input type="text" wire:model="profession" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                                    <label class="control-label">Téléphone principal <span class="text-danger">*</span></label>
                                    <input type="tel" wire:model="phone" class="form-control"
                                           placeholder="+212 600 000 000">
                                    @error('phone') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Téléphone secondaire</label>
                                    <input type="tel" wire:model="phone_secondary" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Email</label>
                            <input type="email" wire:model="email" class="form-control"
                                   placeholder="patient@exemple.com">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Adresse</label>
                            <input type="text" wire:model="address" class="form-control"
                                   placeholder="Adresse complète">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Ville</label>
                            <input type="text" wire:model="city" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Allergies connues</label>
                            <textarea wire:model="known_allergies" class="form-control" rows="2"
                                      placeholder="Ex: Pénicilline, aspirine..."></textarea>
                        </div>

                    </div>
                </div>

                {{-- Assurance --}}
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-shield"></i> Assurance / Mutuelle</h5>
                    </div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Nom de la mutuelle</label>
                                    <input type="text" wire:model="insurance_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">N° de contrat / immatriculation</label>
                                    <input type="text" wire:model="insurance_number" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-file-text"></i> Notes</h5>
                    </div>
                    <div class="card-block">
                        <div class="form-group">
                            <textarea wire:model="notes" class="form-control" rows="4"
                                      placeholder="Notes supplémentaires..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne droite : photo --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-camera"></i> Photo</h5>
                    </div>
                    <div class="card-block text-center">
                        {{-- Aperçu photo --}}
                        @if($photo)
                            <img src="{{ $photo->temporaryUrl() }}"
                                 class="img-radius img-fluid m-b-15"
                                 style="max-height:200px;object-fit:cover;"
                                 alt="Aperçu photo">
                        @elseif($isEdit && $patient?->photo_path)
                            <img src="{{ $patient->photo_url }}"
                                 class="img-radius img-fluid m-b-15"
                                 style="max-height:200px;object-fit:cover;"
                                 alt="Photo actuelle">
                        @else
                            <div style="width:120px;height:120px;background:#f0f0f0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:15px;">
                                <i class="feather icon-user" style="font-size:3em;color:#aaa;"></i>
                            </div>
                        @endif

                        <div class="form-group">
                            <input type="file" wire:model="photo" accept="image/*" class="form-control">
                            @error('photo') <span class="help-block text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div wire:loading wire:target="photo" class="text-info">
                            <i class="feather icon-loader"></i> Chargement...
                        </div>
                    </div>
                </div>

                {{-- Dossier (en mode édition) --}}
                @if($isEdit)
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="feather icon-folder"></i> Dossier</h5>
                        </div>
                        <div class="card-block">
                            <p><strong>N° Dossier :</strong>
                                <span class="label label-primary">{{ $patient->dossier_number }}</span>
                            </p>
                            <p><strong>Créé le :</strong> {{ $patient->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Boutons --}}
                <div class="card">
                    <div class="card-block">
                        <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="feather icon-save"></i>
                                {{ $isEdit ? 'Mettre à jour' : 'Enregistrer le patient' }}
                            </span>
                            <span wire:loading>
                                <i class="feather icon-loader"></i> Enregistrement...
                            </span>
                        </button>
                        <a href="{{ route('patients.index') }}" class="btn btn-default btn-block m-t-5">
                            <i class="feather icon-x"></i> Annuler
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
