{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.auth')

@section('title', 'Login - KLEMA')

@section('content')
<div class="auth-card">
    @if (session('status'))
        <div class="auth-alert auth-alert--info">{{ session('status') }}</div>
    @endif

    <div class="auth-card__header">
        <div class="auth-card__badge">
            <i class="fas fa-seedling text-3xl text-blue-200"></i>
        </div>
        <div>
            <h1 class="auth-card__title">Welcome back</h1>
            <p class="auth-card__subtitle">Sign in to continue to the KLEMA dashboard</p>
        </div>
    </div>

    <form class="auth-form" action="{{ route('login') }}" method="POST">
        @csrf
        <div class="auth-form__group">
            <label for="email" class="auth-form__label">Email address</label>
            <div class="auth-input-wrapper @error('email') auth-input--error @enderror">
                <i class="fas fa-envelope"></i>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="auth-input @error('email') has-error @enderror"
                    placeholder="someone@example.com"
                    required
                    autocomplete="email"
                >
            </div>
            @error('email')
                <p class="auth-feedback">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label for="password" class="auth-form__label">Password</label>
            <div class="auth-input-wrapper @error('password') auth-input--error @enderror">
                <i class="fas fa-lock"></i>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="auth-input @error('password') has-error @enderror"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
                <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                    <i id="password-icon" class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <p class="auth-feedback">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__meta">
            <label class="auth-checkbox" for="remember">
                <input id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                <span>Remember me</span>
            </label>
            <a href="#" class="auth-link">Forgot password?</a>
        </div>

        <button type="submit" class="auth-button">
            <i class="fas fa-sign-in-alt"></i>
            <span>Sign in</span>
        </button>
    </form>

    <div class="auth-footer">
        <span>Don't have an account?</span>
        <a href="{{ route('register') }}" class="auth-link">Create one</a>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const passwordIcon = document.getElementById('password-icon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordIcon.className = 'fas fa-eye-slash';
        } else {
            passwordField.type = 'password';
            passwordIcon.className = 'fas fa-eye';
        }
    }
</script>
@endsection
