@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1 class="page-title">Welcome back, {{ Auth::user()->name }}!</h1>
    <p class="page-subtitle">Here's what's happening with your learning journey today</p>
</div>

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <h3 class="stat-value">12</h3>
                <p class="stat-label">Enrolled Courses</p>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>2 new this month
                </div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-book"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <h3 class="stat-value">8</h3>
                <p class="stat-label">Completed Assignments</p>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>3 this week
                </div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-tasks"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <h3 class="stat-value">24h</h3>
                <p class="stat-label">Learning Time</p>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>4h this week
                </div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <h3 class="stat-value">87%</h3>
                <p class="stat-label">Average Grade</p>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>5% improvement
                </div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="dashboard-grid">
    <!-- Recent Courses -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">Continue Learning</h3>
            <a href="#" class="btn btn-primary btn-sm">View All Courses</a>
        </div>
        
        <div class="course-item">
            <div class="course-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-code"></i>
            </div>
            <div class="course-info">
                <h4 class="course-title">Web Development Fundamentals</h4>
                <p class="course-meta">Next: HTML & CSS Basics • Due in 2 days</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 65%;"></div>
                </div>
            </div>
        </div>
        
        <div class="course-item">
            <div class="course-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-database"></i>
            </div>
            <div class="course-info">
                <h4 class="course-title">Database Management Systems</h4>
                <p class="course-meta">Next: SQL Queries • Due in 5 days</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 40%;"></div>
                </div>
            </div>
        </div>
        
        <div class="course-item">
            <div class="course-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="course-info">
                <h4 class="course-title">Mobile App Development</h4>
                <p class="course-meta">Next: React Native Basics • Due in 1 week</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 25%;"></div>
                </div>
            </div>
        </div>
        
        <div class="course-item">
            <div class="course-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="course-info">
                <h4 class="course-title">Data Analytics</h4>
                <p class="course-meta">Next: Python for Data Science • Due in 3 days</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 80%;"></div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
