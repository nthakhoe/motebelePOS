@extends('filament.auth.layouts.app')

@section('title', 'System Administrator')

@section('content')

<div class="auth-container admin-theme">

    <aside class="brand-panel">

        <div class="brand-content">

            <span class="portal-badge">

                System Administrator

            </span>

            <h1 class="brand-title">

                Motebele Systems POS

            </h1>

            <p class="brand-description">

                Centralized administration and system management for enterprise retail operations.

            </p>

            @if(file_exists(public_path('images/auth/administrator.svg')))
                <img
                    src="{{ asset('images/auth/administrator.svg') }}"
                    class="brand-illustration"
                    alt="Administrator Portal">
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

    <main class="login-panel">

        <div class="login-wrapper">

            <div class="login-header">

                <h2>

                    Administrator Login

                </h2>

                <p>

                    Sign in to manage the Motebele Systems POS platform.

                </p>

            </div>

            <div class="login-form">

                <form wire:submit="authenticate">

                    {{ $this->form }}

                    <button
                        type="submit"
                        class="login-button">

                        <i class="fa-solid fa-shield-halved"></i>

                        Login to Administration

                    </button>

                </form>

            </div>

        </div>

    </main>

</div>

@endsection