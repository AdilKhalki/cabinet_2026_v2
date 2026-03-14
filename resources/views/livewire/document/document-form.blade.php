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
                {{ $documentId ? 'Modifier le Document' : 'Nouveau Document' }}
            </h5>
            <div class="card-header-right">
                <a href="{{ route('documents.index') }}" class="btn btn-default btn-sm">
                    <i class="feather icon-arrow-left"></i> Retour
                </a>
            </div>
        </div>
        <div class="card-block">
            <form wire:submit="save">
                <div class="row">
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
                                <div class="form-group {{ $errors->has('consultation_id') ? 'has-error' : '' }}">
                                    <label>Consultation liée <small class="text-muted">(optionnel)</small></label>
                                    <select wire:model="consultation_id" class="form-control">
                                        <option value="">-- Aucune consultation --</option>
                                        @foreach($consultations as $consultation)
                                            <option value="{{ $consultation->id }}">
                                                {{ \Carbon\Carbon::parse($consultation->consultation_date)->format('d/m/Y') }} — {{ $consultation->patient->full_name ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('consultation_id')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('category') ? 'has-error' : '' }}">
                                    <label>Catégorie <span class="text-danger">*</span></label>
                                    <select wire:model="category" class="form-control">
                                        <option value="">-- Catégorie --</option>
                                        @foreach($categories as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('document_date') ? 'has-error' : '' }}">
                                    <label>Date du document</label>
                                    <input type="date" wire:model="document_date" class="form-control">
                                    @error('document_date')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                            <label>Titre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="title" class="form-control" placeholder="Titre du document">
                            @error('title')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                            <label>Description</label>
                            <textarea wire:model="description" class="form-control" rows="3" placeholder="Description du document..."></textarea>
                            @error('description')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('source') ? 'has-error' : '' }}">
                            <label>Source / Origine</label>
                            <input type="text" wire:model="source" class="form-control" placeholder="Ex: Hôpital Ibn Rochd, Dr. Alami...">
                            @error('source')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                    </div>

                    {{-- File Upload --}}
                    <div class="col-md-4">

                        <div class="form-group {{ $errors->has('file') ? 'has-error' : '' }}">
                            <label><i class="feather icon-paperclip"></i> Fichier <span class="text-danger">*</span></label>
                            <input type="file" wire:model="file" class="form-control">
                            <small class="text-muted">PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Max 20 Mo.</small>
                            <div wire:loading wire:target="file" class="m-t-5 text-info">
                                <i class="feather icon-loader"></i> Chargement du fichier...
                            </div>
                            @error('file')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        {{-- File Info after upload --}}
                        @if($file)
                            <div class="alert alert-info m-t-10" style="padding: 10px;">
                                <i class="feather icon-check-circle"></i>
                                <strong>Fichier sélectionné :</strong><br>
                                <span>{{ $file->getClientOriginalName() }}</span><br>
                                <small class="text-muted">
                                    Taille :
                                    @if($file->getSize() >= 1048576)
                                        {{ round($file->getSize() / 1048576, 2) }} Mo
                                    @else
                                        {{ round($file->getSize() / 1024, 1) }} Ko
                                    @endif
                                </small><br>
                                <small class="text-muted">Type : {{ strtoupper($file->extension()) }}</small>
                            </div>
                        @elseif($existingFileName)
                            <div class="alert alert-default m-t-10" style="padding: 10px; border: 1px solid #ddd;">
                                <i class="feather icon-file"></i>
                                <strong>Fichier actuel :</strong><br>
                                {{ $existingFileName }}
                            </div>
                        @else
                            <div class="text-center p-20 text-muted" style="border: 2px dashed #ddd; border-radius: 4px;">
                                <i class="feather icon-upload" style="font-size: 2.5em;"></i>
                                <p class="m-t-5">Glissez un fichier ou cliquez pour sélectionner</p>
                            </div>
                        @endif

                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12 text-right">
                        <a href="{{ route('documents.index') }}" class="btn btn-default">
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
