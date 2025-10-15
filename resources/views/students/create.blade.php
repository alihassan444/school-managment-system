@extends('layouts.dashboard')

@section('title', 'Add New Student')

@section('content')
<div class="page-header">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="page-title">Add New Student</h1>
            <p class="page-subtitle">Enter basic student information only</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Students
        </a>
    </div>
</div>

<form action="{{ route('students.store') }}" method="POST" id="studentForm">
    @csrf
    
    <div class="row">
        <!-- Student Information -->
        <div class="col-12">
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
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="father_name" class="form-label">Father Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('father_name') is-invalid @enderror" 
                                   id="father_name" name="father_name" value="{{ old('father_name') }}" required>
                            @error('father_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="class" class="form-label">Class <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('class') is-invalid @enderror" 
                                   id="class" name="class" value="{{ old('class') }}" required>
                            @error('class')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="campus" class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select @error('campus') is-invalid @enderror" id="campus" name="campus" required>
                                <option value="">Select Campus</option>
                                <option value="campus_1" {{ old('campus') == 'campus_1' ? 'selected' : '' }}>Campus 1: Gulshan Town Thokar Niaz Baig Lahore</option>
                                <option value="campus_2" {{ old('campus') == 'campus_2' ? 'selected' : '' }}>Campus 2: 43 C, Rana Town Main Gt Road</option>
                            </select>
                            @error('campus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control @error('contact') is-invalid @enderror" 
                                   id="contact" name="contact" value="{{ old('contact') }}">
                            @error('contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror" 
                                   id="mobile" name="mobile" value="{{ old('mobile') }}">
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="dashboard-card mt-4">
                <div class="card-body text-center">
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                        <i class="fas fa-save me-2"></i>Save Student
                    </button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
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
</style>

<script>
// No fee calculations needed in simplified student form
</script>
@endsection
