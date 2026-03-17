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
                <i class="feather icon-file-text"></i>
                {{ $reportId ? 'Modifier le Rapport Médical' : 'Nouveau Rapport Médical' }}
            </h5>
            <div class="card-header-right">
                <a href="{{ url()->previous() }}" class="btn btn-default btn-sm">
                    <i class="feather icon-arrow-left"></i> Retour
                </a>
            </div>
        </div>
        <div class="card-block">
            <form wire:submit="save">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('patient_id') ? 'has-error' : '' }}">
                            <label>Patient <span class="text-danger">*</span></label>
                            <x-patient-search-select :patients="$patients" />
                            @error('patient_id')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('doctor_id') ? 'has-error' : '' }}">
                            <label>Médecin <span class="text-danger">*</span></label>
                            <select wire:model="doctor_id" class="form-control">
                                <option value="">-- Sélectionner un médecin --</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
                                @endforeach
                            </select>
                            @error('doctor_id')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('consultation_id') ? 'has-error' : '' }}">
                            <label>Consultation liée <small class="text-muted">(optionnel)</small></label>
                            <select wire:model="consultation_id" class="form-control">
                                <option value="">-- Aucune --</option>
                                @foreach($consultations as $consultation)
                                    <option value="{{ $consultation->id }}">
                                        {{ \Carbon\Carbon::parse($consultation->consultation_date)->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('consultation_id')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('type') ? 'has-error' : '' }}">
                            <label>Type de rapport <span class="text-danger">*</span></label>
                            <select wire:model.live="type" class="form-control">
                                <option value="">-- Type --</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('report_date') ? 'has-error' : '' }}">
                            <label>Date du rapport</label>
                            <input type="date" wire:model="report_date" class="form-control">
                            @error('report_date')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                            <label>Titre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="title" class="form-control" placeholder="Titre du rapport">
                            @error('title')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                {{-- Conditional Fields --}}
                @if($type === 'sick_leave')
                    <div class="row" style="background: #fff8e1; padding: 10px; border-radius: 4px; margin: 0 0 15px;">
                        <div class="col-md-12">
                            <h6><i class="feather icon-calendar"></i> Paramètres de l'arrêt de travail</h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group {{ $errors->has('sick_leave_days') ? 'has-error' : '' }}">
                                <label>Nombre de jours</label>
                                <input type="number" wire:model="sick_leave_days" class="form-control" min="1" placeholder="Ex: 7">
                                @error('sick_leave_days')<span class="help-block text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group {{ $errors->has('valid_from') ? 'has-error' : '' }}">
                                <label>Valable du</label>
                                <input type="date" wire:model="valid_from" class="form-control">
                                @error('valid_from')<span class="help-block text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group {{ $errors->has('valid_until') ? 'has-error' : '' }}">
                                <label>Au</label>
                                <input type="date" wire:model="valid_until" class="form-control">
                                @error('valid_until')<span class="help-block text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                @endif

                @if($type === 'fitness_certificate')
                    <div class="row" style="background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 0 0 15px;">
                        <div class="col-md-12">
                            <h6><i class="feather icon-check-circle"></i> Paramètres du certificat d'aptitude</h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group {{ $errors->has('valid_until') ? 'has-error' : '' }}">
                                <label>Valable jusqu'au</label>
                                <input type="date" wire:model="valid_until" class="form-control">
                                @error('valid_until')<span class="help-block text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Template Buttons --}}
                <div class="form-group">
                    <label>Modèles de contenu :</label><br>
                    <button type="button" wire:click="applyTemplate('sick_leave')" class="btn btn-default btn-sm m-r-5 m-b-5">
                        <i class="feather icon-file"></i> Arrêt maladie
                    </button>
                    <button type="button" wire:click="applyTemplate('fitness_certificate')" class="btn btn-default btn-sm m-r-5 m-b-5">
                        <i class="feather icon-check-circle"></i> Aptitude
                    </button>
                    <button type="button" wire:click="applyTemplate('medical_certificate')" class="btn btn-default btn-sm m-r-5 m-b-5">
                        <i class="feather icon-file-text"></i> Certificat médical
                    </button>
                    <button type="button" wire:click="applyTemplate('referral')" class="btn btn-default btn-sm m-r-5 m-b-5">
                        <i class="feather icon-send"></i> Lettre de référence
                    </button>
                    <button type="button" wire:click="applyTemplate('surgery_report')" class="btn btn-default btn-sm m-r-5 m-b-5">
                        <i class="feather icon-scissors"></i> CR chirurgical
                    </button>
                </div>

                <div class="form-group {{ $errors->has('content') ? 'has-error' : '' }}">
                    <label>Contenu du rapport <span class="text-danger">*</span></label>
                    <textarea wire:model="content" class="form-control" rows="14" placeholder="Rédigez le contenu du rapport ici..."></textarea>
                    @error('content')<span class="help-block text-danger">{{ $message }}</span>@enderror
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12 text-right">
                        <a href="{{ url()->previous() }}" class="btn btn-default">
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
