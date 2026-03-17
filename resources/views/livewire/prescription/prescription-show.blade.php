<div>
    {{-- Header --}}
    <div class="card">
        <div class="card-block">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="m-b-5">
                        <i class="feather icon-file-text text-primary"></i>
                        {{ $prescription->prescription_number }}
                    </h4>
                    <p class="text-muted m-b-0">
                        Patient : <strong>{{ $prescription->patient->full_name }}</strong>
                        &mdash; {{ $prescription->patient->dossier_number }}
                    </p>
                    <p class="text-muted m-b-0">
                        Date : {{ $prescription->prescribed_at
                            ? \Carbon\Carbon::parse($prescription->prescribed_at)->format('d/m/Y')
                            : $prescription->created_at->format('d/m/Y') }}
                        @if($prescription->is_printed)
                            <span class="label label-success m-l-10">
                                <i class="feather icon-printer"></i> Imprimée
                            </span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('prescriptions.pdf', $prescription->id) }}"
                       target="_blank" class="btn btn-primary btn-sm m-r-5">
                        <i class="feather icon-printer"></i> Imprimer PDF
                    </a>
                    <a href="{{ url()->previous() }}" class="btn btn-default btn-sm">
                        <i class="feather icon-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Info Médecin --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5><i class="feather icon-user"></i> Médecin</h5></div>
                <div class="card-block">
                    <table class="table table-condensed m-b-0">
                        <tr>
                            <th style="width:40%">Nom</th>
                            <td>Dr. {{ $prescription->doctor->name }}</td>
                        </tr>
                        @if($prescription->doctor->speciality)
                        <tr>
                            <th>Spécialité</th>
                            <td>{{ $prescription->doctor->speciality }}</td>
                        </tr>
                        @endif
                        @if($prescription->doctor->license_number)
                        <tr>
                            <th>N° Inpe</th>
                            <td>{{ $prescription->doctor->license_number }}</td>
                        </tr>
                        @endif
                        @if($prescription->doctor->phone)
                        <tr>
                            <th>Téléphone</th>
                            <td>{{ $prescription->doctor->phone }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Info Patient --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5><i class="feather icon-users"></i> Patient</h5></div>
                <div class="card-block">
                    <table class="table table-condensed m-b-0">
                        <tr>
                            <th style="width:40%">Nom</th>
                            <td>{{ $prescription->patient->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Date naissance</th>
                            <td>{{ $prescription->patient->birth_date
                                ? \Carbon\Carbon::parse($prescription->patient->birth_date)->format('d/m/Y')
                                : '-' }}
                                @if($prescription->patient->age)
                                    ({{ $prescription->patient->age }} ans)
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td>{{ $prescription->patient->phone ?? '-' }}</td>
                        </tr>
                        @if($prescription->patient->known_allergies)
                        <tr>
                            <th>Allergies</th>
                            <td><span class="text-danger">{{ $prescription->patient->known_allergies }}</span></td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Lignes de médicaments --}}
    <div class="card">
        <div class="card-header">
            <h5>
                <span style="font-size:1.4em;font-weight:700;font-family:serif;margin-right:10px;">&#8478;</span>
                Médicaments prescrits
            </h5>
        </div>
        <div class="card-block">
            @if($prescription->lines->isEmpty())
                <p class="text-center text-muted p-20">Aucun médicament sur cette ordonnance.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Médicament</th>
                                <th>Forme</th>
                                <th>Dosage</th>
                                <th>Fréquence</th>
                                <th>Durée</th>
                                <th>Voie</th>
                                <th>Instructions</th>
                                <th>Qté</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($prescription->lines->sortBy('sort_order') as $i => $line)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $line->medication_name }}</strong>
                                    @if($line->medication)
                                        <br><small class="text-muted">{{ $line->medication->generic_name }}</small>
                                    @endif
                                </td>
                                <td>{{ $line->medication->dosage_form ?? '-' }}</td>
                                <td>{{ $line->dosage ?? '-' }}</td>
                                <td>{{ $line->frequency ?? '-' }}</td>
                                <td>{{ $line->duration_days ? $line->duration_days.' j' : '-' }}</td>
                                <td>{{ $line->route ?? '-' }}</td>
                                <td>{{ $line->instructions ?? '-' }}</td>
                                <td>{{ $line->quantity ?? '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Notes --}}
    @if($prescription->notes)
    <div class="card">
        <div class="card-header"><h5>Notes</h5></div>
        <div class="card-block">
            <p style="white-space:pre-wrap;">{{ $prescription->notes }}</p>
        </div>
    </div>
    @endif
</div>
