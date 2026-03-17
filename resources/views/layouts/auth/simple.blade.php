<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            .auth-bg {
                min-height: 100vh;
                position: relative;
                overflow: hidden;
            }
            .auth-card {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 16px;
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
                backdrop-filter: blur(10px);
                padding: 40px;
                width: 100%;
                max-width: 420px;
                position: relative;
                z-index: 1;
            }
            .auth-brand {
                text-align: center;
                margin-bottom: 10px;
            }
            .auth-brand img {
                height: 180px;
                margin: 0 auto 15px;
                display: block;
            }
            .auth-brand h1 {
                font-size: 22px;
                font-weight: 700;
                color: #01a9ac;
                margin: 0;
                letter-spacing: 1px;
            }
            .auth-brand p {
                font-size: 13px;
                color: #888;
                margin-top: 4px;
            }
            .auth-footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: rgba(255, 255, 255, 0.4);
                position: relative;
                z-index: 1;
            }
        </style>
    </head>
    <body class="antialiased" style="margin: 0; padding: 0;">
        <div class="auth-bg" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; background: url('{{ asset('images/bg.jpg') }}') no-repeat center center / cover;">
            <div class="auth-card">
                <div class="auth-brand">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                </div>
                {{ $slot }}
            </div>
            <div class="auth-footer" style="color:darkblue">
                &copy; {{ date('Y') }} {{ config('app.name', 'MISAHATI') }} — Cabinet Médical
                <br>
                Adil KHALKI
            </div>
        </div>
        @fluxScripts
    </body>
</html>
