<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} - Cabinet Médical</title>
    <link rel="icon" href="{{ asset('adminty/images/favicon.ico') }}" type="image/x-icon">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 3 --}}
    <link rel="stylesheet" href="{{ asset('adminty/css/bootstrap.min.css') }}">

    {{-- Feather Icons --}}
    <link rel="stylesheet" href="{{ asset('adminty/icon-feather/css/feather.css') }}">

    {{-- Adminty Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('adminty/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('adminty/css/jquery.mCustomScrollbar.css') }}">

    {{-- CSS personnalisé du cabinet --}}
    <link rel="stylesheet" href="{{ asset('adminty/css/cabinet.css') }}">

    {{-- Styles supplémentaires (chargés une seule fois via stack) --}}
    @stack('styles')

    {{-- Livewire styles --}}
    @livewireStyles
</head>

<body>
    {{-- Pre-loader --}}
    <div class="theme-loader">
        <div class="ball-scale">
            <div class="contain">
                @for ($i = 0; $i < 10; $i++)
                    <div class="ring"><div class="frame"></div></div>
                @endfor
            </div>
        </div>
    </div>

    <div id="pcoded" class="pcoded"
         nav-type="st2"
         theme-layout="vertical"
         vertical-placement="left"
         vertical-layout="wide"
         pcoded-device-type="desktop"
         vertical-nav-type="expanded"
         vertical-effect="shrink"
         vnavigation-view="view1"
         fream-type="theme1"
         sidebar-img="false"
         sidebar-img-type="img1"
         layout-type="light">
        <div class="pcoded-overlay-box"></div>
        <div class="pcoded-container navbar-wrapper">

            {{-- NAVBAR --}}
            @include('partials.navbar')

            {{-- SIDEBAR CHAT (optionnel) --}}

            <div class="pcoded-main-container">
                <div class="pcoded-wrapper">

                    {{-- SIDEBAR NAVIGATION --}}
                    @include('partials.sidebar')

                    {{-- CONTENU PRINCIPAL --}}
                    <div class="pcoded-content">
                        <div class="pcoded-inner-content">
                            <div class="main-body">
                                <div class="page-wrapper">

                                    {{-- BREADCRUMB --}}
                                    @isset($header)
                                        <div class="page-header">
                                            <div class="row align-items-end">
                                                <div class="col-lg-8">
                                                    <div class="page-header-title">
                                                        <div class="d-inline">
                                                            @isset($headerIcon)
                                                                <i class="feather {{ $headerIcon }} bg-c-blue update-card text-white"></i>
                                                            @endisset
                                                            <div class="d-inline">
                                                                <h4>{{ $header }}</h4>
                                                                @isset($subHeader)
                                                                    <span>{{ $subHeader }}</span>
                                                                @endisset
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="page-header-breadcrumb">
                                                        <ul class="breadcrumb-title">
                                                            <li class="breadcrumb-item">
                                                                <a href="{{ route('dashboard') }}">
                                                                    <i class="feather icon-home"></i>
                                                                </a>
                                                            </li>
                                                            @isset($breadcrumbs)
                                                                @foreach($breadcrumbs as $bc)
                                                                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                                                        @if($loop->last)
                                                                            {{ $bc['label'] }}
                                                                        @else
                                                                            <a href="{{ $bc['url'] ?? '#' }}">{{ $bc['label'] }}</a>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            @endisset
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endisset

                                    {{-- CONTENU --}}
                                    {{ $slot }}

                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- FIN CONTENU PRINCIPAL --}}

                </div>
            </div>

        </div>
    </div>

    {{-- FOOTER --}}
    @include('partials.footer')

    {{-- Scripts de base (chargés UNE SEULE FOIS) --}}
    <script src="{{ asset('adminty/js/jquery.min.js') }}"></script>
    {{-- Popper.js requis par Bootstrap 4 --}}
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="{{ asset('adminty/js/bootstrap.min.js') }}"></script>
    {{-- jQuery slimScroll requis par script.min.js --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery-slimscroll@1.3.8/jquery.slimscroll.min.js"></script>
    <script src="{{ asset('adminty/js/jquery.mCustomScrollbar.concat.min.js') }}"></script>
    <script src="{{ asset('adminty/js/pcoded.min.js') }}"></script>
    <script src="{{ asset('adminty/js/vartical-layout.min.js') }}"></script>
    <script src="{{ asset('adminty/js/script.min.js') }}"></script>

    {{-- Livewire scripts --}}
    @livewireScripts

    {{-- Alpine.js (via Livewire) --}}

    {{-- Scripts de pages spécifiques (poussés depuis les views) --}}
    @stack('scripts')

</body>
</html>
