@extends('layouts.auth')

@section('title', 'Verify Email - KLEMA')

@section('content')
<div class="auth-card">
    <div class="auth-card__header">
        <div class="auth-card__badge">
            <i class="fas fa-envelope-open-text text-3xl text-blue-200"></i>
        </div>
        <div>
            <h1 class="auth-card__title">Verify your email</h1>
            <p class="auth-card__subtitle">We sent a confirmation link to complete your registration</p>
        </div>
    </div>

    @if (session('resent'))
        <div class="auth-alert auth-alert--success">{{ __('A fresh verification link has been sent to your email address.') }}</div>
    @endif

    @if (session('status'))
        <div class="auth-alert auth-alert--info">{{ session('status') }}</div>
    @endif

    <div class="auth-form__group">
        <p class="auth-feedback" style="color: rgba(226, 232, 240, 0.85);">
            {{ __('Before proceeding, please check your email for a verification link.') }}<br>
            {{ __('If you did not receive the email, you can request another below.') }}
        </p>
    </div>

    <form class="auth-form" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="auth-button">
            <i class="fas fa-paper-plane"></i>
            <span>{{ __('Resend verification email') }}</span>
        </button>
    </form>

    <div class="auth-footer" style="margin-top: 18px;">
        <span>{{ __('Need to use a different email?') }}</span>
        <a href="#" class="auth-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Sign out') }}</a>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</div>
@endsection
