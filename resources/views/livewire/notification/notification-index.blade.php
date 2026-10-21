<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5>
                <i class="feather icon-bell"></i> Notifications
                @if($unreadCount > 0)
                    <span class="badge badge-pill label-danger" style="font-size: 12px; margin-left: 5px;">{{ $unreadCount }}</span>
                @endif
            </h5>
            <div class="card-header-right">
                @if($unreadCount > 0)
                    <button type="button" wire:click="markAllAsRead" class="btn btn-info btn-sm" wire:loading.attr="disabled" wire:loading.class="disabled" wire:target="markAllAsRead">
                        <span wire:loading.remove wire:target="markAllAsRead">
                            <i class="feather icon-check-circle"></i> Tout marquer comme lu
                        </span>
                        <span wire:loading wire:target="markAllAsRead">
                            <i class="feather icon-loader"></i> En cours...
                        </span>
                    </button>
                @endif
                <a href="{{ route('home') }}" class="btn btn-default btn-sm">
                    <i class="feather icon-home"></i> Tableau de bord
                </a>
            </div>
        </div>
        <div class="card-block">

            {{-- Filter --}}
            <div class="row m-b-20">
                <div class="col-md-4">
                    <select wire:model.live="filter" class="form-control">
                        <option value="all">-- Toutes les notifications --</option>
                        <option value="unread">Non lues</option>
                        <option value="read">Lues</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="typeFilter" class="form-control">
                        <option value="">-- Tous les types --</option>
                        @foreach($availableTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-right">
                    <span class="text-muted">{{ $notifications->total() }} notification(s)</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Date</th>
                            <th style="width: 150px;">Type</th>
                            <th>Message</th>
                            <th style="width: 100px;">Statut</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            @php
                                $isUnread = is_null($notification->read_at);
                                $typeColors = [
                                    'appointment'   => 'label-primary',
                                    'payment'       => 'label-success',
                                    'lab_result'    => 'label-info',
                                    'prescription'  => 'label-warning',
                                    'alert'         => 'label-danger',
                                    'reminder'      => 'label-default',
                                    'system'        => 'label-default',
                                ];
                                $notifType = $notification->data['type'] ?? $notification->type ?? 'system';
                                $notifType = class_basename($notifType);
                                $notifType = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $notifType));
                                $typeColor  = $typeColors[$notifType] ?? 'label-default';
                                $message    = $notification->data['message'] ?? $notification->data['body'] ?? 'Notification';
                                $typeLabel  = $notification->data['title'] ?? ucfirst(str_replace('_', ' ', $notifType));
                            @endphp
                            <tr class="{{ $isUnread ? 'active' : '' }}">
                                <td>
                                    <small>{{ $notification->created_at ? $notification->created_at->format('d/m/Y H:i') : '—' }}</small>
                                    @if($isUnread)
                                        <br><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#4099ff; margin-top:2px;" title="Non lu"></span>
                                    @endif
                                </td>
                                <td>
                                    <span class="label {{ $typeColor }}">{{ $typeLabel }}</span>
                                </td>
                                <td>
                                    @if($isUnread)
                                        <strong>{{ $message }}</strong>
                                    @else
                                        {{ $message }}
                                    @endif
                                    @if(!empty($notification->data['url']) && \Illuminate\Support\Str::startsWith($notification->data['url'], ['http://', 'https://', '/']))
                                        <br><a href="{{ $notification->data['url'] }}" class="text-primary" style="font-size:12px;">
                                            <i class="feather icon-external-link"></i> Voir les détails
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if($isUnread)
                                        <span class="label label-warning"><i class="feather icon-circle"></i> Non lu</span>
                                    @else
                                        <span class="label label-default"><i class="feather icon-check"></i> Lu</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isUnread)
                                        <button type="button"
                                            wire:click="markAsRead('{{ $notification->id }}')"
                                            class="btn btn-info btn-xs" title="Marquer comme lu">
                                            <i class="feather icon-check"></i> Lu
                                        </button>
                                    @endif
                                    <button type="button"
                                        wire:click="delete('{{ $notification->id }}')"
                                        wire:confirm="Supprimer cette notification ?"
                                        class="btn btn-danger btn-xs" title="Supprimer">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted p-30">
                                    <i class="feather icon-bell-off" style="font-size: 3em;"></i>
                                    <p class="m-t-10">Aucune notification.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-t-20">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
</div>
