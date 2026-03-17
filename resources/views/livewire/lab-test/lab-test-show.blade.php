<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Patient Info Header --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-activity"></i> Résultat d'Analyse — {{ $labTest->request_number ?? 'N/A' }}</h5>
            <div class="card-header-right">
                <a href="{{ route('lab-tests.index') }}" class="btn btn-default btn-sm">
                    <i class="feather icon-arrow-left"></i> Retour
                </a>
                @if($labTest->pdf_path)
                    <a href="{{ Storage::url($labTest->pdf_path) }}" target="_blank" class="btn btn-info btn-sm">
                        <i class="feather icon-file"></i> Voir PDF
                    </a>
                @endif
                <a href="{{ route('lab-tests.pdf', $labTest) }}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="feather icon-printer"></i> Imprimer demande
                </a>
                <a href="{{ route('lab-tests.edit', $labTest) }}" class="btn btn-warning btn-sm">
                    <i class="feather icon-edit"></i> Modifier
                </a>
            </div>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-md-3">
                    <strong><i class="feather icon-user"></i> Patient :</strong><br>
                    {{ $labTest->patient->full_name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong><i class="feather icon-user-check"></i> Médecin prescripteur :</strong><br>
                    {{ $labTest->doctor->full_name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong><i class="feather icon-calendar"></i> Date de demande :</strong><br>
                    {{ $labTest->requested_at ? \Carbon\Carbon::parse($labTest->requested_at)->format('d/m/Y') : '—' }}
                </div>
                <div class="col-md-3">
                    <strong><i class="feather icon-check-circle"></i> Date des résultats :</strong><br>
                    {{ $labTest->resulted_at ? \Carbon\Carbon::parse($labTest->resulted_at)->format('d/m/Y') : '—' }}
                </div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-3">
                    <strong>Laboratoire :</strong><br>
                    {{ $labTest->laboratory_name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong>Statut :</strong><br>
                    @php
                        $statusColors = ['pending'=>'label-warning','in_progress'=>'label-info','completed'=>'label-success','cancelled'=>'label-danger'];
                        $statusLabels = ['pending'=>'En attente','in_progress'=>'En cours','completed'=>'Complété','cancelled'=>'Annulé'];
                        $sc = $statusColors[$labTest->status] ?? 'label-default';
                        $sl = $statusLabels[$labTest->status] ?? $labTest->status;
                    @endphp
                    <span class="label {{ $sc }}">{{ $sl }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Informations cliniques :</strong><br>
                    {{ $labTest->clinical_info ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Results Table grouped by category --}}
    @if($labTest->results && $labTest->results->count())
        @php
            $grouped = $labTest->results->groupBy('category');
        @endphp
        @foreach($grouped as $category => $results)
            <div class="card m-t-15">
                <div class="card-header">
                    <h6><i class="feather icon-layers"></i> {{ $category ?: 'Général' }}</h6>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr class="bg-c-blue">
                                    <th class="text-white">Paramètre</th>
                                    <th class="text-white">Valeur</th>
                                    <th class="text-white">Unité</th>
                                    <th class="text-white">Valeurs normales</th>
                                    <th class="text-white">Statut</th>
                                    <th class="text-white">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    @php
                                        $rowClass = '';
                                        $flagLabel = 'label-success';
                                        $flagText = 'Normal';
                                        if ($result->flag === 'critical') { $rowClass = 'danger'; $flagLabel = 'label-danger'; $flagText = 'Critique'; }
                                        elseif ($result->flag === 'high') { $rowClass = 'warning'; $flagLabel = 'label-warning'; $flagText = 'Élevé'; }
                                        elseif ($result->flag === 'low') { $rowClass = 'warning'; $flagLabel = 'label-info'; $flagText = 'Bas'; }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td><strong>{{ $result->parameter_name }}</strong></td>
                                        <td>{{ $result->value }}</td>
                                        <td>{{ $result->unit ?? '—' }}</td>
                                        <td>
                                            @if($result->normal_min !== null && $result->normal_max !== null)
                                                {{ $result->normal_min }} – {{ $result->normal_max }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td><span class="label {{ $flagLabel }}">{{ $flagText }}</span></td>
                                        <td>{{ $result->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card m-t-15">
            <div class="card-block">
                <p class="text-muted text-center p-20">Aucun résultat disponible.</p>
            </div>
        </div>
    @endif

    {{-- Doctor Interpretation --}}
    <div class="card m-t-15">
        <div class="card-header">
            <h5><i class="feather icon-file-text"></i> Interprétation médicale</h5>
        </div>
        <div class="card-block">
            @if($labTest->doctor_interpretation)
                <div style="white-space: pre-wrap; font-family: inherit; background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #4099ff;">{{ $labTest->doctor_interpretation }}</div>
            @else
                <p class="text-muted">Aucune interprétation renseignée.</p>
            @endif
        </div>
    </div>

    {{-- Evolution Charts --}}
    @if(!empty($this->evolutionData))
        <div class="card m-t-15">
            <div class="card-header">
                <h5><i class="feather icon-trending-up"></i> Évolution des paramètres</h5>
            </div>
            <div class="card-block">
                <div class="row">
                    @foreach($this->evolutionData as $paramName => $data)
                        <div class="col-md-6 m-b-20">
                            <h6>{{ $paramName }} @if(!empty($data['unit']))<small class="text-muted">({{ $data['unit'] }})</small>@endif</h6>
                            <canvas id="chart-{{ Str::slug($paramName) }}" height="120"></canvas>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            var evolutionData = @json($this->evolutionData);
            Object.keys(evolutionData).forEach(function (paramName) {
                var data = evolutionData[paramName];
                var slug = paramName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                var canvas = document.getElementById('chart-' + slug);
                if (!canvas) return;
                var datasets = [{
                    label: paramName,
                    data: data.values,
                    borderColor: '#4099ff',
                    backgroundColor: 'rgba(64,153,255,0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#4099ff',
                    pointRadius: 4,
                }];
                if (data.normal_min !== undefined && data.normal_max !== undefined) {
                    datasets.push({
                        label: 'Limite basse',
                        data: data.labels.map(function () { return data.normal_min; }),
                        borderColor: '#2ed8b6',
                        borderDash: [5, 5],
                        borderWidth: 1,
                        pointRadius: 0,
                        fill: false,
                    });
                    datasets.push({
                        label: 'Limite haute',
                        data: data.labels.map(function () { return data.normal_max; }),
                        borderColor: '#ff5370',
                        borderDash: [5, 5],
                        borderWidth: 1,
                        pointRadius: 0,
                        fill: false,
                    });
                }
                new Chart(canvas, {
                    type: 'line',
                    data: { labels: data.labels, datasets: datasets },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: false } }
                    }
                });
            });
        })();
        </script>
        @endpush
    @endif
</div>
