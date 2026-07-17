@extends('filament.auth.layouts.app')

@section('title', 'Cashier Login')

@section('content')

<div class="auth-container">

    <!-- ==========================================
        LEFT BRANDING PANEL
    =========================================== -->

    <aside class="brand-panel">

        <div class="brand-content">

            <span class="portal-badge">
                Cashier Portal
            </span>

            <h1 class="brand-title">
                Motebele Systems POS
            </h1>

            <p class="brand-description">
                Fast, secure and Lekuka compliant Point of Sale system designed
                for modern retail businesses.
            </p>

            @if(file_exists(public_path('images/auth/cashier.svg')))
                <img
                    src="{{ asset('images/auth/cashier.svg') }}"
                    alt="Cashier Illustration"
                    class="brand-illustration">
            @endif

            <div class="feature-list">

                @foreach ($features as $feature)

                    <div class="feature-item">

                        <i class="fa-solid fa-circle-check"></i>

                        <span>{{ $feature }}</span>

                    </div>

                @endforeach

            </div>

            <div class="version">

                Version {{ config('app.version', '1.0.0') }}

            </div>

        </div>

    </aside>

    <!-- ==========================================
        LOGIN PANEL
    =========================================== -->

    <main class="login-panel">

    <div class="login-wrapper">

        <div class="login-card">

            <div class="login-header">

                <h2>Welcome Back</h2>

                <p>Sign in to continue processing sales.</p>

            </div>

            <form wire:submit="authenticate">

                {{ $this->form }}

                <button
                    type="submit"
                    class="login-button">

                    Login

                </button>

            </form>

        </div>

    </div>

    </main>

</div>

@endsection