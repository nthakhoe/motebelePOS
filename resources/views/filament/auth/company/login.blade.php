@extends('filament.auth.layouts.app')

@section('title', 'Company Login')

@section('content')

<div class="auth-container company-theme">

    <!-- LEFT PANEL -->

    <aside class="brand-panel">

        <div class="brand-content">

            <span class="portal-badge">

                Company Portal

            </span>

            <h1 class="brand-title">

                Motebele Systems POS

            </h1>

            <p class="brand-description">

                Manage every aspect of your retail business from one secure platform.

            </p>

            @if(file_exists(public_path('images/auth/company.svg')))
                <img
                    src="{{ asset('images/auth/company.svg') }}"
                    class="brand-illustration"
                    alt="Company Portal">
            @endif

            <div class="feature-list">

                @foreach($features as $feature)

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

    <!-- RIGHT PANEL -->

    <main class="login-panel">

        <div class="login-wrapper">

            <div class="login-header">

                <h2>

                    Welcome Back

                </h2>

                <p>

                    Sign in to manage your business.

                </p>

            </div>

            <div class="login-form">

                <form wire:submit="authenticate">

                    {{ $this->form }}

                    <button
                        type="submit"
                        class="login-button">

                        <i class="fa-solid fa-building"></i>

                        Login to Company Portal

                    </button>

                </form>

            </div>

        </div>

    </main>

</div>

@endsection