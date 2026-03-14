<div wire:poll.30000ms>
    {{-- Statistiques du jour --}}
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-c-blue">
                <div class="card-block text-white text-center">
                    <h2>{{ $this->todayStats['total'] }}</h2>
                    <p><i class="feather icon-calendar"></i> Total RDV du jour</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-c-yellow">
                <div class="card-block text-white text-center">
                    <h2>{{ $this->todayStats['waiting'] }}</h2>
                    <p><i class="feather icon-clock"></i> En attente</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-c-green">
                <div class="card-block text-white text-center">
                    <h2>{{ $this->todayStats['completed'] }}</h2>
                    <p><i class="feather icon-check-circle"></i> Consultés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-c-pink">
                <div class="card-block text-white text-center">
                    <h2>{{ $this->todayStats['cancelled'] }}</h2>
                    <p><i class="feather icon-x-circle"></i> Annulés</p>
                </div>
            </div>
        </div>
    </div>

    {{-- File d'attente --}}
    <div class="card">
        <div class="card-header">
            <h5>
                <i class="feather icon-users"></i> File d'attente
                <span class="label label-warning m-l-5">{{ $this->waitingPatients->count() }} patient(s)</span>
            </h5>
            <div class="card-header-right">
                <small class="text-muted">
                    <i class="feather icon-refresh-cw"></i> Actualisation automatique toutes les 30 sec.
                </small>
            </div>
        </div>
        <div class="card-block">
            @forelse($this->waitingPatients as $index => $appt)
                <div class="card m-b-10" style="border-left: 4px solid {{ $appt->status === 'in_progress' ? '#01a9ac' : '#f4a100' }};">
                    <div class="card-block">
                        <div class="row align-items-center">
                            <div class="col-md-1 text-center">
                                <h2 style="color: {{ $appt->status === 'in_progress' ? '#01a9ac' : '#f4a100' }};">
                                    {{ $index + 1 }}
                                </h2>
                            </div>
                            <div class="col-md-5">
                                <h5 class="m-0">{{ $appt->patient->full_name }}</h5>
                                <small class="text-muted">{{ $appt->patient->dossier_number }}</small>
                                <br>
                                <span class="label label-{{ $appt->status === 'in_progress' ? 'success' : 'warning' }}">
                                    {{ $appt->status === 'in_progress' ? 'En consultation' : 'En attente' }}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <p class="m-0"><strong>RDV :</strong> {{ $appt->scheduled_at->format('H:i') }}</p>
                                @if($appt->arrived_at)
                                    <p class="m-0"><strong>Arrivé :</strong> {{ $appt->arrived_at->format('H:i') }}</p>
                                    <small class="text-muted">Attente : {{ $appt->arrived_at->diffForHumans() }}</small>
                                @endif
                            </div>
                            <div class="col-md-3 text-right">
                                @if($appt->status === 'waiting')
                                    <button wire:click="callNext({{ $appt->id }})"
                                            class="btn btn-success btn-sm">
                                        <i class="feather icon-arrow-right"></i> Appeler
                                    </button>
                                @else
                                    <a href="{{ route('consultations.create', ['appointment' => $appt->id]) }}"
                                       class="btn btn-primary btn-sm">
                                        <i class="feather icon-activity"></i> Consulter
                                    </a>
                                    <button wire:click="markCompleted({{ $appt->id }})"
                                            class="btn btn-info btn-sm m-t-5">
                                        <i class="feather icon-check"></i> Terminé
                                    </button>
                                @endif
                                <button wire:click="markNoShow({{ $appt->id }})"
                                        class="btn btn-danger btn-sm m-t-5">
                                    <i class="feather icon-user-x"></i> Absent
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center p-30">
                    <i class="feather icon-smile" style="font-size:3em;color:#01a9ac;"></i>
                    <h4 class="m-t-15">Salle d'attente vide</h4>
                    <p class="text-muted">Aucun patient en attente pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
