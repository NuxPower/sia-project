@extends('layouts.auth')

@section('title', 'Register - KLEMA')

@section('content')
<div class="auth-card">
    <div class="auth-card__header">
        <div class="auth-card__badge">
            <i class="fas fa-seedling text-3xl text-blue-200"></i>
        </div>
        <div>
            <h1 class="auth-card__title">Join KLEMA</h1>
            <p class="auth-card__subtitle">Create your account to get started</p>
        </div>
    </div>

    <form class="auth-form" action="{{ route('register') }}" method="POST">
        @csrf

        <div class="auth-form__group">
            <label for="name" class="auth-form__label">Full Name</label>
            <div class="auth-input-wrapper @error('name') auth-input--error @enderror">
                <i class="fas fa-user"></i>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    class="auth-input @error('name') has-error @enderror"
                    placeholder="Enter your full name"
                    required
                    autocomplete="name"
                >
            </div>
            @error('name')
                <p class="auth-feedback">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label for="email" class="auth-form__label">Email Address</label>
            <div class="auth-input-wrapper @error('email') auth-input--error @enderror">
                <i class="fas fa-envelope"></i>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="auth-input @error('email') has-error @enderror"
                    placeholder="Enter your email"
                    required
                    autocomplete="email"
                >
            </div>
            @error('email')
                <p class="auth-feedback">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label">Account Type</label>
            <div class="auth-input-wrapper" style="pointer-events: none; opacity: 0.8;">
                <i class="fas fa-user-tag"></i>
                <div class="auth-input" style="padding-right: 20px;">Farmer</div>
            </div>
            <input type="hidden" name="role" value="farmer">
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
                    placeholder="Create a password"
                    required
                    autocomplete="new-password"
                >
                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword('password', 'password-icon')"
                >
                    <i id="password-icon" class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <p class="auth-feedback">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label for="password_confirmation" class="auth-form__label">Confirm Password</label>
            <div class="auth-input-wrapper">
                <i class="fas fa-lock"></i>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="auth-input"
                    placeholder="Confirm your password"
                    required
                    autocomplete="new-password"
                >
                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword('password_confirmation', 'password-confirmation-icon')"
                >
                    <i id="password-confirmation-icon" class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-button">
            <i class="fas fa-user-plus"></i>
            <span>Create Account</span>
        </button>

        <div class="auth-footer">
            <span>Already have an account?</span>
            <a href="{{ route('login') }}" class="auth-link">Sign in</a>
        </div>
    </form>
</div>

<script>
    document.body.classList.add('auth-scroll');

    window.addEventListener('beforeunload', function () {
        document.body.classList.remove('auth-scroll');
    });

    function togglePassword(fieldId, iconId) {
        const passwordField = document.getElementById(fieldId);
        const passwordIcon = document.getElementById(iconId);
        
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
