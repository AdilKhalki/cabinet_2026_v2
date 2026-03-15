<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Formulaire créer/modifier (panneau collapsible) ─────────────────── --}}
    @if($showForm)
    <div class="card" style="border-left: 4px solid #4099ff;">
        <div class="card-header">
            <h5>
                <i class="feather icon-calendar"></i>
                {{ $editingId ? 'Modifier le rendez-vous' : 'Nouveau rendez-vous' }}
            </h5>
            <div class="card-header-right">
                <button type="button" wire:click="closeForm" class="btn btn-default btn-sm">
                    <i class="feather icon-x"></i> Fermer
                </button>
            </div>
        </div>
        <div class="card-block">
            <form wire:submit="save">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Patient <span class="text-danger">*</span></label>
                            <select class="form-control" wire:model="patient_id">
                                <option value="">-- Sélectionner --</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">
                                        {{ $patient->full_name }} ({{ $patient->dossier_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('patient_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Médecin <span class="text-danger">*</span></label>
                            <select class="form-control" wire:model="doctor_id">
                                <option value="">-- Sélectionner --</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                                @endforeach
                            </select>
                            @error('doctor_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date et heure <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" wire:model="scheduled_at">
                            @error('scheduled_at') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Type <span class="text-danger">*</span></label>
                            <select class="form-control" wire:model="consultation_type">
                                @foreach(\App\Models\Appointment::TYPE_LABELS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Durée (min)</label>
                            <select class="form-control" wire:model="duration_minutes">
                                <option value="15">15 min</option>
                                <option value="20">20 min</option>
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60">1h</option>
                                <option value="90">1h30</option>
                                <option value="120">2h</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Couleur</label>
                            <input type="color" class="form-control" wire:model="color" style="height:38px;padding:2px 4px;">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" class="form-control" wire:model="notes" placeholder="Motif, instructions...">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="button" wire:click="closeForm" class="btn btn-default m-r-10">
                            <i class="feather icon-x"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin"></i></span>
                            <i class="feather icon-save"></i>
                            {{ $editingId ? 'Mettre à jour' : 'Créer le rendez-vous' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Liste principale ─────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-calendar"></i> Rendez-vous</h5>
            <div class="card-header-right">
                <a href="{{ route('appointments.calendar') }}" class="btn btn-default btn-sm m-r-5">
                    <i class="feather icon-grid"></i> Calendrier
                </a>
                <a href="{{ route('appointments.waiting-room') }}" class="btn btn-warning btn-sm m-r-5">
                    <i class="feather icon-users"></i> Salle d'attente
                </a>
                <button type="button" wire:click="openForm()" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Nouveau RDV
                </button>
            </div>
        </div>
        <div class="card-block">

            {{-- ── Filtres ──────────────────────────────────────────────── --}}
            <div class="row m-b-20">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                               class="form-control" placeholder="Rechercher un patient...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-control">
                        <option value="">Tous les statuts</option>
                        @foreach(\App\Models\Appointment::STATUS_LABELS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="doctorFilter" class="form-control">
                        <option value="">Tous les médecins</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="dateFilter" class="form-control" title="Filtrer par date">
                </div>
                <div class="col-md-2 text-right">
                    @if($search || $statusFilter || $doctorFilter || $dateFilter)
                        <button wire:click="$set('search',''); $set('statusFilter',''); $set('doctorFilter',''); $set('dateFilter','')"
                                class="btn btn-default btn-sm">
                            <i class="feather icon-x"></i> Réinitialiser
                        </button>
                    @endif
                    <span class="text-muted small">{{ $appointments->total() }} RDV</span>
                </div>
            </div>

            {{-- ── Tableau ──────────────────────────────────────────────── --}}
            @if($appointments->count())
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width:140px">Date & Heure</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Type</th>
                            <th style="width:80px">Durée</th>
                            <th style="width:120px">Statut</th>
                            <th style="width:160px" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appt)
                        <tr class="{{ in_array($appt->status, ['cancelled','no_show']) ? 'text-muted' : '' }}">

                            {{-- Date/heure --}}
                            <td>
                                <div style="font-weight:600; font-size:13px;">
                                    {{ $appt->scheduled_at->format('d/m/Y') }}
                                </div>
                                <div class="text-muted" style="font-size:12px;">
                                    <i class="feather icon-clock" style="font-size:11px;"></i>
                                    {{ $appt->scheduled_at->format('H:i') }}
                                    @if($appt->scheduled_at->isToday())
                                        <span class="label label-success" style="font-size:9px;">Aujourd'hui</span>
                                    @elseif($appt->scheduled_at->isTomorrow())
                                        <span class="label label-info" style="font-size:9px;">Demain</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Patient --}}
                            <td>
                                <a href="{{ route('patients.show', $appt->patient) }}"
                                   class="text-dark" style="font-weight:600;">
                                    {{ $appt->patient->full_name }}
                                </a>
                                <div class="text-muted" style="font-size:11px;">
                                    {{ $appt->patient->dossier_number }}
                                    @if($appt->patient->phone)
                                        · {{ $appt->patient->phone }}
                                    @endif
                                </div>
                            </td>

                            {{-- Médecin --}}
                            <td>
                                <span style="font-size:13px;">Dr. {{ $appt->doctor->name }}</span>
                                @if($appt->doctor->speciality)
                                    <div class="text-muted" style="font-size:11px;">{{ $appt->doctor->speciality }}</div>
                                @endif
                            </td>

                            {{-- Type --}}
                            <td>
                                @php
                                    $typeColors = ['general'=>'label-default','specialized'=>'label-primary','followup'=>'label-info','emergency'=>'label-danger','control'=>'label-warning','teleconsultation'=>'bg-c-blue'];
                                @endphp
                                <span class="label {{ $typeColors[$appt->consultation_type] ?? 'label-default' }}" style="font-size:11px;">
                                    {{ \App\Models\Appointment::TYPE_LABELS[$appt->consultation_type] ?? $appt->consultation_type }}
                                </span>
                                @if($appt->notes)
                                    <div class="text-muted" style="font-size:11px; margin-top:3px;" title="{{ $appt->notes }}">
                                        <i class="feather icon-message-square" style="font-size:10px;"></i>
                                        {{ Str::limit($appt->notes, 30) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Durée --}}
                            <td class="text-muted" style="font-size:12px;">
                                {{ $appt->duration_minutes }} min
                            </td>

                            {{-- Statut + changement rapide --}}
                            <td>
                                <span class="label {{ \App\Models\Appointment::STATUS_COLORS[$appt->status] ?? 'label-default' }}">
                                    {{ \App\Models\Appointment::STATUS_LABELS[$appt->status] ?? $appt->status }}
                                </span>

                                {{-- Boutons de changement de statut rapide --}}
                                @if($appt->status === 'planned' || $appt->status === 'confirmed')
                                    <div class="m-t-5">
                                        <button wire:click="updateStatus({{ $appt->id }}, 'confirmed')"
                                                class="btn btn-xs btn-info m-b-2" title="Confirmer" style="font-size:10px; {{ $appt->status === 'confirmed' ? 'opacity:0.5' : '' }}">
                                            <i class="feather icon-check"></i>
                                        </button>
                                        <button wire:click="updateStatus({{ $appt->id }}, 'waiting')"
                                                class="btn btn-xs btn-warning m-b-2" title="Arrivé en salle d'attente" style="font-size:10px;">
                                            <i class="feather icon-user-check"></i>
                                        </button>
                                        <button wire:click="updateStatus({{ $appt->id }}, 'cancelled')"
                                                class="btn btn-xs btn-danger m-b-2" title="Annuler" style="font-size:10px;">
                                            <i class="feather icon-x"></i>
                                        </button>
                                    </div>
                                @elseif($appt->status === 'waiting')
                                    <div class="m-t-5">
                                        <button wire:click="updateStatus({{ $appt->id }}, 'in_progress')"
                                                class="btn btn-xs btn-primary" title="Démarrer la consultation" style="font-size:10px;">
                                            <i class="feather icon-play"></i> Démarrer
                                        </button>
                                    </div>
                                @elseif($appt->status === 'in_progress')
                                    <div class="m-t-5">
                                        <button wire:click="updateStatus({{ $appt->id }}, 'completed')"
                                                class="btn btn-xs btn-success" title="Terminer" style="font-size:10px;">
                                            <i class="feather icon-check-circle"></i> Terminer
                                        </button>
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-center">
                                {{-- Créer une consultation depuis le RDV --}}
                                @if(in_array($appt->status, ['confirmed','waiting','in_progress']) && !$appt->consultation)
                                    <a href="{{ route('consultations.create', ['appointmentId' => $appt->id]) }}"
                                       class="btn btn-xs btn-success m-b-2" title="Créer consultation">
                                        <i class="feather icon-activity"></i>
                                    </a>
                                @elseif($appt->consultation)
                                    <a href="{{ route('consultations.show', $appt->consultation) }}"
                                       class="btn btn-xs btn-info m-b-2" title="Voir consultation">
                                        <i class="feather icon-eye"></i>
                                    </a>
                                @endif

                                {{-- Modifier --}}
                                @if(!in_array($appt->status, ['completed','cancelled','no_show']))
                                    <button wire:click="openForm({{ $appt->id }})"
                                            class="btn btn-xs btn-warning m-b-2" title="Modifier">
                                        <i class="feather icon-edit"></i>
                                    </button>
                                @endif

                                {{-- Supprimer --}}
                                <button wire:click="confirmDelete({{ $appt->id }})"
                                        class="btn btn-xs btn-danger m-b-2" title="Supprimer">
                                    <i class="feather icon-trash-2"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="m-t-15">
                {{ $appointments->links() }}
            </div>

            @else
            <div class="text-center p-40 text-muted">
                <i class="feather icon-calendar" style="font-size:3em;"></i>
                <p class="m-t-10">Aucun rendez-vous trouvé.</p>
                <button wire:click="openForm()" class="btn btn-primary btn-sm">
                    <i class="feather icon-plus"></i> Créer un rendez-vous
                </button>
            </div>
            @endif

        </div>
    </div>

    {{-- ── Modal suppression ────────────────────────────────────────────── --}}
    @if($showDeleteModal)
    <div class="modal fade show" style="display:block; background:rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="feather icon-alert-triangle text-danger"></i> Confirmer la suppression</h5>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer ce rendez-vous ? Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('showDeleteModal', false)" class="btn btn-default">
                        <i class="feather icon-x"></i> Annuler
                    </button>
                    <button wire:click="delete" class="btn btn-danger">
                        <span wire:loading wire:target="delete"><i class="fa fa-spinner fa-spin"></i></span>
                        <i class="feather icon-trash-2"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
