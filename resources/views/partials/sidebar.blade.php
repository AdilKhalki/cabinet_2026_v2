@php
    $currentRoute = request()->route()?->getName() ?? '';
    $isActive = fn(string $prefix) => str_starts_with($currentRoute, $prefix);
@endphp

<nav class="pcoded-navbar"
     navbar-theme="themelight1"
     active-item-theme="theme1"
     sub-item-theme="theme2"
     active-item-style="style0"
     pcoded-navbar-position="fixed">
    <div class="sidebar_toggle"><a href="#"><i class="icon-close icons"></i></a></div>
    <div class="pcoded-inner-navbar main-menu">

        {{-- NAVIGATION PRINCIPALE --}}
        <div class="pcoded-navigatio-lavel">Navigation</div>
        <ul class="pcoded-item pcoded-left-item">

            {{-- Tableau de bord --}}
            <li class="{{ $isActive('dashboard') ? 'active' : '' }}">
                <a href="{{ route('home') }}">
                    <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                    <span class="pcoded-mtext">Tableau de bord</span>
                </a>
            </li>

            {{-- Patients --}}
            <li class="{{ $isActive('patients') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('patients.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                    <span class="pcoded-mtext">Patients</span>
                </a>
            </li>

            {{-- Rendez-vous --}}
            <li class="pcoded-hasmenu {{ $isActive('appointments') ? 'pcoded-trigger active' : '' }}"
                dropdown-icon="style1" subitem-icon="style6">
                <a href="javascript:void(0)">
                    <span class="pcoded-micon"><i class="feather icon-calendar"></i></span>
                    <span class="pcoded-mtext">Rendez-vous</span>
                </a>
                <ul class="pcoded-submenu">
                    <li class="{{ $currentRoute === 'appointments.index' ? 'active' : '' }}">
                        <a href="{{ route('appointments.index') }}">
                            <span class="pcoded-mtext">Liste des RDV</span>
                        </a>
                    </li>
                    <li class="{{ $currentRoute === 'appointments.calendar' ? 'active' : '' }}">
                        <a href="{{ route('appointments.calendar') }}">
                            <span class="pcoded-mtext">Calendrier</span>
                        </a>
                    </li>
                    <li class="{{ $currentRoute === 'appointments.waiting-room' ? 'active' : '' }}">
                        <a href="{{ route('appointments.waiting-room') }}">
                            <span class="pcoded-mtext">Salle d'attente</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Consultations --}}
            <li class="{{ $isActive('consultations') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('consultations.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-activity"></i></span>
                    <span class="pcoded-mtext">Consultations</span>
                </a>
            </li>

            {{-- Ordonnances --}}
            <li class="{{ $isActive('prescriptions') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('prescriptions.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                    <span class="pcoded-mtext">Ordonnances</span>
                </a>
            </li>

            {{-- Analyses --}}
            <li class="{{ $isActive('lab-tests') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('lab-tests.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-thermometer"></i></span>
                    <span class="pcoded-mtext">Analyses</span>
                </a>
            </li>

            {{-- Imagerie --}}
            <li class="{{ $isActive('medical-images') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('medical-images.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-image"></i></span>
                    <span class="pcoded-mtext">Imagerie</span>
                </a>
            </li>

            {{-- Documents --}}
            <li class="{{ $isActive('documents') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('documents.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-folder"></i></span>
                    <span class="pcoded-mtext">Documents</span>
                </a>
            </li>

            {{-- Rapports médicaux --}}
            <li class="{{ $isActive('medical-reports') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('medical-reports.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-clipboard"></i></span>
                    <span class="pcoded-mtext">Rapports Médicaux</span>
                </a>
            </li>

            {{-- Actes Médicaux --}}
            <li class="{{ $isActive('medical-acts') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('medical-acts.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-list"></i></span>
                    <span class="pcoded-mtext">Actes Médicaux</span>
                </a>
            </li>

            {{-- Médicaments --}}
            <li class="{{ $isActive('medications') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('medications.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-package"></i></span>
                    <span class="pcoded-mtext">Médicaments</span>
                </a>
            </li>

        </ul>

        {{-- FINANCE --}}
        <div class="pcoded-navigatio-lavel">Finance</div>
        <ul class="pcoded-item pcoded-left-item">

            <li class="{{ $isActive('payments') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('payments.index') }}">
                    <span class="pcoded-micon"><i class="feather icon-credit-card"></i></span>
                    <span class="pcoded-mtext">Paiements</span>
                </a>
            </li>

            <li class="{{ $isActive('reports.financial') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('reports.financial') }}">
                    <span class="pcoded-micon"><i class="feather icon-trending-up"></i></span>
                    <span class="pcoded-mtext">Rapport Financier</span>
                </a>
            </li>

        </ul>

        {{-- STATISTIQUES --}}
        <div class="pcoded-navigatio-lavel">Statistiques</div>
        <ul class="pcoded-item pcoded-left-item">

            <li class="{{ $isActive('reports.stats') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('reports.stats') }}">
                    <span class="pcoded-micon"><i class="feather icon-bar-chart-2"></i></span>
                    <span class="pcoded-mtext">Statistiques Cabinet</span>
                </a>
            </li>

        </ul>

        {{-- ADMINISTRATION (admin seulement) --}}
        @hasrole('admin')
        <div class="pcoded-navigatio-lavel">Administration</div>
        <ul class="pcoded-item pcoded-left-item">

            <li class="pcoded-hasmenu {{ $isActive('admin') ? 'pcoded-trigger active' : '' }}"
                dropdown-icon="style1" subitem-icon="style6">
                <a href="javascript:void(0)">
                    <span class="pcoded-micon"><i class="feather icon-shield"></i></span>
                    <span class="pcoded-mtext">Gestion Utilisateurs</span>
                </a>
                <ul class="pcoded-submenu">
                    <li>
                        <a href="{{ route('admin.users.index') }}">
                            <span class="pcoded-mtext">Utilisateurs</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.roles.index') }}">
                            <span class="pcoded-mtext">Rôles & Permissions</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="{{ $isActive('admin.activity-log') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('admin.activity-log') }}">
                    <span class="pcoded-micon"><i class="feather icon-eye"></i></span>
                    <span class="pcoded-mtext">Journal d'Audit</span>
                </a>
            </li>

            <li class="{{ $isActive('admin.settings') ? 'pcoded-trigger active' : '' }}">
                <a href="{{ route('admin.settings') }}">
                    <span class="pcoded-micon"><i class="feather icon-settings"></i></span>
                    <span class="pcoded-mtext">Paramètres Cabinet</span>
                </a>
            </li>

        </ul>
        @endhasrole

    </div>
</nav>
