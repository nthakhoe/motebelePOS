<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="stylesheet"
          href="{{ asset('css/auth.css') }}">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

    @filamentStyles

</head>

<body>

<div class="login-wrapper">

    <!-- ==========================================================
            LEFT PANEL
    =========================================================== -->

    <div class="login-brand">

        <div class="brand-overlay"></div>

        <div class="brand-content">

            <img
                src="{{ asset($logo ?? 'images/logo.png') }}"
                class="brand-logo"
                alt="Logo">

            <div class="portal-badge">

                {{ $portal ?? 'Portal' }}

            </div>

            <h1>

                {{ $heading }}

            </h1>

            <p class="brand-description">

                {{ $description }}

            </p>

            @if(!empty($illustration))

                <img
                    src="{{ asset($illustration) }}"
                    class="portal-illustration"
                    alt="Illustration">

            @endif

            @if(!empty($features))

                <ul class="feature-list">

                    @foreach($features as $feature)

                        <li>

                            <i class="fa-solid fa-circle-check"></i>

                            {{ $feature }}

                        </li>

                    @endforeach

                </ul>

            @endif

            <div class="version">

                Version {{ config('app.version','1.0.0') }}

            </div>

        </div>

    </div>

    <!-- ==========================================================
            RIGHT PANEL
    =========================================================== -->

    <div class="login-panel">

        <div class="login-card">

            <div class="welcome">

                <h2>

                    {{ $welcomeTitle ?? 'Welcome Back' }}

                </h2>

                <p>

                    {{ $welcomeMessage ?? 'Please sign in to continue.' }}

                </p>

            </div>

            {{ $slot }}

        </div>

    </div>

</div>

@filamentScripts

</body>

</html>