<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Al-Sufa LMS') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Bootstrap CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
        }
        
        
        .main-content {
            width: 100%;
            min-height: 100vh;
        }
        
        
        .top-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #e2e8f0;
            min-height: 70px;
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* School Logo Styling */
        .school-logo {
            display: flex;
            align-items: center;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .logo-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
            text-decoration: none;
            color: white;
        }
        
        .logo-icon {
            font-size: 1.5rem;
            color: white;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .school-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }
        
        .school-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            font-weight: 500;
        }
        
        .search-bar {
            flex: 1;
            max-width: 400px;
            margin-right: 2rem;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background-color: #f8fafc;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        
        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background-color: white;
            outline: none;
        }
        
        .search-input::placeholder {
            color: #9ca3af;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.875rem;
        }
        
        
        .top-nav-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-item {
            position: relative;
        }
        
        .profile-btn {
            position: relative;
            background: none !important;
            border: none !important;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            box-shadow: none !important;
        }
        
        .profile-btn:hover {
            background-color: #f7fafc !important;
            transform: scale(1.05);
            border: none !important;
        }
        
        .profile-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
            border: none !important;
        }
        
        .profile-btn:active {
            background-color: #f7fafc !important;
            border: none !important;
        }
        
        /* Hide Bootstrap dropdown arrow */
        .profile-btn.dropdown-toggle::after {
            display: none;
        }
        
        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .profile-status-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background-color: #10b981;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .dropdown-header {
            background-color: #f8fafc;
            border-radius: 12px 12px 0 0;
            margin: -0.5rem -0.5rem 0 -0.5rem;
            padding: 0.5rem !important;
        }
        
        .dropdown-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .dropdown-item:hover .dropdown-icon {
            background-color: #e2e8f0;
            color: #475569;
        }
        
        .text-danger:hover .dropdown-icon {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .breadcrumb-nav a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb-nav a:hover {
            color: #5a67d8;
        }
        
        .breadcrumb-separator {
            color: #d1d5db;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .page-subtitle {
            color: #718096;
            margin: 0.5rem 0 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            margin: 0;
        }
        
        .stat-change {
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .stat-change.positive {
            color: #38a169;
        }
        
        .stat-change.negative {
            color: #e53e3e;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .course-item,
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f7fafc;
        }
        
        .course-item:last-child,
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .course-icon,
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1rem;
        }
        
        .course-info,
        .activity-info {
            flex: 1;
        }
        
        .course-title,
        .activity-title {
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 0.25rem;
        }
        
        .course-meta,
        .activity-meta {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background-color: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        /* Tablet Responsiveness */
        @media (max-width: 1024px) {
            .content-area {
                padding: 1.5rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .top-navbar {
                padding: 0.75rem 1rem;
                min-height: 60px;
            }
            
            .school-logo {
                margin-right: 0.5rem;
            }
            
            .logo-container {
                padding: 0.375rem 0.75rem;
                gap: 0.5rem;
            }
            
            .logo-icon {
                font-size: 1.25rem;
            }
            
            .school-name {
                font-size: 1rem;
            }
            
            .school-subtitle {
                font-size: 0.7rem;
            }
            
            .search-bar {
                display: none;
            }
            
            .top-nav-right {
                gap: 0.5rem;
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .page-header .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                margin-bottom: 0.25rem;
                border-radius: 8px !important;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .dashboard-card {
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .top-navbar {
                padding: 0.5rem 0.75rem;
                min-height: 56px;
            }
            
            .logo-container {
                padding: 0.25rem 0.5rem;
                gap: 0.375rem;
            }
            
            .logo-icon {
                font-size: 1rem;
            }
            
            .school-name {
                font-size: 0.875rem;
            }
            
            .school-subtitle {
                font-size: 0.65rem;
            }
            
            .top-nav-right {
                gap: 0.25rem;
            }
            
            .content-area {
                padding: 0.75rem;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .page-subtitle {
                font-size: 0.875rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .card-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.5rem;
            }
            
            .card-header .d-flex {
                width: 100%;
                justify-content: space-between;
            }
            
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }
            
            .student-avatar,
            .course-icon,
            .activity-icon {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }
        }
        
        .btn-outline-primary {
            border-color: #667eea;
            color: #667eea;
        }
        
        .btn-outline-primary:hover {
            background-color: #667eea;
            border-color: #667eea;
            color: white;
        }
        
        .btn-outline-success {
            border-color: #10b981;
            color: #10b981;
        }
        
        .btn-outline-success:hover {
            background-color: #10b981;
            border-color: #10b981;
            color: white;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            padding: 0.5rem;
            min-width: 280px;
            margin-top: 0.5rem;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
            z-index: 1050;
            position: absolute;
        }
        
        .dropdown-item {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8fafc;
            transform: translateX(2px);
        }
        
        .dropdown-item.text-danger:hover {
            background-color: #fef2f2;
            color: #dc2626 !important;
        }
        
        .dropdown-divider {
            border-color: #e5e7eb;
            margin: 0.5rem 0;
        }
        
        .badge.bg-success-subtle {
            background-color: #dcfce7 !important;
            color: #166534 !important;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Fix dropdown button styling */
        .dropdown-item.btn {
            text-align: left;
            border: none;
            background: none;
            width: 100%;
            display: block;
        }
        
        .dropdown-item.btn:hover {
            background-color: #f8fafc;
            color: inherit;
        }
        
        .dropdown-item.btn.text-danger:hover {
            background-color: #fef2f2;
            color: #dc2626 !important;
        }
        
        /* Ensure logout button works properly */
        .dropdown-item.text-danger {
            cursor: pointer;
            transition: all 0.3s ease;
            border: none !important;
            background: transparent !important;
        }
        
        .dropdown-item.text-danger:hover {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
            transform: translateX(2px);
        }
        
        .dropdown-item.text-danger:focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }
        
        .dropdown-item.text-danger:active {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }
        
        .dropdown-item.text-danger:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
    </style>
</head>
<body>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div class="navbar-left">
                <!-- School Logo -->
                <div class="school-logo me-4">
                    <div class="logo-container">
                        <i class="fas fa-graduation-cap logo-icon"></i>
                        <div class="logo-text">
                            <div class="school-name">Al-Suffaa School
                            </div>
                            <div class="school-subtitle">Learning Management System</div>
                        </div>
                    </div>
                </div>
                
              
            </div>
            
            <div class="top-nav-right">
                <!-- Quick Actions -->
                <div class="d-flex align-items-center me-3">
                   
                    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-info me-2" title="Dashboard">
                        <i class="fas fa-home me-1"></i>
                        <span class="d-none d-xl-inline">Dashboard</span>
                    </a>
                    <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary me-2" title="Students">
                        <i class="fas fa-users me-1"></i>
                        <span class="d-none d-xl-inline">Students</span>
                    </a>
                    <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-secondary me-2" title="Payments">
                        <i class="fas fa-money-bill-wave me-1"></i>
                        <span class="d-none d-xl-inline">Payments</span>
                    </a>
                </div>
                
                <!-- Logout Button -->
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Logout">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        <span class="d-none d-xl-inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        // Initialize responsive behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Bootstrap dropdowns are auto-initialized by Bootstrap 5
            
            // Handle logout form submission
            const logoutForms = document.querySelectorAll('form[action*="logout"]');
            if (logoutForms.length > 0) {
                console.log('Logout forms found:', logoutForms.length);
                
                logoutForms.forEach((logoutForm, index) => {
                    logoutForm.addEventListener('submit', function(e) {
                        console.log(`Logout form ${index + 1} submitted`);
                        const submitButton = this.querySelector('button[type="submit"]');
                        if (submitButton) {
                            // Show loading state
                            submitButton.disabled = true;
                            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Logging out...';
                            submitButton.style.opacity = '0.7';
                        }
                    });
                });
            } else {
                console.log('No logout forms found');
            }
        });
    </script>
</body>
</html>
