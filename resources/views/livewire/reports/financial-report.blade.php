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
            <h5><i class="feather icon-bar-chart-2"></i> Rapport Financier <small class="text-muted">({{ $periodLabel }})</small></h5>
            <div class="card-header-right">
                @if($canExport)
                    <button type="button" class="btn btn-success btn-sm" wire:click="export" wire:loading.attr="disabled" wire:target="export">
                        <i class="feather icon-download"></i> Exporter Excel
                    </button>
                @endif
                <a href="{{ route('home') }}" class="btn btn-primary btn-sm">
                    <i class="feather icon-home"></i> Tableau de bord
                </a>
            </div>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Période</label>
                        <select wire:model.live="periodType" class="form-control">
                            @foreach(\App\Livewire\Reports\FinancialReport::PERIOD_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Date début</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Date fin</label>
                        <input type="date" wire:model.live="dateTo" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Médecin</label>
                        <select wire:model.live="doctorFilter" class="form-control" @if(\App\Models\Payment::restrictedToOwn(auth()->user())) disabled @endif>
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
                    <h6 class="m-b-20 text-white">CA encaissé (période)</h6>
                    <h2 class="text-right text-white"><i class="feather icon-trending-up f-left"></i>
                        <span>{{ number_format($stats['total_revenue'], 2, ',', ' ') }} MAD</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $stats['total_invoices'] }} factures — moy. {{ number_format($stats['avg_invoice'], 2, ',', ' ') }} MAD</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-green order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">CA de l'année</h6>
                    <h2 class="text-right text-white"><i class="feather icon-calendar f-left"></i>
                        <span>{{ number_format($caYear, 2, ',', ' ') }} MAD</span>
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
                    <h6 class="m-b-20 text-white">Reste à encaisser (période)</h6>
                    <h2 class="text-right text-white"><i class="feather icon-alert-circle f-left"></i>
                        <span>{{ number_format($stats['total_pending'], 2, ',', ' ') }} MAD</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">{{ $stats['pending_count'] }} impayées · {{ $stats['partial_count'] }} partielles</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-c-pink order-card">
                <div class="card-block">
                    <h6 class="m-b-20 text-white">Nombre de factures</h6>
                    <h2 class="text-right text-white"><i class="feather icon-file-text f-left"></i>
                        <span>{{ $totalInvoices }}</span>
                    </h2>
                    <p class="m-b-0 text-white">
                        <span class="f-right">Toutes périodes (hors annulées)</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row m-t-10">
        {{-- Revenue chart --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-bar-chart"></i> Évolution du CA encaissé (par {{ $revenueSeries['unit'] }})</h5>
                </div>
                <div class="card-block" wire:ignore>
                    <canvas id="monthlyRevenueChart" height="110"></canvas>
                </div>
            </div>
        </div>

        {{-- By payment method --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="feather icon-pie-chart"></i> Par mode de paiement</h5>
                </div>
                <div class="card-block">
                    <table class="table table-condensed">
                        <thead><tr><th>Mode</th><th class="text-right">Factures</th><th class="text-right">Encaissé</th></tr></thead>
                        <tbody>
                            @forelse($paymentsByMethod as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">{{ $row['count'] }}</td>
                                    <td class="text-right">{{ number_format($row['total'], 2, ',', ' ') }} MAD</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Aucune donnée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($stats['total_insurance'] > 0)
                        <p class="text-muted small m-b-0">Part assurance facturée (non incluse dans l'encaissé) : {{ number_format($stats['total_insurance'], 2, ',', ' ') }} MAD</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Payments Table --}}
    <div class="card m-t-10">
        <div class="card-header">
            <h5><i class="feather icon-credit-card"></i> Derniers paiements de la période</h5>
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
                            <th>Payé</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr wire:key="recent-{{ $payment->id }}">
                                <td><strong>{{ $payment->invoice_number }}</strong></td>
                                <td>{{ $payment->patient?->full_name ?? '—' }}</td>
                                <td>{{ $payment->doctor ? 'Dr. '.$payment->doctor->name : '—' }}</td>
                                <td><strong>{{ number_format($payment->total_amount, 2, ',', ' ') }} MAD</strong></td>
                                <td>{{ number_format($payment->amount_paid, 2, ',', ' ') }} MAD</td>
                                <td><span class="label {{ $statusColors[$payment->status] ?? 'label-default' }}">{{ $statusLabels[$payment->status] ?? $payment->status }}</span></td>
                                <td>{{ $payment->payment_date?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-20">Aucun paiement sur la période.</td>
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
        var series = @json($revenueSeries);
        var ctx = document.getElementById('monthlyRevenueChart');
        if (!ctx) return;

        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: series.labels || [],
                datasets: [{
                    label: 'CA encaissé (MAD)',
                    data: series.data || [],
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
                                return Number(value).toLocaleString('fr-FR') + ' MAD';
                            }
                        }
                    }
                }
            }
        });

        // Mise à jour du graphique quand les filtres changent
        Livewire.on('financial-chart-updated', function (event) {
            var s = (event && event.series) || (Array.isArray(event) && event[0] && event[0].series) || { labels: [], data: [] };
            chart.data.labels = s.labels;
            chart.data.datasets[0].data = s.data;
            chart.update();
        });
    });
    </script>
    @endpush
</div>
