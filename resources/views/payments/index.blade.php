@extends('layouts.dashboard')

@section('title', 'Payment History')

@section('content')
<div class="page-header">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="page-title">
                <i class="fas fa-history me-2 text-primary"></i>Payment History
            </h1>
            <p class="page-subtitle">Track and manage all payment records and transactions</p>
            <div class="d-flex align-items-center gap-3 mt-2">
                <div class="stats-badge">
                    <i class="fas fa-receipt me-1"></i>
                    <span class="fw-semibold">{{ $payments->total() ?? 0 }}</span> Records
                </div>
                <div class="stats-badge">
                    <i class="fas fa-calendar me-1"></i>
                    <span class="fw-semibold">{{ date('M Y') }}</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('payments.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Payment
            </a>
            <a href="#" class="btn btn-outline-secondary" onclick="printTable()">
                <i class="fas fa-print me-2"></i>Print
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Advanced Filters -->
<div class="dashboard-card mb-4">
    <div class="card-header bg-dark">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter me-2"></i>Advanced Filters
        </h5>
    
    </div>
    <div class="collapse show" id="filtersCollapse">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-3" id="filterForm">
                <div class="col-12 col-lg-4">
                    <label for="search" class="form-label">
                        <i class="fas fa-search me-1"></i>Search
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Student name, receipt number...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Payment method filter removed in simplified system -->
                
                <div class="col-6 col-lg-2">
                    <label for="date_from" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i>From Date
                    </label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                
                <div class="col-6 col-lg-2">
                    <label for="date_to" class="form-label">
                        <i class="fas fa-calendar-check me-1"></i>To Date
                    </label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                
                <div class="col-12 col-lg-4 d-flex align-items-end">
                    <div class="ms-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Apply
                        </button>
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Quick Filter Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="text-muted small">Quick filters:</span>
                        <button class="btn btn-outline-primary btn-sm" onclick="filterByPeriod('today')">Today</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="filterByPeriod('week')">This Week</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="filterByPeriod('month')">This Month</button>
                        <!-- Quick method filters removed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="dashboard-card">
    <div class="card-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h3 class="card-title mb-1">
                    <i class="fas fa-receipt me-2"></i>Payment Records
                </h3>
                <small class="text-light opacity-75">
                    Showing {{ $payments->count() }} of {{ $payments->total() }} payments
                </small>
            </div>
            <div class="d-flex gap-2">
                <div class="btn-group gap-2" role="group">
                    <button class="btn btn-outline-light btn-sm" onclick="toggleView('table')" id="tableViewBtn">
                        <i class="fas fa-table me-1"></i>Table
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="toggleView('cards')" id="cardsViewBtn">
                        <i class="fas fa-th-large me-1"></i>Cards
                    </button>
                </div>
                <button class="btn btn-outline-light btn-sm" onclick="refreshData()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>
    
    <!-- Table View -->
    <div class="table-responsive" id="tableView">
        <table class="table table-hover mb-0" id="paymentsTable">
            <thead>
                <tr>
                    <th class="d-none d-lg-table-cell">
                        <i class="fas fa-calendar me-1"></i>Date
                    </th>
                    <th>
                        <i class="fas fa-user me-1"></i>Student
                    </th>
                    <th class="d-none d-md-table-cell">
                        <i class="fas fa-receipt me-1"></i>Receipt
                    </th>
                    <!-- Type & Method columns removed -->
                    <th class="text-end">
                        <i class="fas fa-money-bill me-1"></i>Amount
                    </th>
                    <!-- Reference column removed -->
                    <th>
                        <i class="fas fa-cogs me-1"></i>Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="payment-row" data-payment-id="{{ $payment->id }}">
                        <td class="d-none d-lg-table-cell">
                            <div class="d-flex flex-column">
                                <span class="fw-semibold">{{ $payment->payment_date->format('d M Y') }}</span>
                                <small class="text-muted">{{ $payment->payment_date->format('l') }}</small>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="student-avatar me-3">
                                    {{ strtoupper(substr($payment->student->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $payment->student->name }}</div>
                                    <small class="text-muted">Class: {{ $payment->student->class }}</small>
                                    <div class="d-block d-lg-none">
                                        <small class="text-muted">{{ $payment->payment_date->format('d M Y') }}</small>
                                    </div>
                                    <div class="d-block d-md-none mt-1">
                                        <span class="badge bg-primary">{{ $payment->receipt_number }}</span>
                                    </div>
                                
                                </div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span class="badge bg-primary">{{ $payment->receipt_number }}</span>
                        </td>
                        
                        <td class="text-end">
                            <span class="fw-bold text-success amount-highlight">Rs {{ number_format($payment->amount, 0) }}</span>
                            
                        </td>
                        
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('payments.show', $payment) }}" 
                                   class="btn btn-outline-info btn-sm" 
                                   title="View Receipt">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <a href="{{ route('students.show', $payment->student) }}" 
                                   class="btn btn-outline-primary btn-sm" 
                                   title="View Student">
                                    <i class="fas fa-user"></i>
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" 
                                            id="actionDropdown{{ $payment->id }}" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false"
                                            title="More Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown{{ $payment->id }}">
                                        <li>
                                            <form method="POST" action="{{ route('payments.destroy', $payment) }}" 
                                                  onsubmit="return confirmDelete('{{ $payment->student->name }}', '{{ $payment->receipt_number }}')"
                                                  class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash me-2"></i>Delete Payment
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No Payment Records Found</h5>
                                <p class="text-muted">Start by recording your first payment</p>
                                <a href="{{ route('payments.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Record First Payment
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Card View (Mobile Friendly) -->
    <div class="d-none" id="cardsView">
        <div class="row">
            @forelse($payments as $payment)
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="payment-card">
                        <div class="card-header-custom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="student-avatar me-2">
                                        {{ strtoupper(substr($payment->student->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $payment->student->name }}</h6>
                                        <small class="text-muted">{{ $payment->student->class }}</small>
                                    </div>
                                </div>
                                <span class="badge bg-primary">{{ $payment->receipt_number }}</span>
                            </div>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Amount</small>
                                    <div class="fw-bold text-success">Rs {{ number_format($payment->amount, 0) }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Date</small>
                                    <div>{{ $payment->payment_date->format('d M Y') }}</div>
                                </div>
                            </div>
                            
                        </div>
                        <div class="card-footer-custom">
                            <div class="d-flex gap-2">
                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-info btn-sm flex-fill">
                                    <i class="fas fa-receipt me-1"></i>Receipt
                                </a>
                                <a href="{{ route('students.show', $payment->student) }}" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="fas fa-user me-1"></i>Student
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false"
                                            title="More Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <form method="POST" action="{{ route('payments.destroy', $payment) }}" 
                                                  onsubmit="return confirmDelete('{{ $payment->student->name }}', '{{ $payment->receipt_number }}')"
                                                  class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash me-2"></i>Delete Payment
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Payment Records Found</h5>
                        <p class="text-muted">Start by recording your first payment</p>
                        <a href="{{ route('payments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Record First Payment
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
    
    @if($payments->hasPages())
        <div class="card-footer">
            {{ $payments->links() }}
        </div>
    @endif
</div>

<style>
/* Enhanced Card Header */
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0 !important;
    padding: 1.5rem;
    border-bottom: none;
}

.card-header .card-title {
    color: white !important;
    margin: 0;
    font-weight: 600;
}

.card-header .card-title i {
    color: white !important;
}

.card-header h3 {
    color: white !important;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.card-header h3 i {
    color: white !important;
    margin-right: 0.5rem;
}

.card-header h5 {
    color: white !important;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.card-header h5 i {
    color: white !important;
    margin-right: 0.5rem;
}

.card-header.bg-light {
    background: #f8fafc !important;
    color: #374151 !important;
    border-bottom: 1px solid #e5e7eb;
}

/* Stats Badges */
.stats-badge {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

/* Student Avatar */
.student-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Enhanced Table */
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background-color: #f8fafc;
    color: #374151;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.payment-row:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
    transition: all 0.3s ease;
}

.amount-highlight {
    font-size: 1.1rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Payment Cards */
.payment-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}

.payment-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.card-header-custom {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px 12px 0 0;
}

.card-body-custom {
    padding: 1rem;
}

.card-footer-custom {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f8fafc;
    border-radius: 0 0 12px 12px;
}

/* Use Bootstrap default button styling */

/* Use only Bootstrap default styling for dropdowns */

/* Use Bootstrap default dropdown styling */

/* Alerts */
.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* Empty State */
.empty-state {
    padding: 2rem;
}

/* Responsive Utilities */
@media (max-width: 768px) {
    .card-header {
        padding: 1rem;
    }
    
    .stats-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .payment-card {
        margin-bottom: 1rem;
    }
    
    .student-avatar {
        width: 32px;
        height: 32px;
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.5rem;
    }
    
    .table th,
    .table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.875rem;
    }
    
    .card-header-custom,
    .card-body-custom,
    .card-footer-custom {
        padding: 0.75rem;
    }
    
    /* Use Bootstrap default mobile styling */
}

/* Dashboard Card */
.dashboard-card .table-responsive {
    border-radius: 0 0 16px 16px;
}
</style>

<script>
// View Toggle Functions
function toggleView(viewType) {
    const tableView = document.getElementById('tableView');
    const cardsView = document.getElementById('cardsView');
    const tableBtn = document.getElementById('tableViewBtn');
    const cardsBtn = document.getElementById('cardsViewBtn');
    
    if (viewType === 'table') {
        tableView.classList.remove('d-none');
        cardsView.classList.add('d-none');
        tableBtn.classList.add('active');
        cardsBtn.classList.remove('active');
        localStorage.setItem('paymentView', 'table');
    } else {
        tableView.classList.add('d-none');
        cardsView.classList.remove('d-none');
        tableBtn.classList.remove('active');
        cardsBtn.classList.add('active');
        localStorage.setItem('paymentView', 'cards');
    }
}

// Filter Functions
function filterByPeriod(period) {
    const today = new Date();
    let fromDate, toDate;
    
    switch(period) {
        case 'today':
            fromDate = toDate = today.toISOString().split('T')[0];
            break;
        case 'week':
            const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
            fromDate = weekStart.toISOString().split('T')[0];
            toDate = new Date().toISOString().split('T')[0];
            break;
        case 'month':
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            toDate = new Date().toISOString().split('T')[0];
            break;
    }
    
    document.getElementById('date_from').value = fromDate;
    document.getElementById('date_to').value = toDate;
    document.getElementById('filterForm').submit();
}

// Method filter removed

function clearSearch() {
    document.getElementById('search').value = '';
    document.getElementById('search').focus();
}

// Enhanced Delete Confirmation
function confirmDelete(studentName, receiptNumber) {
    return confirm(`Are you sure you want to delete payment ${receiptNumber} for ${studentName}?\n\nThis action will:\n• Remove the payment record\n• Update the student's balance\n• Cannot be undone\n\nClick OK to proceed or Cancel to abort.`);
}

// Export Functions
function exportToExcel() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = '{{ route("payments.export.excel") }}' + '?' + urlParams.toString();
    
    // Show loading state
    showToast('Preparing Excel export...', 'info');
    
    // Create a form and submit it for better Chrome compatibility
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = exportUrl;
    form.target = '_blank';
    form.style.display = 'none';
    
    // Add all current filter parameters as hidden inputs
    urlParams.forEach((value, key) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Show success message after a short delay
    setTimeout(() => {
        showToast('Excel file download started!', 'success');
    }, 1000);
}

function exportToPDF() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = '{{ route("payments.export.pdf") }}' + '?' + urlParams.toString();
    
    // Show loading state
    showToast('Preparing PDF export...', 'info');
    
    // Create a form and submit it for better Chrome compatibility
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = exportUrl;
    form.target = '_blank';
    form.style.display = 'none';
    
    // Add all current filter parameters as hidden inputs
    urlParams.forEach((value, key) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Show success message after a short delay
    setTimeout(() => {
        showToast('PDF file download started!', 'success');
    }, 1000);
}

function printTable() {
    window.print();
}

function refreshData() {
    const refreshBtn = document.querySelector('[onclick="refreshData()"]');
    const originalContent = refreshBtn.innerHTML;
    
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
    refreshBtn.disabled = true;
    
    // Simulate refresh (in real app, this would reload data)
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Toast Notification System
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${getToastIcon(type)} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 4000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function getToastIcon(type) {
    const icons = {
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'danger': 'times-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Restore saved view preference
    const savedView = localStorage.getItem('paymentView') || 'table';
    toggleView(savedView);
    
    // Auto-collapse filters on mobile
    if (window.innerWidth < 768) {
        const filtersCollapse = document.getElementById('filtersCollapse');
        if (filtersCollapse) {
            filtersCollapse.classList.remove('show');
        }
    }
    
    // Bootstrap 5 auto-initializes dropdowns, no custom code needed
});
</script>
@endsection
