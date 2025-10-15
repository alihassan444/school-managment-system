@extends('layouts.dashboard')

@section('title', 'Edit Student')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Edit Student</h1>
            <p class="page-subtitle">Update {{ $student->name }}'s information</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('students.show', $student) }}" class="btn btn-outline-info">
                <i class="fas fa-eye me-2"></i>View Details
            </a>
            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
    </div>
</div>

<form action="{{ route('students.update', $student) }}" method="POST" id="studentForm">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Student Information -->
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user me-2"></i>Student Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Student Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $student->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="father_name" class="form-label">Father Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('father_name') is-invalid @enderror" 
                                   id="father_name" name="father_name" value="{{ old('father_name', $student->father_name) }}" required>
                            @error('father_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="class" class="form-label">Class <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('class') is-invalid @enderror" 
                                   id="class" name="class" value="{{ old('class', $student->class) }}" required>
                            @error('class')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="campus" class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select @error('campus') is-invalid @enderror" id="campus" name="campus" required>
                                <option value="">Select Campus</option>
                                <option value="campus_1" {{ old('campus', $student->campus) == 'campus_1' ? 'selected' : '' }}>Campus 1: Gulshan Town Thokar Niaz Baig Lahore</option>
                                <option value="campus_2" {{ old('campus', $student->campus) == 'campus_2' ? 'selected' : '' }}>Campus 2: 43 C, Rana Town Main Gt Road</option>
                            </select>
                            @error('campus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ old('status', $student->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $student->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control @error('contact') is-invalid @enderror" 
                                   id="contact" name="contact" value="{{ old('contact', $student->contact) }}">
                            @error('contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror" 
                                   id="mobile" name="mobile" value="{{ old('mobile', $student->mobile) }}">
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3">{{ old('address', $student->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="col-lg-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-save me-2"></i>Actions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" form="studentForm" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Update Student
                        </button>
                        <a href="{{ route('students.show', $student) }}" class="btn btn-outline-info">
                            <i class="fas fa-eye me-2"></i>View Student
                        </a>
                        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Students
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <h6 class="text-muted mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-plus me-1"></i>Add Payment
                            </a>
                            <a href="{{ route('payments.index', ['search' => $student->name]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-receipt me-1"></i>View Payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0 !important;
}

.card-header .card-title {
    color: white !important;
    margin: 0;
    font-weight: 600;
}

.card-header .card-title i {
    color: white !important;
}

.card-header {
    padding: 1.5rem;
    border-bottom: none;
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

.input-group-text {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    font-weight: 600;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

.gap-3 {
    gap: 1rem;
}

.btn-outline-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.btn-outline-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
}

.btn-outline-secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('studentForm');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        submitBtn.disabled = true;
    });
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Auto-save draft functionality (optional)
    let autoSaveTimeout;
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                // Auto-save to localStorage as draft
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                localStorage.setItem('studentEditDraft', JSON.stringify(data));
            }, 2000);
        });
    });
    
    // Load draft on page load
    const draft = localStorage.getItem('studentEditDraft');
    if (draft) {
        try {
            const data = JSON.parse(draft);
            Object.keys(data).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && field.type !== 'file') {
                    field.value = data[key];
                }
            });
        } catch (e) {
            console.log('No valid draft found');
        }
    }
    
    // Clear draft on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem('studentEditDraft');
    });
});
</script>
@endsection
