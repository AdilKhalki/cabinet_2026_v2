<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5>
                <i class="feather icon-upload"></i>
                {{ $imageId ? 'Modifier Image Médicale' : 'Nouvelle Image Médicale' }}
            </h5>
            <div class="card-header-right">
                <a href="{{ route('medical-images.index') }}" class="btn btn-default btn-sm">
                    <i class="feather icon-arrow-left"></i> Retour
                </a>
            </div>
        </div>
        <div class="card-block">
            <form wire:submit="save">
                <div class="row">

                    {{-- Form Fields --}}
                    <div class="col-md-8">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('patient_id') ? 'has-error' : '' }}">
                                    <label>Patient <span class="text-danger">*</span></label>
                                    <select wire:model="patient_id" class="form-control">
                                        <option value="">-- Sélectionner un patient --</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('patient_id')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('doctor_id') ? 'has-error' : '' }}">
                                    <label>Médecin</label>
                                    <select wire:model="doctor_id" class="form-control">
                                        <option value="">-- Sélectionner un médecin --</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('category') ? 'has-error' : '' }}">
                                    <label>Catégorie <span class="text-danger">*</span></label>
                                    <select wire:model="category" class="form-control">
                                        <option value="">-- Catégorie --</option>
                                        @foreach($categoryLabels as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('image_date') ? 'has-error' : '' }}">
                                    <label>Date de l'image</label>
                                    <input type="date" wire:model="image_date" class="form-control">
                                    @error('image_date')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                            <label>Titre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="title" class="form-control" placeholder="Ex: Radio thorax de face">
                            @error('title')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('body_part') ? 'has-error' : '' }}">
                                    <label>Partie du corps</label>
                                    <input type="text" wire:model="body_part" class="form-control" placeholder="Ex: Thorax, Genou gauche...">
                                    @error('body_part')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                            <label>Description</label>
                            <textarea wire:model="description" class="form-control" rows="3" placeholder="Description de l'image médicale..."></textarea>
                            @error('description')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('radiological_report') ? 'has-error' : '' }}">
                            <label><i class="feather icon-file-text"></i> Compte-rendu radiologique</label>
                            <textarea wire:model="radiological_report" class="form-control" rows="5" placeholder="Compte-rendu du radiologue..."></textarea>
                            @error('radiological_report')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                    </div>

                    {{-- File Upload & Preview --}}
                    <div class="col-md-4">

                        <div class="form-group {{ $errors->has('imageFile') ? 'has-error' : '' }}">
                            <label><i class="feather icon-upload"></i> Fichier image <span class="text-danger">*</span></label>
                            <input type="file" wire:model="imageFile" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted">Formats acceptés : JPG, PNG, GIF, PDF. Max 10 Mo.</small>
                            <div wire:loading wire:target="imageFile" class="text-info m-t-5">
                                <i class="feather icon-loader"></i> Chargement en cours...
                            </div>
                            @error('imageFile')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        {{-- Image Preview --}}
                        @if($imageFile)
                            @php
                                $ext = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION));
                            @endphp
                            @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <div class="m-t-10">
                                    <label>Aperçu :</label>
                                    <div style="border: 1px solid #ddd; border-radius: 4px; padding: 5px; text-align: center;">
                                        <img src="{{ $imageFile->temporaryUrl() }}" alt="Aperçu" style="max-width: 100%; max-height: 250px; border-radius: 4px;">
                                    </div>
                                </div>
                            @else
                                <div class="m-t-10 text-center p-20" style="border: 1px solid #ddd; border-radius: 4px;">
                                    <i class="feather icon-file" style="font-size: 3em; color: #e74c3c;"></i>
                                    <p class="m-t-5 text-muted">{{ $imageFile->getClientOriginalName() }}</p>
                                    <small class="text-muted">{{ round($imageFile->getSize() / 1024, 1) }} Ko</small>
                                </div>
                            @endif
                        @elseif($existingFilePath)
                            <div class="m-t-10">
                                <label>Fichier actuel :</label>
                                <div style="border: 1px solid #ddd; border-radius: 4px; padding: 10px; text-align: center;">
                                    @if(in_array(strtolower(pathinfo($existingFilePath, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif']))
                                        <img src="{{ Storage::url($existingFilePath) }}" alt="Image actuelle" style="max-width: 100%; max-height: 200px;">
                                    @else
                                        <i class="feather icon-file" style="font-size: 3em; color: #e74c3c;"></i>
                                        <p class="text-muted">Fichier PDF existant</p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center p-30 text-muted" style="border: 2px dashed #ddd; border-radius: 4px; margin-top: 10px;">
                                <i class="feather icon-image" style="font-size: 3em;"></i>
                                <p>Aucun fichier sélectionné</p>
                            </div>
                        @endif

                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12 text-right">
                        <a href="{{ route('medical-images.index') }}" class="btn btn-default">
                            <i class="feather icon-x"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:loading.class="disabled">
                            <span wire:loading.remove wire:target="save">
                                <i class="feather icon-save"></i> Enregistrer
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="feather icon-loader"></i> Enregistrement...
                            </span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
