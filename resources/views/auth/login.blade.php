@extends('layouts.auth')

@section('content')
<div class="w-100">
    <span class="page-kicker">Staff portal</span>
    <h2 class="form-title">Welcome back</h2>
    <p class="form-subtitle">Sign in to manage students and fee records.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">
                <i class="fas fa-envelope me-2"></i>Email Address
            </label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
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
            <div class="password-field">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="current-password"
                       placeholder="Enter your password">
                <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Show or hide password">
                    <i class="fas fa-eye text-muted" id="password-toggle"></i>
                </button>
            </div>

            @error('password')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group d-flex justify-content-between align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    Remember me
                </label>
            </div>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-arrow-right-to-bracket me-2"></i>Sign in to portal
        </button>
    </form>

    @if (Route::has('register'))
        <div class="divider">
            <span>Don't have an account?</span>
        </div>

        <div class="text-center">
            <a href="{{ route('register') }}" class="auth-link">
                <i class="fas fa-user-plus me-2"></i>Create new account
            </a>
        </div>
    @endif
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
