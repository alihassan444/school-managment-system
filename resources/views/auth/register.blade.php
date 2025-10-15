@extends('layouts.auth')

@section('content')
<div class="w-100">
    <h2 class="form-title">Join Al-Sufa LMS</h2>
    <p class="form-subtitle">Create your account to start learning</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label for="name" class="form-label">
                <i class="fas fa-user me-2"></i>Full Name
            </label>
            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                   name="name" value="{{ old('name') }}" required autocomplete="name" autofocus
                   placeholder="Enter your full name">

            @error('name')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email" class="form-label">
                <i class="fas fa-envelope me-2"></i>Email Address
            </label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                   name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="Enter your email address">

            @error('email')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">
                <i class="fas fa-lock me-2"></i>Password
            </label>
            <div class="position-relative">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="new-password"
                       placeholder="Create a strong password">
                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" 
                        onclick="togglePassword('password')" style="z-index: 10;">
                    <i class="fas fa-eye text-muted" id="password-toggle"></i>
                </button>
            </div>

            @error('password')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password-confirm" class="form-label">
                <i class="fas fa-shield-alt me-2"></i>Confirm Password
            </label>
            <div class="position-relative">
                <input id="password-confirm" type="password" class="form-control" 
                       name="password_confirmation" required autocomplete="new-password"
                       placeholder="Confirm your password">
                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" 
                        onclick="togglePassword('password-confirm')" style="z-index: 10;">
                    <i class="fas fa-eye text-muted" id="password-confirm-toggle"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="#" class="auth-link">Terms of Service</a> and <a href="#" class="auth-link">Privacy Policy</a>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Create Account
        </button>
    </form>

    <div class="divider">
        <span>Already have an account?</span>
    </div>

    <div class="text-center">
        <a href="{{ route('login') }}" class="auth-link">
            <i class="fas fa-sign-in-alt me-2"></i>Sign in to your account
        </a>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = document.getElementById(fieldId + '-toggle');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}
</script>
@endsection
