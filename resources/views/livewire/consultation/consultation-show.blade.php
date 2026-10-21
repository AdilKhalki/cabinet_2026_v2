<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header Card --}}
    <div class="card">
        <div class="card-block">
            <div class="row align-items-center">
                <div class="col-md-1 text-center">
                    @if($consultation->patient->photo_path)
                        <img src="{{ Storage::url($consultation->patient->photo_path) }}"
                             class="img-radius" style="width:60px;height:60px;object-fit:cover;">
                    @else
                        <div style="width:60px;height:60px;background:#01a9ac;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;margin:0 auto;">
                            {{ substr($consultation->patient->first_name,0,1) }}{{ substr($consultation->patient->last_name,0,1) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-5">
                    <h4 class="m-b-5">{{ $consultation->patient->full_name }}</h4>
                    <p class="text-muted m-b-0">
                        <i class="feather icon-folder"></i> {{ $consultation->patient->dossier_number }}
                        &nbsp;&bull;&nbsp;
                        <i class="feather icon-calendar"></i> {{ ($consultation->consultation_date ?? $consultation->created_at)->format('d/m/Y H:i') }}
                    </p>
                    <p class="text-muted m-b-0">
                        <i class="feather icon-user"></i> Dr. {{ $consultation->doctor->name }}
                        &nbsp;&bull;&nbsp;
                        <span class="label label-info">{{ $consultation->type_label }}</span>
                        <span class="label label-{{ $consultation->status === 'completed' ? 'success' : 'warning' }} m-l-5">{{ $consultation->status_label }}</span>
                        @if($consultation->followup_required)
                            <span class="label label-warning m-l-5">Suivi requis</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6 text-right">
                    @can('update', $consultation)
                    @if($consultation->status === 'in_progress')
                    <button type="button" wire:click="complete" wire:confirm="Terminer cette consultation ?"
                            class="btn btn-primary btn-sm m-r-5">
                        <i class="feather icon-check-circle"></i> Terminer
                    </button>
                    @endif
                    @endcan
                    @can('create prescriptions')
                    <a href="{{ route('prescriptions.create', ['patient' => $consultation->patient_id, 'doctor' => $consultation->doctor_id, 'consultation' => $consultation->id]) }}"
                       class="btn btn-success btn-sm m-r-5">
                        <i class="feather icon-file-text"></i> Nouvelle Ordonnance
                    </a>
                    @endcan
                    @can('update', $consultation)
                    <a href="{{ route('consultations.edit', $consultation->id) }}"
                       class="btn btn-warning btn-sm m-r-5">
                        <i class="feather icon-edit"></i> Modifier
                    </a>
                    @endcan
                    <a href="{{ route('consultations.index') }}" class="btn btn-default btn-sm">
                        <i class="feather icon-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Signes Vitaux --}}
    @if($consultation->weight_kg || $consultation->blood_pressure || $consultation->heart_rate || $consultation->temperature)
    <div class="row">
        @if($consultation->weight_kg)
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-block p-b-10 p-t-10">
                    <i class="feather icon-activity text-primary" style="font-size:1.4em;"></i>
                    <h5 class="m-t-5">{{ $consultation->weight_kg }} kg</h5>
                    <p class="text-muted m-b-0" style="font-size:11px;">Poids</p>
                    @if($consultation->height_cm)
                        <small class="text-muted">{{ $consultation->height_cm }} cm</small>
                    @endif
                </div>
            </div>
        </div>
        @endif
        @if($consultation->blood_pressure)
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-block p-b-10 p-t-10">
                    <i class="feather icon-heart text-danger" style="font-size:1.4em;"></i>
                    <h5 class="m-t-5">{{ $consultation->blood_pressure }}</h5>
                    <p class="text-muted m-b-0" style="font-size:11px;">Tension Art.</p>
                </div>
            </div>
        </div>
        @endif
        @if($consultation->heart_rate)
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-block p-b-10 p-t-10">
                    <i class="feather icon-radio text-warning" style="font-size:1.4em;"></i>
                    <h5 class="m-t-5">{{ $consultation->heart_rate }} bpm</h5>
                    <p class="text-muted m-b-0" style="font-size:11px;">Fréq. Cardiaque</p>
                </div>
            </div>
        </div>
        @endif
        @if($consultation->temperature)
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-block p-b-10 p-t-10">
                    <i class="feather icon-thermometer text-info" style="font-size:1.4em;"></i>
                    <h5 class="m-t-5">{{ $consultation->temperature }}°C</h5>
                    <p class="text-muted m-b-0" style="font-size:11px;">Température</p>
                </div>
            </div>
        </div>
        @endif
        @if($consultation->spo2)
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-block p-b-10 p-t-10">
                    <i class="feather icon-droplet text-c-blue" style="font-size:1.4em;"></i>
                    <h5 class="m-t-5">{{ $consultation->spo2 }}%</h5>
                    <p class="text-muted m-b-0" style="font-size:11px;">SpO2</p>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Clinique + Diagnostic --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Motif et Clinique</h5></div>
                <div class="card-block">
                    @if($consultation->reason)
                        <h6 class="text-muted">Motif de consultation</h6>
                        <p>{{ $consultation->reason }}</p>
                    @endif
                    @if($consultation->symptoms)
                        <h6 class="text-muted">Symptômes</h6>
                        <p>{{ $consultation->symptoms }}</p>
                    @endif
                    @if($consultation->clinical_exam)
                        <h6 class="text-muted">Examen clinique</h6>
                        <p>{{ $consultation->clinical_exam }}</p>
                    @endif
                    @if(!$consultation->reason && !$consultation->symptoms && !$consultation->clinical_exam)
                        <p class="text-muted">Aucune information clinique.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Diagnostic et Traitement</h5></div>
                <div class="card-block">
                    @if($consultation->diagnosis)
                        <h6 class="text-muted">Diagnostic</h6>
                        <p>{{ $consultation->diagnosis }}
                            @if($consultation->icd10_code)
                                <span class="label label-default m-l-5">{{ $consultation->icd10_code }}</span>
                            @endif
                        </p>
                    @endif
                    @if($consultation->treatment_plan)
                        <h6 class="text-muted">Plan de traitement</h6>
                        <p>{{ $consultation->treatment_plan }}</p>
                    @endif
                    @if($consultation->followup_required && $consultation->followup_date)
                        <div class="alert alert-warning p-10 m-t-10">
                            <i class="feather icon-calendar"></i>
                            Suivi prévu le <strong>{{ \Carbon\Carbon::parse($consultation->followup_date)->format('d/m/Y') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card">
        <div class="card-block">
            <ul class="nav nav-tabs" id="consultTabs">
                <li class="active">
                    <a data-toggle="tab" href="#tab-prescriptions">
                        <i class="feather icon-file-text"></i> Ordonnances ({{ $consultation->prescriptions->count() }})
                    </a>
                </li>
                <li>
                    <a data-toggle="tab" href="#tab-labtests">
                        <i class="feather icon-activity"></i> Analyses ({{ $consultation->labTests->count() }})
                    </a>
                </li>
                <li>
                    <a data-toggle="tab" href="#tab-images">
                        <i class="feather icon-image"></i> Images ({{ $consultation->medicalImages->count() }})
                    </a>
                </li>
                <li>
                    <a data-toggle="tab" href="#tab-documents">
                        <i class="feather icon-paperclip"></i> Documents ({{ $consultation->documents->count() }})
                    </a>
                </li>
                <li>
                    <a data-toggle="tab" href="#tab-notes">
                        <i class="feather icon-edit-3"></i> Notes
                    </a>
                </li>
            </ul>
            <div class="tab-content m-t-20">

                <div id="tab-prescriptions" class="tab-pane fade in active">
                    @if($consultation->prescriptions->isEmpty())
                        <p class="text-center text-muted p-20">Aucune ordonnance pour cette consultation.</p>
                    @else
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>N° Ordonnance</th>
                                    <th>Date</th>
                                    <th>Médicaments</th>
                                    <th>Imprimée</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($consultation->prescriptions as $rx)
                                <tr>
                                    <td><strong>{{ $rx->prescription_number }}</strong></td>
                                    <td>{{ $rx->prescribed_at ? \Carbon\Carbon::parse($rx->prescribed_at)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $rx->lines->count() }} médicament(s)</td>
                                    <td>
                                        @if($rx->is_printed)
                                            <span class="label label-success">Oui</span>
                                        @else
                                            <span class="label label-default">Non</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('prescriptions.show', $rx->id) }}" class="btn btn-info btn-xs">
                                            <i class="feather icon-eye"></i>
                                        </a>
                                        <a href="{{ route('prescriptions.pdf', $rx->id) }}" target="_blank" class="btn btn-primary btn-xs">
                                            <i class="feather icon-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div id="tab-labtests" class="tab-pane fade">
                    @if($consultation->labTests->isEmpty())
                        <p class="text-center text-muted p-20">Aucune analyse prescrite.</p>
                    @else
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>N° Analyse</th>
                                    <th>Examen</th>
                                    <th>Statut</th>
                                    <th>Date résultat</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($consultation->labTests as $lt)
                                <tr>
                                    <td>{{ $lt->test_number }}</td>
                                    <td>{{ $lt->test_name }}</td>
                                    <td>
                                        <span class="label label-{{ $lt->status === 'completed' ? 'success' : ($lt->status === 'pending' ? 'warning' : 'default') }}">
                                            {{ ucfirst($lt->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $lt->result_date ? \Carbon\Carbon::parse($lt->result_date)->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <a href="{{ route('lab-tests.show', $lt->id) }}" class="btn btn-info btn-xs">
                                            <i class="feather icon-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div id="tab-images" class="tab-pane fade">
                    @if($consultation->medicalImages->isEmpty())
                        <p class="text-center text-muted p-20">Aucune image médicale.</p>
                    @else
                        <div class="row">
                        @foreach($consultation->medicalImages as $img)
                            <div class="col-md-3 m-b-15">
                                <div class="card">
                                    @php $ext = strtolower(pathinfo($img->file_path, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg','jpeg','png','gif']))
                                        <img src="{{ $img->file_url }}" class="img-fluid"
                                             style="height:150px;object-fit:cover;">
                                    @else
                                        <div class="text-center p-20" style="background:#f4f7fa;">
                                            <i class="feather icon-file" style="font-size:3em;color:#aaa;"></i>
                                        </div>
                                    @endif
                                    <div class="card-block p-10">
                                        <small class="text-muted">{{ $img->category ?? 'Imagerie' }}</small>
                                        <p class="m-b-0">{{ $img->title ?? basename($img->file_path) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    @endif
                </div>

                <div id="tab-documents" class="tab-pane fade">
                    @if($consultation->documents->isEmpty())
                        <p class="text-center text-muted p-20">Aucun document joint.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr><th>Nom</th><th>Catégorie</th><th>Date</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                            @foreach($consultation->documents as $doc)
                                <tr>
                                    <td><i class="feather icon-paperclip"></i> {{ $doc->title ?? basename($doc->file_path) }}</td>
                                    <td>{{ $doc->category ?? '-' }}</td>
                                    <td>{{ $doc->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-primary btn-xs">
                                            <i class="feather icon-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div id="tab-notes" class="tab-pane fade">
                    @if($consultation->doctor_notes)
                        <h6 class="text-muted">Notes du médecin</h6>
                        <div class="well well-sm" style="white-space:pre-wrap;">{{ $consultation->doctor_notes }}</div>
                    @endif
                    @if($consultation->summary)
                        <h6 class="text-muted m-t-20">Résumé de consultation</h6>
                        <div class="well well-sm" style="white-space:pre-wrap;">{{ $consultation->summary }}</div>
                    @endif
                    @if(!$consultation->doctor_notes && !$consultation->summary)
                        <p class="text-center text-muted p-20">Aucune note enregistrée.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
