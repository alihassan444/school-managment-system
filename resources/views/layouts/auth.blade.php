<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1f33">

    <title>Sign in · {{ config('app.name', 'Al Suffaa School') }}</title>
    <link rel="icon" href="{{ asset('images/al-suffaa-school-crest.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <div class="ambient ambient-one" aria-hidden="true"></div>
    <div class="ambient ambient-two" aria-hidden="true"></div>

    <main class="auth-shell">
        <section class="auth-story" aria-label="About this portal">
            <div class="auth-brand">
                <span class="brand-icon"><img src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="Al Suffaa School crest"></span>
                <span>Al Suffaa School</span>
            </div>

            <div class="auth-story-copy">
                <span class="hero-label"><i class="fa-solid fa-shield-halved"></i>Secure school office</span>
                <h1>Students and fees, <span>beautifully simple.</span></h1>
                <p>A focused administration portal for keeping student records accurate and every fee payment accounted for.</p>
                <div class="auth-feature-row">
                    <span class="auth-feature"><i class="fa-solid fa-user-graduate me-2"></i>Student records</span>
                    <span class="auth-feature"><i class="fa-solid fa-receipt me-2"></i>Fee receipts</span>
                </div>
            </div>

            <small class="text-white-50">Authorized staff access only</small>
        </section>

        <section class="auth-form-panel">
            @yield('content')
        </section>
    </main>
</body>
</html>
