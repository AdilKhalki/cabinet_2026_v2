<div>
    {{-- En-tête du dossier --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card user-card">
                <div class="card-block">
                    <div class="row">
                        <div class="col-sm-auto text-center">
                            @if($patient->photo_path)
                                <img src="{{ $patient->photo_url }}"
                                     class="img-radius"
                                     style="width:100px;height:100px;object-fit:cover;">
                            @else
                                <div style="width:100px;height:100px;background:{{ $patient->gender==='male'?'#01a9ac':'#ff5370' }};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5em;color:#fff;font-weight:700;">
                                    {{ mb_substr($patient->first_name,0,1) }}
                                </div>
                            @endif
                        </div>
                        <div class="col-sm">
                            <h3 class="m-0">{{ $patient->full_name }}</h3>
                            <p class="text-muted">
                                <span class="label label-primary">{{ $patient->dossier_number }}</span>
                                &nbsp;
                                <span class="label {{ $patient->gender==='male'?'label-info':'label-warning' }}">
                                    {{ $patient->gender==='male'?'Homme':'Femme' }}
                                </span>
                                @if($patient->blood_group)
                                    &nbsp;<span class="label label-danger">{{ $patient->blood_group }}</span>
                                @endif
                            </p>
                            <div class="row m-t-10">
                                <div class="col-md-3"><i class="feather icon-calendar"></i> {{ $patient->age }} ans ({{ $patient->birth_date->format('d/m/Y') }})</div>
                                <div class="col-md-3"><i class="feather icon-phone"></i> {{ $patient->phone }}</div>
                                @if($patient->email)
                                    <div class="col-md-3"><i class="feather icon-mail"></i> {{ $patient->email }}</div>
                                @endif
                                @if($patient->city)
                                    <div class="col-md-3"><i class="feather icon-map-pin"></i> {{ $patient->city }}</div>
                                @endif
                            </div>
                            @if($patient->known_allergies)
                                <div class="m-t-10">
                                    <span class="label label-danger"><i class="feather icon-alert-triangle"></i> Allergies :</span>
                                    {{ $patient->known_allergies }}
                                </div>
                            @endif
                        </div>
                        <div class="col-sm-auto">
                            @can('update', $patient)
                                <a href="{{ route('patients.edit', $patient) }}" class="btn btn-primary btn-sm">
                                    <i class="feather icon-edit"></i> Modifier
                                </a>
                            @endcan
                            @can('create', App\Models\Appointment::class)
                            <a href="{{ route('appointments.create', ['patient' => $patient->id]) }}" class="btn btn-info btn-sm m-t-5">
                                <i class="feather icon-calendar"></i> RDV
                            </a>
                            @endcan
                            @can('create', App\Models\Consultation::class)
                            <a href="{{ route('consultations.create', ['patient' => $patient->id]) }}" class="btn btn-success btn-sm m-t-5">
                                <i class="feather icon-activity"></i> Consultation
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Onglets de navigation --}}
    <div class="card">
        <div class="card-block p-0">
            @php
                $tabs = [
                    'overview'       => ['icon'=>'icon-eye',        'label'=>'Vue d\'ensemble',                        'roles'=>null],
                    'consultations'  => ['icon'=>'icon-activity',   'label'=>'Consultations ('.count($patient->consultations).')',   'roles'=>['admin','medecin']],
                    'prescriptions'  => ['icon'=>'icon-file-text',  'label'=>'Ordonnances ('.count($patient->prescriptions).')',     'roles'=>['admin','medecin']],
                    'lab-tests'      => ['icon'=>'icon-thermometer','label'=>'Analyses ('.count($patient->labTests).')',             'roles'=>['admin','medecin']],
                    'images'         => ['icon'=>'icon-image',      'label'=>'Imagerie ('.count($patient->medicalImages).')',        'roles'=>['admin','medecin']],
                    'reports'        => ['icon'=>'icon-clipboard',  'label'=>'Rapports ('.count($patient->medicalReports).')',       'roles'=>['admin','medecin']],
                    'acts'           => ['icon'=>'icon-list',       'label'=>'Actes Médicaux',                          'roles'=>['admin','medecin']],
                    'history'        => ['icon'=>'icon-clock',      'label'=>'Antécédents',                             'roles'=>null],
                    'documents'      => ['icon'=>'icon-folder',     'label'=>'Documents ('.count($patient->documents).')',           'roles'=>null],
                    'payments'       => ['icon'=>'icon-credit-card','label'=>'Paiements ('.count($patient->payments).')',            'roles'=>null],
                ];
            @endphp
            <ul class="nav nav-tabs" style="margin:0;border-bottom:none;">
                @foreach($tabs as $tab => $info)
                    @if($info['roles'] === null || auth()->user()->hasAnyRole($info['roles']))
                    <li class="{{ $activeTab === $tab ? 'active' : '' }}">
                        <a href="#" wire:click.prevent="setTab('{{ $tab }}')" style="border-radius:0;">
                            <i class="feather {{ $info['icon'] }}"></i> {{ $info['label'] }}
                        </a>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Contenu des onglets --}}
    <div class="tab-content">

        {{-- Vue d'ensemble --}}
        @if($activeTab === 'overview')
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-c-blue">
                        <div class="card-block text-white text-center">
                            <h2>{{ $patient->consultations->count() }}</h2>
                            <p>Consultations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-c-green">
                        <div class="card-block text-white text-center">
                            <h2>{{ $patient->appointments->count() }}</h2>
                            <p>Rendez-vous</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-c-yellow">
                        <div class="card-block text-white text-center">
                            <h2>{{ $patient->prescriptions->count() }}</h2>
                            <p>Ordonnances</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-c-pink">
                        <div class="card-block text-white text-center">
                            <h2>{{ $patient->payments->where('status','pending')->count() }}</h2>
                            <p>Impayés</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dernière consultation --}}
            @php $lastConsult = $patient->consultations->first(); @endphp
            @if($lastConsult)
                <div class="card">
                    <div class="card-header">
                        <h5><i class="feather icon-activity"></i> Dernière Consultation</h5>
                        <div class="card-header-right">
                            <a href="{{ route('consultations.show', $lastConsult) }}" class="btn btn-primary btn-sm">
                                Voir détails
                            </a>
                        </div>
                    </div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date :</strong> {{ $lastConsult->consultation_date->format('d/m/Y H:i') }}</p>
                                <p><strong>Motif :</strong> {{ $lastConsult->reason ?? '—' }}</p>
                                <p><strong>Diagnostic :</strong> {{ $lastConsult->diagnosis ?? '—' }}</p>
                            </div>
                            <div class="col-md-6">
                                @if($lastConsult->weight_kg || $lastConsult->blood_pressure)
                                    <p><strong>Signes vitaux :</strong></p>
                                    <ul>
                                        @if($lastConsult->weight_kg) <li>Poids : {{ $lastConsult->weight_kg }} kg (IMC: {{ $lastConsult->bmi }})</li> @endif
                                        @if($lastConsult->blood_pressure) <li>TA : {{ $lastConsult->blood_pressure }} mmHg</li> @endif
                                        @if($lastConsult->heart_rate) <li>FC : {{ $lastConsult->heart_rate }} bpm</li> @endif
                                        @if($lastConsult->temperature) <li>T° : {{ $lastConsult->temperature }}°C</li> @endif
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- Consultations --}}
        @if($activeTab === 'consultations')
            <div class="card">
                <div class="card-header">
                    <h5>Historique des Consultations</h5>
                    <div class="card-header-right">
                        <a href="{{ route('consultations.create', ['patient' => $patient->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="feather icon-plus"></i> Nouvelle consultation
                        </a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th><th>Type</th><th>Motif</th><th>Médecin</th><th>Statut</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->consultations as $consult)
                                    <tr>
                                        <td>{{ $consult->consultation_date->format('d/m/Y H:i') }}</td>
                                        <td>{{ \App\Models\Appointment::TYPE_LABELS[$consult->consultation_type] ?? $consult->consultation_type }}</td>
                                        <td>{{ Str::limit($consult->reason, 60) ?? '—' }}</td>
                                        <td>{{ $consult->doctor->name }}</td>
                                        <td>
                                            <span class="label {{ $consult->status === 'completed' ? 'label-success' : 'label-warning' }}">
                                                {{ $consult->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('consultations.show', $consult) }}"
                                               class="btn btn-info btn-xs">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucune consultation</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Ordonnances --}}
        @if($activeTab === 'prescriptions')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-file-text"></i> Ordonnances</h5>
                    <div class="card-header-right">
                        <a href="{{ route('prescriptions.create', ['patient' => $patient->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="feather icon-plus"></i> Nouvelle ordonnance
                        </a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>N° Ordonnance</th>
                                    <th>Date</th>
                                    <th>Médecin</th>
                                    <th>Médicaments</th>
                                    <th>Validité</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->prescriptions as $prescription)
                                    <tr>
                                        <td><strong>{{ $prescription->prescription_number }}</strong></td>
                                        <td>{{ $prescription->prescribed_at->format('d/m/Y') }}</td>
                                        <td>{{ $prescription->doctor->name }}</td>
                                        <td>
                                            @foreach($prescription->lines as $line)
                                                <span class="label label-default m-b-2" style="display:inline-block;">{{ $line->medication_name }}</span>
                                            @endforeach
                                            @if($prescription->lines->isEmpty())
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($prescription->valid_until)
                                                <span class="{{ $prescription->valid_until->isPast() ? 'text-danger' : 'text-success' }}">
                                                    {{ $prescription->valid_until->format('d/m/Y') }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('prescriptions.show', $prescription) }}"
                                               class="btn btn-info btn-xs" title="Voir">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                            <a href="{{ route('prescriptions.pdf', $prescription) }}"
                                               class="btn btn-warning btn-xs" title="PDF" target="_blank">
                                                <i class="feather icon-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucune ordonnance</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Analyses --}}
        @if($activeTab === 'lab-tests')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-thermometer"></i> Analyses Biologiques</h5>
                    <div class="card-header-right">
                        <a href="{{ route('lab-tests.create', ['patient' => $patient->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="feather icon-plus"></i> Nouvelle analyse
                        </a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>N° Demande</th>
                                    <th>Date demande</th>
                                    <th>Laboratoire</th>
                                    <th>Résultats</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->labTests as $labTest)
                                    <tr>
                                        <td><strong>{{ $labTest->request_number }}</strong></td>
                                        <td>{{ $labTest->requested_at->format('d/m/Y') }}</td>
                                        <td>{{ $labTest->laboratory_name ?? '—' }}</td>
                                        <td>{{ $labTest->results->count() }} résultat(s)</td>
                                        <td>
                                            <span class="label label-{{ match($labTest->status) {
                                                'completed' => 'success',
                                                'requested' => 'info',
                                                'pending' => 'warning',
                                                'cancelled' => 'danger',
                                                default => 'default'
                                            } }}">
                                                {{ match($labTest->status) {
                                                    'requested' => 'Demandé',
                                                    'pending' => 'En attente',
                                                    'completed' => 'Terminé',
                                                    'cancelled' => 'Annulé',
                                                    default => $labTest->status
                                                } }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('lab-tests.show', $labTest) }}"
                                               class="btn btn-info btn-xs" title="Voir">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                            <a href="{{ route('lab-tests.pdf', $labTest) }}"
                                               class="btn btn-warning btn-xs" title="Imprimer" target="_blank">
                                                <i class="feather icon-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucune analyse</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Imagerie --}}
        @if($activeTab === 'images')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-image"></i> Imagerie Médicale</h5>
                    <div class="card-header-right">
                        <a href="{{ route('medical-images.create', ['patient' => $patient->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="feather icon-plus"></i> Nouvelle imagerie
                        </a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Catégorie</th>
                                    <th>Titre</th>
                                    <th>Partie du corps</th>
                                    <th>Médecin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->medicalImages as $image)
                                    <tr>
                                        <td>{{ $image->image_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            <span class="label label-info">
                                                {{ \App\Models\MedicalImage::CATEGORY_LABELS[$image->category] ?? $image->category }}
                                            </span>
                                        </td>
                                        <td>{{ $image->title }}</td>
                                        <td>{{ $image->body_part ?? '—' }}</td>
                                        <td>{{ $image->doctor->name ?? '—' }}</td>
                                        <td>
                                            <a href="{{ route('medical-images.show', $image) }}"
                                               class="btn btn-info btn-xs" title="Voir">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucune imagerie</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Documents --}}
        @if($activeTab === 'documents')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-folder"></i> Documents</h5>
                    <div class="card-header-right">
                        <a href="{{ route('documents.create', ['patient' => $patient->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="feather icon-plus"></i> Nouveau document
                        </a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Catégorie</th>
                                    <th>Titre</th>
                                    <th>Fichier</th>
                                    <th>Taille</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->documents as $document)
                                    <tr>
                                        <td>{{ $document->document_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            <span class="label label-primary">
                                                {{ \App\Models\Document::CATEGORY_LABELS[$document->category] ?? $document->category }}
                                            </span>
                                        </td>
                                        <td>{{ $document->title }}</td>
                                        <td>{{ $document->file_name ?? '—' }}</td>
                                        <td>{{ $document->file_size_formatted }}</td>
                                        <td>
                                            <a href="{{ $document->file_url }}"
                                               class="btn btn-info btn-xs" title="Télécharger" target="_blank">
                                                <i class="feather icon-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucun document</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Rapports Médicaux --}}
        @if($activeTab === 'reports')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-clipboard"></i> Rapports Médicaux</h5>
                    <div class="card-header-right">
                        <a href="{{ route('medical-reports.create', ['patient' => $patient->id]) }}"
                           class="btn btn-primary btn-sm">
                            <i class="feather icon-plus"></i> Nouveau rapport
                        </a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>N° Rapport</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Titre</th>
                                    <th>Médecin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->medicalReports as $report)
                                    <tr>
                                        <td><strong>{{ $report->report_number }}</strong></td>
                                        <td>{{ $report->report_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            <span class="label label-info">
                                                {{ \App\Models\MedicalReport::TYPE_LABELS[$report->type] ?? $report->type }}
                                            </span>
                                        </td>
                                        <td>{{ Str::limit($report->title, 50) }}</td>
                                        <td>Dr. {{ $report->doctor->name ?? '—' }}</td>
                                        <td>
                                            <a href="{{ route('medical-reports.show', $report) }}"
                                               class="btn btn-info btn-xs" title="Voir">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                            <a href="{{ route('medical-reports.edit', $report) }}"
                                               class="btn btn-warning btn-xs" title="Modifier">
                                                <i class="feather icon-edit"></i>
                                            </a>
                                            <a href="{{ route('medical-reports.pdf', $report) }}"
                                               class="btn btn-primary btn-xs" title="PDF" target="_blank">
                                                <i class="feather icon-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucun rapport médical</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Actes Médicaux --}}
        @if($activeTab === 'acts')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-list"></i> Actes Médicaux Effectués</h5>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>N° Facture</th>
                                    <th>Code</th>
                                    <th>Acte</th>
                                    <th>Qté</th>
                                    <th>Prix Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $acts = $patient->payments->flatMap(function($payment) {
                                        return $payment->lines->filter(fn($line) => $line->medical_act_id)->map(function($line) use ($payment) {
                                            $line->_payment = $payment;
                                            return $line;
                                        });
                                    })->sortByDesc(fn($line) => $line->_payment->payment_date);
                                @endphp
                                @forelse($acts as $line)
                                    <tr>
                                        <td>{{ $line->_payment->payment_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            <strong>{{ $line->_payment->invoice_number }}</strong>
                                        </td>
                                        <td>
                                            <span class="label label-default">{{ $line->medicalAct->code ?? '—' }}</span>
                                        </td>
                                        <td>{{ $line->medicalAct->name ?? $line->description }}</td>
                                        <td class="text-center">{{ $line->quantity }}</td>
                                        <td class="text-right">{{ number_format($line->unit_price, 2) }} MAD</td>
                                        <td class="text-right">{{ number_format($line->total_price, 2) }} MAD</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Aucun acte médical enregistré</td></tr>
                                @endforelse
                            </tbody>
                            @if($acts->isNotEmpty())
                                <tfoot>
                                    <tr class="active">
                                        <td colspan="6" class="text-right"><strong>Total</strong></td>
                                        <td class="text-right"><strong>{{ number_format($acts->sum('total_price'), 2) }} MAD</strong></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Antécédents --}}
        @if($activeTab === 'history')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-clock"></i> Antécédents Médicaux</h5>
                </div>
                <div class="card-block">
                    @php
                        $histories = $patient->medicalHistories->groupBy('type');
                    @endphp

                    @foreach(\App\Models\MedicalHistory::TYPE_LABELS as $type => $label)
                        @if($histories->has($type))
                            <h6 class="m-t-20 text-primary">
                                <i class="feather icon-chevron-right"></i> {{ $label }}
                            </h6>
                            <div class="timeline">
                                @foreach($histories[$type] as $hist)
                                    <div class="card m-b-5" style="border-left: 3px solid #01a9ac;">
                                        <div class="card-block p-10">
                                            <strong>{{ $hist->title }}</strong>
                                            @if($hist->date_occurred)
                                                <small class="text-muted pull-right">{{ $hist->date_occurred->format('d/m/Y') }}</small>
                                            @endif
                                            @if($hist->description)
                                                <p class="text-muted m-b-0 m-t-5">{{ $hist->description }}</p>
                                            @endif
                                            @if($hist->type === 'vaccination' && $hist->vaccine_name)
                                                <p class="m-b-0">
                                                    Vaccin: {{ $hist->vaccine_name }}
                                                    @if($hist->vaccine_date) — Le {{ $hist->vaccine_date->format('d/m/Y') }} @endif
                                                    @if($hist->vaccine_next_date) — Rappel: {{ $hist->vaccine_next_date->format('d/m/Y') }} @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    @if($patient->medicalHistories->isEmpty())
                        <p class="text-muted text-center p-20">Aucun antécédent enregistré.</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Paiements --}}
        @if($activeTab === 'payments')
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-credit-card"></i> Historique des Paiements</h5>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>N° Facture</th><th>Date</th><th>Total</th><th>Payé</th><th>Reste</th><th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->payments as $payment)
                                    <tr>
                                        <td><strong>{{ $payment->invoice_number }}</strong></td>
                                        <td>{{ $payment->payment_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>{{ number_format($payment->total_amount, 2) }} MAD</td>
                                        <td class="text-success">{{ number_format($payment->amount_paid, 2) }} MAD</td>
                                        <td class="{{ $payment->amount_due > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($payment->amount_due, 2) }} MAD
                                        </td>
                                        <td>
                                            <span class="label label-{{ match($payment->status) {
                                                'paid' => 'success', 'pending' => 'warning',
                                                'partial' => 'info', default => 'danger'
                                            } }}">
                                                {{ \App\Models\Payment::STATUS_LABELS[$payment->status] ?? $payment->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucun paiement</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif


    </div>
</div>
