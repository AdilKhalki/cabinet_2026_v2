<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-bar-chart-2"></i> Rapport Financier</h5>
            <div class="card-header-right">
                <button class="btn btn-default btn-sm" disabled title="Export à implémenter">
                    <i class="feather icon-download"></i> Exporter <small class="text-muted">(à implémenter)</small>
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-home"></i> Tableau de bord
                </a>
            </div>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date début</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date fin</label>
                        <input type="date" wire:model.live="dateTo" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Médecin</label>
                        <select wire:model.live="doctorFilter" class="form-control">
                            <option value="">-- Tous les médecins --</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" wire:click="resetFilters" class="btn btn-default">
                        <i class="feather icon-refresh-cw"></i> Réinitialiser
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-blue order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">CA du mois</h6>
                    <h2 class="text-right text-white"><i class="feather icon-trending-up f-left"></i>
                        <span>{{ number_format($caMonth ?? 0, 2) }} MAD</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $invoicesThisMonth ?? 0 }} factures</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-green order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">CA de l'année</h6>
                    <h2 class="text-right text-white"><i class="feather icon-calendar f-left"></i>
                        <span>{{ number_format($caYear ?? 0, 2) }} MAD</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">Année {{ date('Y') }}</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-yellow order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Impayés total</h6>
                    <h2 class="text-right text-white"><i class="feather icon-alert-circle f-left"></i>
                        <span>{{ number_format($totalUnpaid ?? 0, 2) }} MAD</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $countUnpaid ?? 0 }} factures</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-pink order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Nombre de factures</h6>
                    <h2 class="text-right text-white"><i class="feather icon-file-text f-left"></i>
                        <span>{{ $totalInvoices ?? 0 }}</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">Toutes périodes</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Revenue Bar Chart --}}
    <div class="card m-t-10">
        <div class="card-header">
            <h5><i class="feather icon-bar-chart"></i> Chiffre d'affaires mensuel (12 derniers mois)</h5>
        </div>
        <div class="card-block">
            <canvas id="monthlyRevenueChart" height="80"></canvas>
        </div>
    </div>

    {{-- Recent Payments Table --}}
    <div class="card m-t-10">
        <div class="card-header">
            <h5><i class="feather icon-credit-card"></i> Paiements récents</h5>
        </div>
        <div class="card-block">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments ?? [] as $payment)
                            @php
                                $statusColors = ['paid'=>'label-success','pending'=>'label-warning','partial'=>'label-info','cancelled'=>'label-danger'];
                                $statusLabels = ['paid'=>'Payé','pending'=>'En attente','partial'=>'Partiel','cancelled'=>'Annulé'];
                                $sc = $statusColors[$payment->status] ?? 'label-default';
                                $sl = $statusLabels[$payment->status] ?? $payment->status;
                            @endphp
                            <tr>
                                <td><strong>{{ $payment->invoice_number ?? ('INV-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT)) }}</strong></td>
                                <td>{{ $payment->patient->full_name ?? '—' }}</td>
                                <td>{{ $payment->doctor->full_name ?? '—' }}</td>
                                <td><strong>{{ number_format($payment->total_amount ?? 0, 2) }} MAD</strong></td>
                                <td><span class="label {{ $sc }}">{{ $sl }}</span></td>
                                <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-20">Aucun paiement récent.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('livewire:initialized', function () {
        var monthlyData = @json($monthlyRevenueData ?? ['labels' => [], 'data' => []]);
        var labels = Array.isArray(monthlyData.labels) ? monthlyData.labels : [];
        var values = Array.isArray(monthlyData.data)   ? monthlyData.data   : [];

        var ctx = document.getElementById('monthlyRevenueChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'CA mensuel (MAD)',
                        data: values,
                        backgroundColor: 'rgba(64,153,255,0.7)',
                        borderColor: '#4099ff',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' ' + Number(context.parsed.y).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' MAD';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString('fr-FR') + ' MAD';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
    @endpush
</div>
