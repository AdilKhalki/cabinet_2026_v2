<div>
    <div class="row">
        {{-- ── Colonne gauche : infos + actions ──────────────────── --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-block">
                    <h6 class="text-muted m-b-5">Numéro</h6>
                    <p class="f-w-700">{{ $report->report_number }}</p>

                    <h6 class="text-muted m-b-5">Type</h6>
                    <p>
                        <span class="label label-primary">
                            {{ $typeLabels[$report->type] ?? $report->type }}
                        </span>
                    </p>

                    <h6 class="text-muted m-b-5">Patient</h6>
                    <p>
                        <a href="{{ route('patients.show', $report->patient) }}">
                            {{ $report->patient->full_name }}
                        </a>
                        <span class="text-muted small d-block">{{ $report->patient->dossier_number }}</span>
                    </p>

                    <h6 class="text-muted m-b-5">Médecin</h6>
                    <p>Dr. {{ $report->doctor->name ?? '—' }}</p>

                    <h6 class="text-muted m-b-5">Date du rapport</h6>
                    <p>{{ $report->report_date?->format('d/m/Y') ?? '—' }}</p>

                    @if($report->type === 'sick_leave')
                        <h6 class="text-muted m-b-5">Arrêt de travail</h6>
                        <p>
                            @if($report->sick_leave_days)
                                {{ $report->sick_leave_days }} jour(s)
                            @endif
                            @if($report->valid_from)
                                <span class="text-muted small d-block">
                                    Du {{ $report->valid_from->format('d/m/Y') }}
                                    @if($report->valid_until)
                                        au {{ $report->valid_until->format('d/m/Y') }}
                                    @endif
                                </span>
                            @endif
                        </p>
                    @elseif($report->valid_until)
                        <h6 class="text-muted m-b-5">Validité</h6>
                        <p>
                            @if($report->valid_from)Du {{ $report->valid_from->format('d/m/Y') }} au @endif
                            {{ $report->valid_until->format('d/m/Y') }}
                        </p>
                    @endif

                    @if($report->consultation)
                        <h6 class="text-muted m-b-5">Consultation liée</h6>
                        <p>
                            <a href="{{ route('consultations.show', $report->consultation) }}">
                                #{{ $report->consultation->id }} —
                                {{ $report->consultation->consultation_date?->format('d/m/Y') }}
                            </a>
                        </p>
                    @endif

                    @if($report->is_printed)
                        <span class="label label-success"><i class="feather icon-printer"></i> Imprimé</span>
                    @endif
                </div>

                <div class="card-footer">
                    <a href="{{ route('medical-reports.pdf', $report) }}"
                       target="_blank" class="btn btn-info btn-block m-b-10">
                        <i class="feather icon-download"></i> Télécharger PDF
                    </a>
                    <a href="{{ route('medical-reports.edit', $report) }}"
                       class="btn btn-warning btn-block m-b-10">
                        <i class="feather icon-edit"></i> Modifier
                    </a>
                    <a href="{{ url()->previous() }}" class="btn btn-default btn-block">
                        <i class="feather icon-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- ── Colonne droite : contenu du rapport ────────────────── --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-file-text"></i> {{ $report->title }}</h5>
                </div>
                <div class="card-block">
                    <div style="white-space: pre-wrap; font-family: inherit; line-height: 1.8; padding: 10px;">{{ $report->content }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
