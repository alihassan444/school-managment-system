@extends('layouts.dashboard')

@section('title', 'Record Payment')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Record Payment</h1>
            <p class="page-subtitle">Add a new payment record for student</p>
        </div>
        <a href="{{ $student ? route('students.show', $student) : route('students.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<form action="{{ route('payments.store') }}" method="POST" id="paymentForm">
    @csrf
    
    <div class="row">
        <!-- Payment Information -->
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-money-bill-wave me-2"></i>Payment Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">Select Student <span class="text-danger">*</span></label>
                            <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required onchange="updateStudentInfo()">
                                <option value="">Choose a student...</option>
                                @foreach($students as $studentOption)
                                    <option value="{{ $studentOption->id }}" 
                                            {{ ($student && $student->id == $studentOption->id) || old('student_id') == $studentOption->id ? 'selected' : '' }}>
                                        {{ $studentOption->name }} - {{ $studentOption->class }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                   id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Simple Amount -->
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rs</span>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" name="amount" value="{{ old('amount', 0) }}" 
                                       min="0.01" step="0.01">
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description/Notes</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Any additional notes about this payment">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description/Notes</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Any additional notes about this payment">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Student Fee Summary -->
        <div class="col-lg-4">
            <div class="dashboard-card" id="studentSummary" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user me-2"></i>Student Summary
                    </h3>
                </div>
                <div class="card-body">
                    <div class="student-info mb-4">
                        <h5 id="studentName">-</h5>
                        <p class="text-muted mb-0" id="studentClass">-</p>
                    </div>
                    
                    <div class="fee-summary d-none"></div>
                </div>
            </div>
            
            <!-- Quick Amount Buttons removed in simplified flow -->
            
            <!-- Action Buttons -->
            <div class="dashboard-card">
                <div class="card-body text-center">
                    <button type="submit" class="btn btn-success btn-lg w-100 mb-2" id="submitButton">
                        <i class="fas fa-save me-2"></i>Record Payment
                    </button>
                    <a href="{{ $student ? route('students.show', $student) : route('students.index') }}" class="btn btn-outline-secondary w-100">
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

.input-group-text {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    font-weight: 600;
}

.form-control:focus, .form-select:focus {
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

.student-info h5 {
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.fee-summary {
    font-size: 0.95rem;
}
</style>

<script>
function updateStudentInfo() {
    const select = document.getElementById('student_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        // Show summary cards
        document.getElementById('studentSummary').style.display = 'block';
        
        // Update student info
        document.getElementById('studentName').textContent = selectedOption.text.split(' - ')[0];
        document.getElementById('studentClass').textContent = 'Class: ' + selectedOption.text.split(' - ')[1].split(' (')[0];
    } else {
        document.getElementById('studentSummary').style.display = 'none';
    }
}
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateStudentInfo();
});
</script>
@endsection
