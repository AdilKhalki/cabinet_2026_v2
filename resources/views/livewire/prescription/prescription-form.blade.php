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

            {{-- Colonne gauche : en-tête ordonnance --}}
            <div class="col-md-8">

                {{-- Informations générales --}}
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-file-text"></i> Informations générales</h5>
                    </div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient <span class="text-danger">*</span></label>
                                    <x-patient-search-select :patients="$patients" />
                                    @error('patient_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Médecin <span class="text-danger">*</span></label>
                                    <select class="form-control" wire:model="doctor_id">
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
                                    <label>Date de prescription <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" wire:model="prescribed_at">
                                    @error('prescribed_at') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Valable jusqu'au</label>
                                    <input type="date" class="form-control" wire:model="valid_until">
                                    @error('valid_until') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alerte allergies du patient --}}
                @if(!empty($patientAllergies))
                    <div class="alert alert-danger">
                        <i class="feather icon-alert-triangle"></i>
                        <strong>Allergies connues :</strong> {{ $patientAllergies }}
                    </div>
                @endif

                {{-- Lignes de médicaments --}}
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-list"></i> Médicaments prescrits</h5>
                        <div class="card-header-right">
                            <button type="button" class="btn btn-success btn-sm" wire:click="addLine">
                                <i class="feather icon-plus"></i> Ajouter un médicament
                            </button>
                        </div>
                    </div>
                    <div class="card-block p-0">

                        @if(count($lines) === 0)
                            <p class="text-muted text-center p-20">Aucun médicament. Cliquez sur "Ajouter".</p>
                        @endif

                        @foreach($lines as $i => $line)
                        <div class="p-15" style="border-bottom: 1px solid #eee; {{ $i % 2 === 0 ? 'background:#fafafa;' : '' }}">
                            <div class="row align-items-center m-b-5">
                                <div class="col-md-11">
                                    <strong style="color:#666;">
                                        <i class="feather icon-package text-primary"></i>
                                        Médicament {{ $i + 1 }}
                                    </strong>
                                </div>
                                <div class="col-md-1 text-right">
                                    @if(count($lines) > 1)
                                    <button type="button" class="btn btn-danger btn-xs"
                                            wire:click="removeLine({{ $i }})" title="Supprimer">
                                        <i class="feather icon-x"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Sélection médicament + nom --}}
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Depuis la liste</label>
                                        <select class="form-control form-control-sm"
                                                wire:model="lines.{{ $i }}.medication_id"
                                                wire:change="selectMedication({{ $i }}, $event.target.value)">
                                            <option value="">-- Choisir un médicament --</option>
                                            @foreach($medications as $med)
                                                <option value="{{ $med->id }}">
                                                    {{ $med->name }}{{ $med->strength ? ' — '.$med->strength : '' }}{{ $med->dosage_form ? ' ('.$med->dosage_form.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Nom du médicament <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm"
                                               wire:model="lines.{{ $i }}.medication_name"
                                               placeholder="Nom (ou saisie libre)">
                                        @error("lines.$i.medication_name")
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Posologie --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Dosage</label>
                                        <input type="text" class="form-control form-control-sm"
                                               wire:model="lines.{{ $i }}.dosage"
                                               placeholder="Ex: 500mg, 1 cp">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Fréquence</label>
                                        <select class="form-control form-control-sm"
                                                wire:model="lines.{{ $i }}.frequency">
                                            <option value="">-- Choisir --</option>
                                            <option value="1 fois/jour">1 fois/jour</option>
                                            <option value="2 fois/jour">2 fois/jour</option>
                                            <option value="3 fois/jour">3 fois/jour</option>
                                            <option value="4 fois/jour">4 fois/jour</option>
                                            <option value="Matin et soir">Matin et soir</option>
                                            <option value="Matin, midi et soir">Matin, midi et soir</option>
                                            <option value="Toutes les 8h">Toutes les 8h</option>
                                            <option value="Toutes les 12h">Toutes les 12h</option>
                                            <option value="1 fois/semaine">1 fois/semaine</option>
                                            <option value="Au besoin">Au besoin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Durée (jours)</label>
                                        <input type="number" class="form-control form-control-sm"
                                               wire:model="lines.{{ $i }}.duration_days"
                                               min="1" placeholder="7">
                                        @error("lines.$i.duration_days")
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Quantité (boîtes)</label>
                                        <input type="number" class="form-control form-control-sm"
                                               wire:model="lines.{{ $i }}.quantity"
                                               min="1" placeholder="Ex: 1">
                                        @error("lines.$i.quantity")
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group m-b-10">
                                        <label class="small text-muted">Voie</label>
                                        <select class="form-control form-control-sm"
                                                wire:model="lines.{{ $i }}.route">
                                            <option value="Oral">Oral</option>
                                            <option value="Injectable">Injectable</option>
                                            <option value="Topique">Topique</option>
                                            <option value="Inhalé">Inhalé</option>
                                            <option value="Sublingual">Sublingual</option>
                                            <option value="Rectal">Rectal</option>
                                            <option value="Ophtalmique">Ophtalmique</option>
                                            <option value="Nasal">Nasal</option>
                                            <option value="Auriculaire">Auriculaire</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Instructions spécifiques --}}
                            <div class="form-group m-b-0">
                                <label class="small text-muted">Instructions spécifiques</label>
                                <input type="text" class="form-control form-control-sm"
                                       wire:model="lines.{{ $i }}.instructions"
                                       placeholder="Ex: À prendre pendant les repas, ne pas mâcher...">
                            </div>
                        </div>
                        @endforeach

                        @if(count($lines) > 0)
                        <div class="p-15">
                            <button type="button" class="btn btn-success btn-sm" wire:click="addLine">
                                <i class="feather icon-plus"></i> Ajouter un autre médicament
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Notes générales --}}
                <div class="card">
                    <div class="card-header"><h5><i class="feather icon-message-square"></i> Notes / Recommandations</h5></div>
                    <div class="card-block">
                        <div class="form-group m-b-0">
                            <textarea class="form-control" wire:model="notes" rows="3"
                                      placeholder="Conseils généraux, régime alimentaire, activité physique, contre-indications à rappeler..."></textarea>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Colonne droite : récap + actions --}}
            <div class="col-md-4">

                {{-- Récapitulatif --}}
                <div class="card">
                    <div class="card-header"><h5><i class="feather icon-eye"></i> Récapitulatif</h5></div>
                    <div class="card-block">
                        <table class="table table-condensed m-b-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width:50%">Médicaments</td>
                                    <td>
                                        <span class="label label-primary">{{ count($lines) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Date</td>
                                    <td>{{ $prescribed_at ? \Carbon\Carbon::parse($prescribed_at)->format('d/m/Y') : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Valable jusqu'au</td>
                                    <td>{{ $valid_until ? \Carbon\Carbon::parse($valid_until)->format('d/m/Y') : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        @if(count($lines) > 0)
                        <hr class="m-t-10 m-b-10">
                        <p class="small text-muted m-b-5"><strong>Médicaments :</strong></p>
                        <ul class="list-unstyled m-b-0">
                            @foreach($lines as $line)
                                @if($line['medication_name'])
                                <li class="m-b-5">
                                    <i class="feather icon-package text-primary" style="font-size:11px;"></i>
                                    <span style="font-size:13px;">{{ $line['medication_name'] }}</span>
                                    @if($line['dosage'])
                                        <br>
                                        <small class="text-muted" style="margin-left:16px;">
                                            {{ $line['dosage'] }}
                                            @if($line['frequency'])
                                                &mdash; {{ $line['frequency'] }}
                                            @endif
                                            @if($line['duration_days'])
                                                &mdash; {{ $line['duration_days'] }}j
                                            @endif
                                        </small>
                                    @endif
                                </li>
                                @endif
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>

                {{-- Consultation liée (optionnel) --}}
                <div class="card">
                    <div class="card-header"><h5><i class="feather icon-link"></i> Consultation liée</h5></div>
                    <div class="card-block">
                        <div class="form-group m-b-0">
                            <label class="small text-muted">ID Consultation (optionnel)</label>
                            <input type="number" class="form-control" wire:model="consultation_id"
                                   placeholder="Laisser vide si aucune">
                            <small class="text-muted">Associer à une consultation existante</small>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-block">
                        <button type="submit" class="btn btn-primary btn-block m-b-10">
                            <div wire:loading wire:target="save">
                                <span class="fa fa-spinner fa-spin"></span>
                            </div>
                            <i class="feather icon-save"></i>
                            {{ $isEdit ? 'Mettre à jour l\'ordonnance' : 'Enregistrer l\'ordonnance' }}
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-default btn-block">
                            <i class="feather icon-x"></i> Annuler
                        </a>
                    </div>
                </div>

                {{-- Aide --}}
                <div class="card" style="border-left: 3px solid #4099ff;">
                    <div class="card-block p-15">
                        <p class="m-b-5"><strong><i class="feather icon-info text-primary"></i> Aide</strong></p>
                        <ul class="list-unstyled small text-muted m-b-0">
                            <li class="m-b-5">Sélectionnez un médicament dans la liste ou saisissez son nom librement.</li>
                            <li class="m-b-5">Ajoutez autant de lignes que nécessaire.</li>
                            <li>L'ordonnance sera numérotée automatiquement (ORD-AAAA-XXXXX).</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
