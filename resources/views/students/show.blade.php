@extends('layouts.dashboard')

@section('title', 'Student Details')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">{{ $student->name }}</h1>
            <p class="page-subtitle">Student ID: #{{ $student->id }} | Class: {{ $student->class }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('students.edit', $student) }}" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit Student
            </a>
            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Student Information -->
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user me-2"></i>Student Information
                </h3>
                <div class="badge bg-{{ $student->status == 'active' ? 'success' : 'secondary' }} fs-6">
                    {{ ucfirst($student->status) }}
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="info-item">
                            <label class="info-label">Full Name</label>
                            <div class="info-value">{{ $student->name }}</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="info-item">
                            <label class="info-label">Father's Name</label>
                            <div class="info-value">{{ $student->father_name }}</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="info-item">
                            <label class="info-label">Class</label>
                            <div class="info-value">
                                <span class="badge bg-info fs-6">{{ $student->class }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="info-item">
                            <label class="info-label">Contact Number</label>
                            <div class="info-value">
                                {{ $student->contact ?: 'Not provided' }}
                                @if($student->contact)
                                    <a href="tel:{{ $student->contact }}" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="info-item">
                            <label class="info-label">Mobile Number</label>
                            <div class="info-value">
                                {{ $student->mobile ?: 'Not provided' }}
                                @if($student->mobile)
                                    <a href="tel:{{ $student->mobile }}" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-mobile-alt"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="info-item">
                            <label class="info-label">Registration Date</label>
                            <div class="info-value">{{ $student->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                    
                    @if($student->address)
                        <div class="col-12 mb-4">
                            <div class="info-item">
                                <label class="info-label">Address</label>
                                <div class="info-value">{{ $student->address }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
         <!-- Payment History -->
         <div class="dashboard-card">
             <div class="card-header">
                 <h3 class="card-title">
                     <i class="fas fa-history me-2"></i>Payment History
                 </h3>
                 <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-outline-light btn-sm">
                     <i class="fas fa-plus me-1"></i>Add Payment
                 </a>
             </div>
             <div class="card-body">
                 @if($student->payments->count() > 0)
                     <div class="table-responsive">
                         <table class="table table-hover">
                             <thead>
                                 <tr>
                                     <th>Date</th>
                                     <th>Receipt No.</th>
                                     
                                     <th class="text-end">Amount</th>
                                     <th>Actions</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @foreach($student->payments->take(10) as $payment)
                                     <tr>
                                         <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                         <td>
                                             <span class="badge bg-primary">{{ $payment->receipt_number }}</span>
                                         </td>
                                         
                                         <td class="text-end">
                                             <strong class="text-success">Rs {{ number_format($payment->amount, 0) }}</strong>
                                         </td>
                                         <td>
                                             <div class="btn-group" role="group">
                                                 <a href="{{ route('payments.show', $payment) }}" 
                                                    class="btn btn-outline-info btn-sm" title="View Receipt">
                                                     <i class="fas fa-receipt"></i>
                                                 </a>
                                                 <button type="button" class="btn btn-outline-danger btn-sm" 
                                                         onclick="deletePayment({{ $payment->id }})" title="Delete Payment">
                                                     <i class="fas fa-trash"></i>
                                                 </button>
                                             </div>
                                         </td>
                                     </tr>
                                 @endforeach
                             </tbody>
                         </table>
                     </div>
                     
                     @if($student->payments->count() > 10)
                         <div class="text-center mt-3">
                             <small class="text-muted">Showing latest 10 payments</small>
                         </div>
                     @endif
                 @else
                     <div class="text-center py-4">
                         <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                         <p class="text-muted">No payment history found</p>
                         <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-primary">
                             <i class="fas fa-plus me-2"></i>Record First Payment
                         </a>
                     </div>
                 @endif
             </div>
         </div>
    </div>
    
    <!-- Right column simplified -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h3>
            </div>
            <div class="card-body">
                 <div class="d-grid gap-2">
                     <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-success">
                         <i class="fas fa-money-bill me-2"></i>Record Payment
                     </a>
                     <a href="{{ route('payments.fee-bill', $student) }}" class="btn btn-info" target="_blank">
                         <i class="fas fa-print me-2"></i>Print Fee Bill
                     </a>
                     <a href="{{ route('students.edit', $student) }}" class="btn btn-primary">
                         <i class="fas fa-edit me-2"></i>Edit Student
                     </a>
                     <button class="btn btn-danger" onclick="deleteStudent({{ $student->id }})">
                         <i class="fas fa-trash me-2"></i>Delete Student
                     </button>
                 </div>
            </div>
        </div>
        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h3>
            </div>
            <div class="card-body">
                 <div class="d-grid gap-2">
                     <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-success">
                         <i class="fas fa-money-bill me-2"></i>Record Payment
                     </a>
                     
                     <a href="{{ route('payments.fee-bill', $student) }}" class="btn btn-info" target="_blank">
                         <i class="fas fa-print me-2"></i>Print Fee Bill
                     </a>
                     
                     <a href="{{ route('students.edit', $student) }}" class="btn btn-primary">
                         <i class="fas fa-edit me-2"></i>Edit Student
                     </a>
                     
                     <button class="btn btn-danger" onclick="deleteStudent({{ $student->id }})">
                         <i class="fas fa-trash me-2"></i>Delete Student
                     </button>
                 </div>
            </div>
        </div>
    </div>
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
                Are you sure you want to delete <strong>{{ $student->name }}</strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="{{ route('students.destroy', $student) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Student</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0 !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.info-item {
    padding: 0.5rem 0;
}

.info-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 500;
    color: #374151;
}

.fee-item .fee-label {
    color: #6b7280;
    font-size: 0.95rem;
}

.fee-item .fee-value {
    font-weight: 600;
    font-size: 1rem;
}

.alert {
    border-radius: 12px;
    border: none;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}

.badge {
    border-radius: 8px;
}
</style>

<script>
function deleteStudent(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function deletePayment(paymentId) {
    if (confirm('Are you sure you want to delete this payment? This will update the student\'s balance.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/payments/${paymentId}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
