<div>
    {{-- Cartes de statistiques --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="card bg-c-blue">
                <div class="card-block">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-white">{{ $this->todayAppointments }}</h4>
                            <h6 class="text-white m-b-0">RDV aujourd'hui</h6>
                        </div>
                        <div class="col-4 text-right">
                            <i class="feather icon-calendar" style="font-size:2.5em;color:rgba(255,255,255,0.5);"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="background:rgba(0,0,0,0.1);">
                    <a href="{{ route('appointments.index') }}" class="text-white">
                        Voir tous les RDV <i class="feather icon-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card bg-c-yellow">
                <div class="card-block">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-white">{{ $this->todayWaiting }}</h4>
                            <h6 class="text-white m-b-0">En salle d'attente</h6>
                        </div>
                        <div class="col-4 text-right">
                            <i class="feather icon-users" style="font-size:2.5em;color:rgba(255,255,255,0.5);"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="background:rgba(0,0,0,0.1);">
                    <a href="{{ route('appointments.waiting-room') }}" class="text-white">
                        Salle d'attente <i class="feather icon-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card bg-c-green">
                <div class="card-block">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-white">{{ $this->totalPatients }}</h4>
                            <h6 class="text-white m-b-0">Total patients</h6>
                        </div>
                        <div class="col-4 text-right">
                            <i class="feather icon-user" style="font-size:2.5em;color:rgba(255,255,255,0.5);"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="background:rgba(0,0,0,0.1);">
                    <a href="{{ route('patients.index') }}" class="text-white">
                        +{{ $this->newPatientsThisMonth }} ce mois <i class="feather icon-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card bg-c-pink">
                <div class="card-block">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-white">{{ number_format($this->monthlyRevenue, 0) }}</h4>
                            <h6 class="text-white m-b-0">CA ce mois (MAD)</h6>
                        </div>
                        <div class="col-4 text-right">
                            <i class="feather icon-trending-up" style="font-size:2.5em;color:rgba(255,255,255,0.5);"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="background:rgba(0,0,0,0.1);">
                    <a href="{{ route('reports.financial') }}" class="text-white">
                        Impayés: {{ number_format($this->pendingPayments, 0) }} MAD <i class="feather icon-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Graphique consultations --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-bar-chart-2"></i> Consultations — 30 derniers jours</h5>
                </div>
                <div class="card-block">
                    <canvas id="consultationsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Prochains RDV --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-clock"></i> Prochains RDV</h5>
                </div>
                <div class="card-block p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($this->upcomingAppointments as $appt)
                            <li class="list-group-item" style="border-left: 3px solid {{ $appt->color ?? '#01a9ac' }};">
                                <div class="row">
                                    <div class="col-7">
                                        <strong>{{ $appt->patient->full_name }}</strong>
                                        <small class="text-muted d-block">{{ $appt->patient->dossier_number }}</small>
                                    </div>
                                    <div class="col-5 text-right">
                                        <small class="text-primary">{{ $appt->scheduled_at->format('d/m H:i') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $appt->scheduled_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Aucun RDV à venir</li>
                        @endforelse
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('appointments.index') }}">Voir tous les RDV</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Récentes consultations --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-activity"></i> Consultations récentes</h5>
                    <div class="card-header-right">
                        <a href="{{ route('consultations.index') }}" class="btn btn-primary btn-sm">Voir tout</a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>Patient</th><th>Médecin</th><th>Date</th><th>Motif</th><th>Statut</th></tr>
                            </thead>
                            <tbody>
                                @forelse($this->recentConsultations as $consult)
                                    <tr>
                                        <td>
                                            <a href="{{ route('patients.show', $consult->patient) }}">
                                                <strong>{{ $consult->patient->full_name }}</strong>
                                            </a>
                                        </td>
                                        <td>{{ $consult->doctor->name }}</td>
                                        <td>{{ $consult->consultation_date->format('d/m/Y H:i') }}</td>
                                        <td>{{ Str::limit($consult->reason, 50) ?? '—' }}</td>
                                        <td>
                                            <span class="label label-{{ $consult->status === 'completed' ? 'success' : 'warning' }}">
                                                {{ $consult->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Aucune consultation récente</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('consultationsChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($this->monthlyConsultationsChart['labels']),
            datasets: [{
                label: 'Consultations',
                data: @json($this->monthlyConsultationsChart['values']),
                borderColor: '#01a9ac',
                backgroundColor: 'rgba(1, 169, 172, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>
@endpush
