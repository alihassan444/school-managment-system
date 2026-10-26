<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1f33">

    <script>
        (() => {
            document.documentElement.classList.add('finance-private');
            try {
                if (localStorage.getItem('al-suffaa-finance-privacy') === 'visible') {
                    document.documentElement.classList.remove('finance-private');
                }
            } catch (error) {
                // Privacy remains enabled when browser storage is unavailable.
            }
        })();
    </script>
    <noscript><style>html.finance-private .content-area { visibility: visible !important; }</style></noscript>

    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'Al Suffaa School') }}</title>
    <link rel="icon" href="{{ asset('images/al-suffaa-school-crest.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="portal-body">
    <div class="ambient ambient-one" aria-hidden="true"></div>
    <div class="ambient ambient-two" aria-hidden="true"></div>

    <button class="sidebar-scrim" id="sidebarScrim" type="button" aria-label="Close navigation"></button>

    <aside class="portal-sidebar" id="portalSidebar" aria-label="Main navigation">
        <a href="{{ route('home') }}" class="brand-mark">
            <span class="brand-icon"><img src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="Al Suffaa School crest"></span>
            <span>
                <strong>Al Suffaa</strong>
                <small>School office</small>
            </span>
        </a>

        <div class="sidebar-label">Workspace</div>
        <nav class="sidebar-nav">
            <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home', 'dashboard') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-user-graduate"></i></span>
                <span>Students</span>
            </a>
            @if(in_array(Auth::user()->role ?: 'admin', ['admin', 'accountant']))
            <a href="{{ route('families.index') }}" class="nav-link {{ request()->routeIs('families.*', 'guardians.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-people-roof"></i></span>
                <span>Families &amp; Guardians</span>
            </a>
            @endif
            <a href="{{ route('academics.index') }}" class="nav-link {{ request()->routeIs('academics.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-layer-group"></i></span>
                <span>Classes &amp; Subjects</span>
            </a>
            <a href="{{ route('family-fees.index') }}" class="nav-link {{ request()->routeIs('fees.*', 'family-fees.*', 'payments.*', 'sibling-discounts.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-wallet"></i></span>
                <span>Fees</span>
            </a>
            <a href="{{ route('attendance.index') }}" class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-calendar-check"></i></span>
                <span>Attendance</span>
            </a>
            <a href="{{ route('exams.index') }}" class="nav-link {{ request()->routeIs('exams.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-file-pen"></i></span>
                <span>Exams</span>
            </a>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*', 'analytics') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                <span>Reports</span>
            </a>
            <a href="{{ route('timetable.index') }}" class="nav-link {{ request()->routeIs('timetable.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-table-cells"></i></span>
                <span>Time Table</span>
            </a>
            <a href="{{ route('staff.index') }}" class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                <span>Staff &amp; Salary</span>
            </a>
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
                <span>Settings</span>
            </a>
        </nav>

        <div class="sidebar-profile">
            <div class="user-avatar profile-brand-badge"><img src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="Al Suffaa School crest"></div>
            <div class="user-copy">
                <strong>{{ Auth::user()->name }}</strong>
                <small>{{ ucfirst(Auth::user()->role ?: 'admin') }}</small>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="icon-button icon-button-dark" aria-label="Sign out" title="Sign out">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </aside>

    <main class="portal-main">
        <header class="portal-topbar">
            <div class="topbar-left">
                <button class="icon-button mobile-menu-button" id="mobileMenuButton" type="button" aria-label="Open navigation" aria-controls="portalSidebar" aria-expanded="false">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div>
                    <span class="topbar-eyebrow">@yield('eyebrow', 'School operations')</span>
                    <strong class="topbar-title">@yield('title', 'Dashboard')</strong>
                </div>
            </div>
            <div class="topbar-actions">
                <button class="finance-privacy-toggle" id="financePrivacyToggle" type="button" aria-pressed="true" aria-label="Show financial amounts" title="Show financial amounts">
                    <span class="finance-privacy-icon" aria-hidden="true"><i class="fa-solid fa-eye-slash"></i></span>
                    <span class="finance-privacy-label">Amounts hidden</span>
                </button>
                <span class="date-chip"><i class="fa-regular fa-calendar"></i>{{ now()->format('D, d M') }}</span>
                <a href="{{ route('students.create') }}" class="btn btn-primary topbar-add">
                    <i class="fa-solid fa-user-plus me-2"></i>New student
                </a>
            </div>
        </header>

        <section class="content-area" id="pageContent">
            @yield('content')
        </section>
    </main>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home', 'dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-graduate"></i><span>Students</span>
        </a>
        <a href="{{ route('family-fees.index') }}" class="{{ request()->routeIs('fees.*', 'family-fees.*', 'payments.*', 'sibling-discounts.*') ? 'active' : '' }}">
            <i class="fa-solid fa-wallet"></i><span>Fees</span>
        </a>
        <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}">
            <i class="fa-solid fa-calendar-check"></i><span>Attendance</span>
        </a>
    </nav>

    @stack('scripts')
</body>
</html>
