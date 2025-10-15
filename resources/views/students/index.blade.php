@extends('layouts.dashboard')

@section('title', 'Students')

@section('content')
<div class="page-header">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="page-title">Students Management</h1>
            <p class="page-subtitle">Manage all student records and fees</p>
        </div>
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Student
        </a>
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

<!-- Filters -->
<div class="dashboard-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('students.index') }}" class="row g-3">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="{{ request('search') }}" placeholder="Search by name, father name, contact...">
            </div>
            <div class="col-6 col-md-3">
                <label for="class" class="form-label">Class</label>
                <select class="form-select" id="class" name="class">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class }}" {{ request('class') == $class ? 'selected' : '' }}>
                            {{ $class }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="campus" class="form-label">Campus</label>
                <select class="form-select" id="campus" name="campus">
                    <option value="">All Campus</option>
                    <option value="campus_1" {{ request('campus') == 'campus_1' ? 'selected' : '' }}>Campus 1</option>
                    <option value="campus_2" {{ request('campus') == 'campus_2' ? 'selected' : '' }}>Campus 2</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3 class="card-title">Students List ({{ $students->total() }} students)</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-1"></i>Export
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="printTable()">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="studentsTable">
            <thead class="table-light">
                <tr>
                    <th class="d-none d-md-table-cell">#</th>
                    <th>Name</th>
                    <th class="d-none d-lg-table-cell">Father Name</th>
                    <th>Class</th>
                    <th>Campus</th>
                    <th class="d-none d-xl-table-cell">Contact</th>
                    <th class="d-none d-md-table-cell">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td class="d-none d-md-table-cell">{{ $students->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="student-avatar me-2">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                                <div>
                                    <strong>{{ $student->name }}</strong>
                                    <div class="d-block d-lg-none">
                                        <small class="text-muted">{{ $student->father_name }}</small>
                                    </div>
                                    @if($student->mobile)
                                        <br><small class="text-muted d-block d-xl-none">{{ $student->mobile }}</small>
                                    @endif
                                    <div class="d-block d-md-none mt-1">
                                        <span class="badge bg-primary me-1">
                                            {{ ucfirst(str_replace('_', ' ', $student->campus)) }}
                                        </span>
                                        @if($student->status == 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell">{{ $student->father_name }}</td>
                        <td>
                            <span class="badge bg-info">{{ $student->class }}</span>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                {{ ucfirst(str_replace('_', ' ', $student->campus)) }}
                            </span>
                        </td>
                        <td class="d-none d-xl-table-cell">{{ $student->contact ?: 'N/A' }}</td>
                        <td class="d-none d-md-table-cell">
                            @if($student->status == 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group-vertical d-block d-sm-none" role="group">
                                <a href="{{ route('students.show', $student) }}" 
                                   class="btn btn-outline-info btn-sm mb-1" title="View">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <a href="{{ route('students.edit', $student) }}" 
                                   class="btn btn-outline-primary btn-sm mb-1" title="Edit">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <form method="POST" action="{{ route('students.destroy', $student) }}" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete {{ $student->name }}? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                            <div class="btn-group d-none d-sm-flex" role="group">
                                <a href="{{ route('students.show', $student) }}" 
                                   class="btn btn-outline-info btn-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('students.edit', $student) }}" 
                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('students.destroy', $student) }}" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete {{ $student->name }}? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No students found</p>
                            <a href="{{ route('students.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add First Student
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($students->hasPages())
        <div class="card-footer">
            {{ $students->links() }}
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this student? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
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
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.btn-group .btn {
    border-radius: 6px !important;
    margin-right: 2px;
}

.alert {
    border-radius: 12px;
    border: none;
}

.dashboard-card .table-responsive {
    border-radius: 0 0 16px 16px;
}
</style>

<script>
function deleteStudent(id) {
    console.log('Delete function called for student ID:', id);
    
    const form = document.getElementById('deleteForm');
    if (!form) {
        console.error('Delete form not found');
        return;
    }
    
    form.action = `/students/${id}`;
    console.log('Form action set to:', form.action);
    
    // Try Bootstrap 5 first, then fallback to Bootstrap 4, then native confirm
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        console.log('Using Bootstrap 5 modal');
        const modalElement = document.getElementById('deleteModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('Modal element not found');
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                form.submit();
            }
        }
    } else if (typeof $ !== 'undefined' && $.fn.modal) {
        console.log('Using Bootstrap 4/jQuery modal');
        $('#deleteModal').modal('show');
    } else {
        console.log('Using native confirm dialog');
        // Fallback to native confirm
        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            form.submit();
        }
    }
}

function exportToExcel() {
    // Simple export functionality - requires XLSX library
    if (typeof XLSX !== 'undefined') {
        const table = document.getElementById('studentsTable');
        const wb = XLSX.utils.table_to_book(table);
        XLSX.writeFile(wb, 'students.xlsx');
    } else {
        alert('Export functionality requires XLSX library. Please install it or use browser print.');
    }
}

function printTable() {
    window.print();
}

// Alternative delete function using fetch API for better error handling
function deleteStudentAjax(id) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        fetch(`/students/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Error deleting student. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting student. Please try again.');
        });
    }
}
</script>
@endsection
