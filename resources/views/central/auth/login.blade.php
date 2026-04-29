@php
    $layoutMode = 'login';
    $hideCentralHeader = true;
    $systemLogo = asset('images/logos/logo.jpeg');
@endphp

@extends('layouts.central')

@section('content')
    <article class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-logo">
                <img src="{{ $systemLogo }}" alt="University Practicum Logo">
            </div>
            <span class="app-section-kicker">Central Access</span>
            <h1>University Practicum Admin System</h1>
            <p>Sign in to the university-wide dashboard for tenant approvals, subscriptions, and platform oversight.</p>
        </div>

        @if ($errors->any())
            <div class="error-panel">
                <strong>Login failed.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $loginAction }}">
            @csrf
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@buksu.edu.ph" required>
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>
            <label class="checkline">
                <input type="checkbox" name="remember" value="1">
                <span>Keep me signed in on this browser</span>
            </label>
            <button type="submit">Sign In</button>
        </form>

        <div class="auth-note">
            <strong>University Registry</strong>
            <p>Manage college registrations, portal access, and subscription oversight from one connected interface.</p>
        </div>
    </article>
@endsection
