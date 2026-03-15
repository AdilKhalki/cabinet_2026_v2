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
                <i class="feather icon-activity"></i>
                {{ $isEdit ? 'Modifier Analyse' : 'Nouvelle Demande d\'Analyse' }}
            </h5>
            <div class="card-header-right">
                <a href="{{ route('lab-tests.index') }}" class="btn btn-default btn-sm">
                    <i class="feather icon-arrow-left"></i> Retour
                </a>
            </div>
        </div>
        <div class="card-block">
            <form wire:submit="save">
                <div class="row">
                    {{-- Left Column --}}
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
                                    <label>Médecin prescripteur <span class="text-danger">*</span></label>
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
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('requested_at') ? 'has-error' : '' }}">
                                    <label>Date de demande</label>
                                    <input type="date" wire:model="requested_at" class="form-control">
                                    @error('requested_at')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('resulted_at') ? 'has-error' : '' }}">
                                    <label>Date des résultats</label>
                                    <input type="date" wire:model="result_date" class="form-control">
                                    @error('result_date')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                                    <label>Statut</label>
                                    <select wire:model="status" class="form-control">
                                        <option value="pending">En attente</option>
                                        <option value="in_progress">En cours</option>
                                        <option value="completed">Complété</option>
                                        <option value="cancelled">Annulé</option>
                                    </select>
                                    @error('status')<span class="help-block text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('laboratory_name') ? 'has-error' : '' }}">
                            <label>Nom du laboratoire</label>
                            <input type="text" wire:model="laboratory_name" class="form-control" placeholder="Ex: Laboratoire Ibn Sina">
                            @error('laboratory_name')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('clinical_info') ? 'has-error' : '' }}">
                            <label>Informations cliniques</label>
                            <textarea wire:model="clinical_info" class="form-control" rows="3" placeholder="Contexte clinique, motif de la demande..."></textarea>
                            @error('clinical_info')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                    </div>

                    {{-- Right Column --}}
                    <div class="col-md-4">

                        <div class="form-group {{ $errors->has('doctor_interpretation') ? 'has-error' : '' }}">
                            <label><i class="feather icon-file-text"></i> Interprétation médicale</label>
                            <textarea wire:model="doctor_interpretation" class="form-control" rows="6" placeholder="Interprétation et conclusions du médecin..."></textarea>
                            @error('doctor_interpretation')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('pdf_file') ? 'has-error' : '' }}">
                            <label><i class="feather icon-paperclip"></i> Fichier PDF du rapport</label>
                            <input type="file" wire:model="pdf_file" class="form-control" accept=".pdf">
                            <div wire:loading wire:target="pdf_file" class="text-info m-t-5">
                                <i class="feather icon-loader"></i> Chargement...
                            </div>
                            @if($pdf_file)
                                <p class="text-success m-t-5"><i class="feather icon-check"></i> Fichier sélectionné : {{ $pdf_file->getClientOriginalName() }}</p>
                            @endif
                            @error('pdf_file')<span class="help-block text-danger">{{ $message }}</span>@enderror
                        </div>

                    </div>
                </div>

                <hr>

                {{-- Results Section --}}
                <div class="row">
                    <div class="col-md-12">
                        <h6><i class="feather icon-list"></i> Résultats de l'analyse</h6>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr class="bg-c-blue">
                                        <th class="text-white" style="min-width:160px">Type d'analyse</th>
                                        <th class="text-white" style="min-width:140px">Paramètre</th>
                                        <th class="text-white" style="width:90px">Valeur</th>
                                        <th class="text-white" style="width:80px">Unité</th>
                                        <th class="text-white" style="width:80px">Min</th>
                                        <th class="text-white" style="width:80px">Max</th>
                                        <th class="text-white" style="width:110px">Statut</th>
                                        <th class="text-white" style="min-width:120px">Notes</th>
                                        <th class="text-white" style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $i => $result)
                                        <tr>
                                            <td>
                                                <select wire:change="fillFromType({{ $i }}, $event.target.value)" class="form-control input-sm">
                                                    <option value="">-- Type --</option>
                                                    @foreach($labTestTypes as $type)
                                                        <option value="{{ $type->id }}" {{ ($result['lab_test_type_id'] ?? '') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" wire:model="results.{{ $i }}.parameter_name" class="form-control input-sm" placeholder="Paramètre">
                                                @error("results.{$i}.parameter_name")<span class="help-block text-danger" style="font-size:10px">{{ $message }}</span>@enderror
                                            </td>
                                            <td>
                                                <input type="text" wire:model="results.{{ $i }}.value" wire:change="autoFlag({{ $i }})" class="form-control input-sm" placeholder="Valeur">
                                                @error("results.{$i}.value")<span class="help-block text-danger" style="font-size:10px">{{ $message }}</span>@enderror
                                            </td>
                                            <td>
                                                <input type="text" wire:model="results.{{ $i }}.unit" class="form-control input-sm" placeholder="g/L">
                                            </td>
                                            <td>
                                                <input type="number" step="any" wire:model="results.{{ $i }}.normal_min" class="form-control input-sm" placeholder="Min">
                                            </td>
                                            <td>
                                                <input type="number" step="any" wire:model="results.{{ $i }}.normal_max" class="form-control input-sm" placeholder="Max">
                                            </td>
                                            <td>
                                                <select wire:model="results.{{ $i }}.flag" class="form-control input-sm">
                                                    <option value="normal">Normal</option>
                                                    <option value="low">Bas</option>
                                                    <option value="high">Élevé</option>
                                                    <option value="critical">Critique</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" wire:model="results.{{ $i }}.notes" class="form-control input-sm" placeholder="Notes...">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" wire:click="removeResult({{ $i }})" class="btn btn-danger btn-xs" title="Supprimer">
                                                    <i class="feather icon-trash-2"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="9">
                                            <button type="button" wire:click="addResult" class="btn btn-success btn-sm">
                                                <i class="feather icon-plus"></i> Ajouter un paramètre
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12 text-right">
                        <a href="{{ route('lab-tests.index') }}" class="btn btn-default">
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
