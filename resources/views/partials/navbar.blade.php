@php
    // Requêtes limitées (évite de charger toutes les notifications non lues à chaque page)
    $navUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    $navUnreadLatest = auth()->user()?->unreadNotifications()->latest()->take(5)->get() ?? collect();
@endphp
<nav class="navbar header-navbar pcoded-header">
    <div class="navbar-wrapper">

        <div class="navbar-logo">
            <a class="mobile-menu" id="mobile-collapse" href="#!">
                <i class="feather icon-menu"></i>
            </a>
            <a href="{{ route('home') }}">
                <img class="img-fluid" src="{{ asset('adminty/images/logo.png') }}" alt="Cabinet Médical"
                    style="max-height:40px;">
                <span class="f-w-600" style="color:#fff; font-size:16px; margin-left:8px;">{{ config('app.name', 'MISAHATI') }}</span>
            </a>
            <a class="mobile-options">
                <i class="feather icon-more-horizontal"></i>
            </a>
        </div>

        <div class="navbar-container container-fluid">
            <ul class="nav-left">
                <li class="header-search">
                    <div class="main-search morphsearch-search">
                        <div class="input-group">
                            <span class="input-group-addon search-close"><i class="feather icon-x"></i></span>
                            <input type="text" class="form-control" placeholder="Rechercher...">
                            <span class="input-group-addon search-btn"><i class="feather icon-search"></i></span>
                        </div>
                    </div>
                </li>
                <li>
                    <a href="#!" onclick="javascript:toggleFullScreen()">
                        <i class="feather icon-maximize full-screen"></i>
                    </a>
                </li>
            </ul>

            <ul class="nav-right">
                {{-- Notifications --}}
                <li class="header-notification">
                    <div class="dropdown-primary dropdown">
                        <div class="dropdown-toggle" data-toggle="dropdown">
                            <i class="feather icon-bell"></i>
                            <span class="badge bg-c-pink">{{ $navUnreadCount }}</span>
                        </div>
                        <ul class="show-notification notification-view dropdown-menu" data-dropdown-in="fadeIn"
                            data-dropdown-out="fadeOut">
                            <li>
                                <h6>Notifications</h6>
                                <label class="label label-danger">Nouveau</label>
                            </li>
                            @forelse($navUnreadLatest as $notif)
                                <li>
                                    <div class="media">
                                        <div class="media-body">
                                            <h5 class="notification-user">{{ $notif->data['title'] ?? 'Notification' }}</h5>
                                            <p class="notification-msg">{{ $notif->data['message'] ?? '' }}</p>
                                            <span class="notification-time">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li><p class="text-center p-20">Aucune nouvelle notification</p></li>
                            @endforelse
                            <li>
                                <a href="{{ route('notifications.index') }}" class="text-center">Voir tout</a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- RDV du jour --}}
                <li class="header-notification">
                    <div class="dropdown-primary dropdown">
                        <div class="dropdown-toggle" data-toggle="dropdown">
                            <i class="feather icon-calendar"></i>
                        </div>
                        <ul class="show-notification dropdown-menu" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut"
                            style="min-width:280px;">
                            <li>
                                <h6>RDV du {{ now()->format('d/m/Y') }}</h6>
                            </li>
                            <li>
                                <a href="{{ route('appointments.index') }}">
                                    <i class="feather icon-calendar"></i> Gérer les rendez-vous
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Profil utilisateur --}}
                <li class="user-profile header-notification">
                    <div class="dropdown-primary dropdown">
                        <div class="dropdown-toggle" data-toggle="dropdown">
                            @if(auth()->user()?->profile_photo_path)
                                <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}"
                                    class="img-radius" alt="Profil">
                            @else
                                <div class="img-radius"
                                    style="width:35px;height:35px;background:#01a9ac;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;color:#fff;font-weight:600;font-size:14px;">
                                    {{ auth()->user()?->initials() ?? 'U' }}
                                </div>
                            @endif
                            <span>{{ auth()->user()?->name ?? 'Utilisateur' }}</span>
                            <i class="feather icon-chevron-down"></i>
                        </div>
                        <ul class="show-notification profile-notification dropdown-menu" data-dropdown-in="fadeIn"
                            data-dropdown-out="fadeOut">
                            <li>
                                <a href="{{ route('profile.edit') }}">
                                    <i class="feather icon-user"></i> Mon Profil
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('security.edit') }}">
                                    <i class="feather icon-settings"></i> Paramètres
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        <i class="feather icon-log-out"></i> Déconnexion
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
