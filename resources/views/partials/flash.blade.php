@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
