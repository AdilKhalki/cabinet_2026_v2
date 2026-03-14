<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-shield"></i> Journal d'Audit</h5>
            <div class="card-header-right">
                <a href="{{ route('dashboard') }}" class="btn btn-default btn-sm">
                    <i class="feather icon-home"></i> Tableau de bord
                </a>
            </div>
        </div>
        <div class="card-block">

            {{-- Search & Filters --}}
            <div class="row m-b-20">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="feather icon-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher dans les logs...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="userFilter" class="form-control">
                        <option value="">-- Tous les utilisateurs --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name ?? $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="actionFilter" class="form-control">
                        <option value="">-- Toutes les actions --</option>
                        @foreach($availableActions as $action)
                            <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <span class="text-muted">{{ $logs->total() }} entrée(s)</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Date</th>
                            <th style="width: 160px;">Utilisateur</th>
                            <th style="width: 110px;">Action</th>
                            <th style="width: 130px;">Modèle</th>
                            <th>Description</th>
                            <th style="width: 120px;">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $actionColors = [
                                    'created'  => 'label-success',
                                    'updated'  => 'label-warning',
                                    'deleted'  => 'label-danger',
                                    'restored' => 'label-info',
                                    'login'    => 'label-primary',
                                    'logout'   => 'label-default',
                                    'viewed'   => 'label-default',
                                ];
                                $actionColor = $actionColors[$log->action] ?? 'label-default';
                            @endphp
                            <tr>
                                <td>
                                    <small>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}</small>
                                </td>
                                <td>
                                    @if($log->user)
                                        <i class="feather icon-user text-muted"></i>
                                        {{ $log->user->full_name ?? $log->user->name }}
                                    @else
                                        <span class="text-muted"><i class="feather icon-user-x"></i> Système</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="label {{ $actionColor }}">{{ $log->action ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($log->subject_type)
                                        <code style="font-size: 11px;">{{ class_basename($log->subject_type) }}</code>
                                        @if($log->subject_id)
                                            <small class="text-muted">#{{ $log->subject_id }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span title="{{ $log->description ?? '' }}">
                                        {{ Str::limit($log->description ?? '—', 80) }}
                                    </span>
                                    @if($log->properties && count($log->properties))
                                        <br>
                                        <small class="text-muted">
                                            @foreach(array_slice((array)$log->properties, 0, 3) as $key => $val)
                                                <span class="label label-default" style="font-size:10px;">{{ $key }}: {{ is_scalar($val) ? $val : json_encode($val) }}</span>
                                            @endforeach
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted"><i class="feather icon-globe"></i> {{ $log->ip_address ?? '—' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-20">
                                    <i class="feather icon-shield" style="font-size: 2em;"></i>
                                    <p>Aucun log d'audit trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $logs->links() }}
            </div>

        </div>
    </div>
</div>
