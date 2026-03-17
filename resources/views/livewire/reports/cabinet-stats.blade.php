<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-pie-chart"></i> Statistiques du Cabinet</h5>
            <div class="card-header-right">
                <a href="{{ route('home') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-home"></i> Tableau de bord
                </a>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-blue order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Total Patients</h6>
                    <h2 class="text-right text-white">
                        <i class="feather icon-users f-left"></i>
                        <span>{{ $totalPatients ?? 0 }}</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">+{{ $newPatientsThisMonth ?? 0 }} ce mois</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-green order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Consultations (mois)</h6>
                    <h2 class="text-right text-white">
                        <i class="feather icon-activity f-left"></i>
                        <span>{{ $consultationsThisMonth ?? 0 }}</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $consultationsThisYear ?? 0 }} cette année</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-yellow order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Taux annulations RDV</h6>
                    <h2 class="text-right text-white">
                        <i class="feather icon-x-circle f-left"></i>
                        <span>{{ number_format($cancellationRate ?? 0, 1) }}%</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $cancelledAppointments ?? 0 }} annulés</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-pink order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Rendez-vous (mois)</h6>
                    <h2 class="text-right text-white">
                        <i class="feather icon-calendar f-left"></i>
                        <span>{{ $appointmentsThisMonth ?? 0 }}</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $pendingAppointments ?? 0 }} en attente</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-pie-chart"></i> Consultations par type</h5>
                </div>
                <div class="card-block text-center">
                    <canvas id="consultationTypeChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-bar-chart-2"></i> Nouveaux patients / mois</h5>
                </div>
                <div class="card-block">
                    <canvas id="newPatientsChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Medications & Lab Tests --}}
    <div class="row m-t-10">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-package"></i> Top 5 Médicaments prescrits</h5>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Médicament</th>
                                    <th>Nb prescriptions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topMedications ?? [] as $i => $med)
                                    <tr>
                                        <td><span class="label label-info">{{ $i + 1 }}</span></td>
                                        <td>{{ $med->medication_name ?? '—' }}</td>
                                        <td><strong>{{ $med->total ?? 0 }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Aucune donnée disponible.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-activity"></i> Top 5 Analyses demandées</h5>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type d'analyse</th>
                                    <th>Nb demandes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topLabTests ?? [] as $i => $test)
                                    <tr>
                                        <td><span class="label label-success">{{ $i + 1 }}</span></td>
                                        <td>{{ $test->parameter_name ?? '—' }}</td>
                                        <td><strong>{{ $test->total ?? 0 }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Aucune donnée disponible.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('livewire:initialized', function () {

        // Consultations by Type — Doughnut
        var consultationTypeData = @json($consultationsByType ?? ['labels' => [], 'data' => []]);
        var ctxDoughnut = document.getElementById('consultationTypeChart');
        if (ctxDoughnut && consultationTypeData.labels && consultationTypeData.labels.length) {
            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: consultationTypeData.labels,
                    datasets: [{
                        data: consultationTypeData.data,
                        backgroundColor: ['#4099ff','#2ed8b6','#ffb64d','#ff5370','#7759de','#a389d4'],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // New Patients per Month — Bar
        var newPatientsData = @json($newPatientsPerMonth ?? []);
        var ctxBar = document.getElementById('newPatientsChart');
        if (ctxBar && newPatientsData.length) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: newPatientsData.map(function (d) { return d.label; }),
                    datasets: [{
                        label: 'Nouveaux patients',
                        data: newPatientsData.map(function (d) { return d.value; }),
                        backgroundColor: 'rgba(46,216,182,0.7)',
                        borderColor: '#2ed8b6',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    });
    </script>
    @endpush
</div>
