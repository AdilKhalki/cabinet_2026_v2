<div>
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <ul class="m-b-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save">
        <div class="row">
            {{-- Colonne gauche --}}
            <div class="col-md-8">

                {{-- Infos de base --}}
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-info"></i> Informations générales</h5>
                        @if($appointmentId)
                            <span class="label label-info m-l-10"><i class="feather icon-calendar"></i> Depuis le RDV n° {{ $appointmentId }}</span>
                        @endif
                        @if($isEdit)
                            <span class="label label-{{ $status === 'completed' ? 'success' : 'warning' }} m-l-5">{{ \App\Models\Consultation::STATUS_LABELS[$status] ?? $status }}</span>
                        @endif
                    </div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient <span class="text-danger">*</span></label>
                                    <x-patient-search-select :patients="$patients" :disabled="$isEdit" />
                                    @error('patient_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Médecin <span class="text-danger">*</span></label>
                                    <select class="form-control" wire:model="doctor_id" @disabled($isEdit || (auth()->user()->isMedecin() && ! auth()->user()->isAdmin()))>
                                        <option value="">-- Sélectionner --</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type de consultation <span class="text-danger">*</span></label>
                                    <select class="form-control" wire:model="consultation_type">
                                        @foreach(\App\Models\Appointment::TYPE_LABELS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('consultation_type') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date et heure <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" wire:model="consultation_date">
                                    @error('consultation_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Clinique --}}
                <div class="card">
                    <div class="card-header"><h5><i class="feather icon-clipboard"></i> Données cliniques</h5></div>
                    <div class="card-block">
                        <div class="form-group">
                            <label>Motif de consultation</label>
                            <textarea class="form-control" wire:model="reason" rows="2"
                                      placeholder="Motif principal de la visite..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Symptômes</label>
                            <textarea class="form-control" wire:model="symptoms" rows="3"
                                      placeholder="Description des symptômes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Examen clinique</label>
                            <textarea class="form-control" wire:model="clinical_exam" rows="4"
                                      placeholder="Résultats de l'examen clinique..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- Diagnostic --}}
                <div class="card">
                    <div class="card-header"><h5><i class="feather icon-search"></i> Diagnostic et traitement</h5></div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Diagnostic</label>
                                    <textarea class="form-control" wire:model="diagnosis" rows="3"
                                              placeholder="Diagnostic principal..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Code CIM-10</label>
                                    <input type="text" class="form-control" wire:model="icd10_code"
                                           placeholder="Ex: J06.9">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Plan de traitement</label>
                            <textarea class="form-control" wire:model="treatment_plan" rows="3"
                                      placeholder="Traitement prescrit, recommandations..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Notes du médecin</label>
                            <textarea class="form-control" wire:model="doctor_notes" rows="3"
                                      placeholder="Notes internes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Résumé de consultation</label>
                            <textarea class="form-control" wire:model="summary" rows="2"
                                      placeholder="Résumé pour le patient..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- Suivi --}}
                <div class="card">
                    <div class="card-block">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-group m-b-0">
                                    <label class="d-flex align-items-center gap-10">
                                        <input type="checkbox" wire:model.live="followup_required" style="margin-right:8px;">
                                        Suivi requis
                                    </label>
                                </div>
                            </div>
                            @if($followup_required)
                            <div class="col-md-6">
                                <div class="form-group m-b-0">
                                    <label>Date de suivi</label>
                                    <input type="date" class="form-control" wire:model="followup_date">
                                    @error('followup_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne droite — Signes vitaux --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5><i class="feather icon-activity"></i> Signes vitaux</h5></div>
                    <div class="card-block">
                        <div class="form-group">
                            <label>Poids (kg)</label>
                            <input type="number" class="form-control" wire:model="weight_kg"
                                   step="0.1" min="0" max="500" placeholder="70.5">
                            @error('weight_kg') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Taille (cm)</label>
                            <input type="number" class="form-control" wire:model="height_cm"
                                   step="1" min="0" max="300" placeholder="175">
                            @error('height_cm') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        @if(is_numeric($weight_kg) && is_numeric($height_cm) && $height_cm > 0)
                        <div class="alert alert-info p-10 m-b-15" style="font-size:12px;">
                            IMC : <strong>{{ number_format($weight_kg / (($height_cm/100) ** 2), 1) }}</strong>
                        </div>
                        @endif
                        <div class="form-group">
                            <label>Tension artérielle</label>
                            <input type="text" class="form-control" wire:model="blood_pressure"
                                   placeholder="120/80">
                            @error('blood_pressure') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Fréquence cardiaque (bpm)</label>
                            <input type="number" class="form-control" wire:model="heart_rate"
                                   min="0" max="300" placeholder="75">
                            @error('heart_rate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Température (°C)</label>
                            <input type="number" class="form-control" wire:model="temperature"
                                   step="0.1" min="30" max="45" placeholder="37.0">
                            @error('temperature') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>SpO2 (%)</label>
                            <input type="number" class="form-control" wire:model="spo2"
                                   min="0" max="100" placeholder="98">
                            @error('spo2') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-block">
                        <button type="submit" class="btn btn-primary btn-block m-b-10">
                            <span wire:loading wire:target="save"><span class="fa fa-spinner fa-spin"></span></span>
                            <i class="feather icon-check-circle"></i>
                            {{ $isEdit && $status === 'completed' ? 'Mettre à jour' : 'Enregistrer et terminer' }}
                        </button>
                        @if(! $isEdit || $status !== 'completed')
                        <button type="button" wire:click="save(false)" class="btn btn-default btn-block m-b-10">
                            <i class="feather icon-save"></i> Enregistrer (consultation en cours)
                        </button>
                        @endif
                        <a href="{{ $isEdit ? route('consultations.show', $consultationId) : route('consultations.index') }}" class="btn btn-default btn-block">
                            <i class="feather icon-x"></i> Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
