@extends('layouts.auth')

@section('title', 'Register - KLEMA')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-20 w-20 bg-blue-600 rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-seedling text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-white">Join KLEMA</h2>
            <p class="mt-2 text-sm text-white/70">Create your account to get started</p>
        </div>
        
        <form class="mt-8 space-y-6" action="{{ route('register') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-white/80 mb-2">Full Name</label>
                    <input id="name" 
                           name="name" 
                           type="text" 
                           value="{{ old('name') }}"
                           class="w-full px-4 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('name') border-red-500 @enderror" 
                           placeholder="Enter your full name"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-white/80 mb-2">Email Address</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           value="{{ old('email') }}"
                           class="w-full px-4 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('email') border-red-500 @enderror" 
                           placeholder="Enter your email"
                           required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-white/80 mb-2">Account Type</label>
                    <select id="role" 
                            name="role" 
                            class="w-full px-4 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('role') border-red-500 @enderror" 
                            required>
                        <option value="">Select your role</option>
                        <option value="farmer" {{ old('role') == 'farmer' ? 'selected' : '' }}>Farmer</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-white/80 mb-2">Password</label>
                    <div class="relative">
                        <input id="password" 
                               name="password" 
                               type="password"
                               class="w-full px-4 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('password') border-red-500 @enderror" 
                               placeholder="Create a password"
                               required>
                        <button type="button" 
                                onclick="togglePassword('password')"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white/60 hover:text-white">
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-white/80 mb-2">Confirm Password</label>
                    <div class="relative">
                        <input id="password_confirmation" 
                               name="password_confirmation" 
                               type="password"
                               class="w-full px-4 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50" 
                               placeholder="Confirm your password"
                               required>
                        <button type="button" 
                                onclick="togglePassword('password_confirmation')"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white/60 hover:text-white">
                            <i id="password_confirmation-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-blue-300 group-hover:text-blue-200"></i>
                    </span>
                    Create Account
                </button>
            </div>

            <div class="text-center">
                <span class="text-white/60">Already have an account?</span>
                <a href="{{ route('login') }}" class="font-medium text-blue-400 hover:text-blue-300 ml-1">
                    Sign in
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const passwordIcon = document.getElementById(fieldId + '-icon');
        
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
