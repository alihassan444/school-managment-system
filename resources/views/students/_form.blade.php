@php($editing = isset($student))
@php($requestedFamily = !$editing && request('family_id') ? $families->firstWhere('id',(int)request('family_id')) : null)
<div class="row g-4">
<div class="col-xl-8">
    <div class="surface-card mb-4"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-id-card"></i>Student information</h2></div><div class="card-body"><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Admission no.</label><input class="form-control" name="admission_no" value="{{ old('admission_no',$student->admission_no ?? '') }}" placeholder="ADM-0001"></div>
        <div class="col-md-4"><label class="form-label">Roll no.</label><input class="form-control" name="roll_no" value="{{ old('roll_no',$student->roll_no ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" @selected(old('status',$student->status ?? 'active')==='active')>Active</option><option value="inactive" @selected(old('status',$student->status ?? '')==='inactive')>Inactive</option></select></div>
        <div class="col-md-6"><label class="form-label">Student name *</label><input class="form-control" name="name" value="{{ old('name',$student->name ?? '') }}" required></div>
        <div class="col-md-6"><label class="form-label">Father's name *</label><input class="form-control" name="father_name" value="{{ old('father_name',$student->father_name ?? '') }}" required></div>
        <div class="col-md-4"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth" value="{{ old('date_of_birth',isset($student) ? $student->date_of_birth?->toDateString() : '') }}"></div>
        <div class="col-md-4"><label class="form-label">Gender</label><select class="form-select" name="gender"><option value="">Select</option>@foreach(['male','female','other'] as $gender)<option value="{{ $gender }}" @selected(old('gender',$student->gender ?? '')===$gender)>{{ ucfirst($gender) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Admission date</label><input class="form-control" type="date" name="admission_date" value="{{ old('admission_date',isset($student) ? $student->admission_date?->toDateString() : today()->toDateString()) }}"></div>
        <div class="col-md-6"><label class="form-label">Mobile</label><input class="form-control" name="mobile" value="{{ old('mobile',$student->mobile ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label">Alternate contact</label><input class="form-control" name="contact" value="{{ old('contact',$student->contact ?? '') }}"></div>
        <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2">{{ old('address',$student->address ?? '') }}</textarea></div>
    </div></div></div>
    @unless($editing)
    <div class="surface-card family-admission-card mb-4">
        <div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-people-roof"></i>Family &amp; sibling information</h2><small class="text-muted">Create the first family record or link this admission to siblings already enrolled.</small></div></div>
        <div class="card-body">
            <div class="family-mode-switch" role="radiogroup" aria-label="Family admission type">
                <label><input type="radio" name="family_mode" value="new" @checked(old('family_mode',request('family_id')?'existing':'new')==='new')><span><i class="fa-solid fa-house-circle-plus"></i><strong>Create new family</strong><small>First child from this household</small></span></label>
                <label><input type="radio" name="family_mode" value="existing" @checked(old('family_mode',request('family_id')?'existing':'new')==='existing')><span><i class="fa-solid fa-link"></i><strong>Existing family</strong><small>Sibling admission</small></span></label>
            </div>
            <div id="newFamilyPanel" class="family-admission-panel mt-3">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Family display name *</label><input class="form-control" name="family_display_name" value="{{ old('family_display_name') }}" placeholder="e.g. Asim Family"></div>
                    <div class="col-md-6"><label class="form-label">Family notes</label><input class="form-control" name="family_notes" value="{{ old('family_notes') }}" placeholder="Optional office note"></div>
                </div>
            </div>
            <div id="existingFamilyPanel" class="family-admission-panel mt-3" hidden>
                <label class="form-label" for="familySearch">Find by Family ID, parent, phone, or sibling</label>
                <div class="input-group mb-3"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span><input class="form-control" id="familySearch" type="search" placeholder="Search existing families..."></div>
                <input type="hidden" name="family_id" id="selectedFamilyId" value="{{ old('family_id',request('family_id')) }}">
                <div id="selectedFamilySummary" class="selected-family-summary mb-3" @if(!$requestedFamily) hidden @endif>@if($requestedFamily)<i class="fa-solid fa-circle-check"></i><span><small>Linked family</small><strong>{{ $requestedFamily->family_code }} · {{ $requestedFamily->display_name }}</strong></span>@endif</div>
                <div class="family-match-list" id="familyMatchList">
                    @forelse($families as $family)
                    <article class="family-match-card" data-family-card data-search="{{ strtolower($family->family_code.' '.$family->display_name.' '.$family->primaryGuardian?->name.' '.$family->primaryGuardian?->phone.' '.$family->students->pluck('name')->implode(' ')) }}">
                        <div><span class="family-code">{{ $family->family_code }}</span><strong>{{ $family->display_name }}</strong><small>{{ $family->primaryGuardian?->name ?: 'No primary guardian' }} · {{ $family->primaryGuardian?->phone ?: 'No phone' }}</small></div>
                        <div class="family-match-children">@forelse($family->students as $sibling)<span>{{ $sibling->name }} · {{ $sibling->schoolClass?->name ?: $sibling->class }}</span>@empty<span>No children linked yet</span>@endforelse</div>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-select-family="{{ $family->id }}" data-family-label="{{ $family->family_code }} · {{ $family->display_name }}">Select family</button>
                    </article>
                    @empty<div class="empty-state py-3"><p>No family records yet. Choose Create New Family.</p></div>@endforelse
                </div>
            </div>
        </div>
    </div>
    @else
        @if($student->family)
        <div class="surface-card mb-4"><div class="card-body d-flex justify-content-between align-items-center gap-3"><div><small class="text-muted">Linked family</small><h3 class="h6 mb-0">{{ $student->family->family_code }} · {{ $student->family->display_name }}</h3></div><a class="btn btn-outline-primary btn-sm" href="{{ route('families.show',$student->family) }}">Open family</a></div></div>
        @endif
    @endunless
    <div class="surface-card" id="guardianFields"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-users"></i>Guardian details</h2></div><div class="card-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Guardian name</label><input class="form-control" name="guardian_name" value="{{ old('guardian_name',$student->guardian?->name ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">Relation</label><select class="form-select" name="guardian_relation">@foreach(['father'=>'Father','mother'=>'Mother','legal_guardian'=>'Legal guardian','grandparent'=>'Grandparent','other'=>'Other'] as $value=>$label)<option value="{{ $value }}" @selected(old('guardian_relation',$student->guardian?->relation ?? 'father')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Phone</label><input class="form-control" name="guardian_phone" value="{{ old('guardian_phone',$student->guardian?->phone ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Alternate phone</label><input class="form-control" name="guardian_alternate_phone" value="{{ old('guardian_alternate_phone',$student->guardian?->alternate_phone ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="guardian_email" value="{{ old('guardian_email',$student->guardian?->email ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">CNIC</label><input class="form-control" name="guardian_cnic" value="{{ old('guardian_cnic',$student->guardian?->cnic ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label">Occupation</label><input class="form-control" name="guardian_occupation" value="{{ old('guardian_occupation',$student->guardian?->occupation ?? '') }}"></div>
    </div><p class="text-muted small mt-3 mb-0" id="guardianReuseNote" hidden><i class="fa-solid fa-circle-info me-1"></i>The selected family’s primary guardian will be reused. Shared guardian data will not be overwritten.</p></div></div>
</div>
<div class="col-xl-4">
    <div class="surface-card mb-4"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-graduation-cap"></i>Current enrollment</h2></div><div class="card-body"><div class="row g-3">
        <div class="col-12"><label class="form-label">Academic session</label><select class="form-select" name="academic_session_id"><option value="">Not assigned</option>@foreach($academicSessions as $session)<option value="{{ $session->id }}" @selected(old('academic_session_id',$student->academic_session_id ?? $academicSessions->firstWhere('is_current',true)?->id)===$session->id)>{{ $session->name }}{{ $session->is_current ? ' · Current':'' }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Structured class</label><select class="form-select" id="school_class_id" name="school_class_id"><option value="">Use legacy class label</option>@foreach($schoolClasses as $class)<option value="{{ $class->id }}" data-fee="{{ $class->monthly_fee }}" @selected(old('school_class_id',$student->school_class_id ?? '')==$class->id)>{{ $class->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Section</label><select class="form-select" name="section_id"><option value="">No section</option>@foreach($schoolClasses as $class)@foreach($class->sections as $section)<option value="{{ $section->id }}" @selected(old('section_id',$student->section_id ?? '')==$section->id)>{{ $class->name }} · {{ $section->name }}</option>@endforeach @endforeach</select></div>
        <div class="col-12"><label class="form-label">Legacy class label</label><input class="form-control" name="class" value="{{ old('class',$student->class ?? '') }}" placeholder="Only needed without a structured class"></div>
        <div class="col-12"><label class="form-label">Campus *</label><select class="form-select" name="campus" required><option value="campus_1" @selected(old('campus',$student->campus ?? 'campus_1')==='campus_1')>Campus 1 · Thokar Niaz Baig</option><option value="campus_2" @selected(old('campus',$student->campus ?? '')==='campus_2')>Campus 2 · Rana Town</option></select></div>
        <div class="col-12"><label class="form-label">Monthly fee *</label><div class="input-group"><span class="input-group-text">Rs</span><input class="form-control" id="monthly_fee" type="number" name="monthly_fee" value="{{ old('monthly_fee',$student->monthly_fee ?? 0) }}" min="0" step="0.01" required></div></div>
    </div></div></div>
    <div class="surface-card"><div class="card-body d-grid gap-2"><button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-2"></i>{{ $submitLabel }}</button><a class="btn btn-outline-primary" href="{{ route('students.index') }}">Cancel</a></div></div>
</div></div>
@push('scripts')<script>
document.getElementById('school_class_id')?.addEventListener('change',event=>{const option=event.target.options[event.target.selectedIndex];if(option?.dataset.fee){document.getElementById('monthly_fee').value=option.dataset.fee;}});
const familyModes=[...document.querySelectorAll('input[name="family_mode"]')];
const newFamilyPanel=document.getElementById('newFamilyPanel');
const existingFamilyPanel=document.getElementById('existingFamilyPanel');
const guardianFields=document.getElementById('guardianFields');
const guardianReuseNote=document.getElementById('guardianReuseNote');
const syncFamilyMode=()=>{const mode=familyModes.find(input=>input.checked)?.value||'new';if(newFamilyPanel)newFamilyPanel.hidden=mode!=='new';if(existingFamilyPanel)existingFamilyPanel.hidden=mode!=='existing';guardianFields?.querySelectorAll('input,select').forEach(input=>input.disabled=mode==='existing');if(guardianReuseNote)guardianReuseNote.hidden=mode!=='existing';};
familyModes.forEach(input=>input.addEventListener('change',syncFamilyMode));syncFamilyMode();
document.getElementById('familySearch')?.addEventListener('input',event=>{const term=event.target.value.toLowerCase().trim();document.querySelectorAll('[data-family-card]').forEach(card=>card.hidden=term&&!card.dataset.search.includes(term));});
document.querySelectorAll('[data-select-family]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('selectedFamilyId').value=button.dataset.selectFamily;const summary=document.getElementById('selectedFamilySummary');summary.innerHTML=`<i class="fa-solid fa-circle-check"></i><span><small>Linked family</small><strong>${button.dataset.familyLabel}</strong></span>`;summary.hidden=false;document.querySelectorAll('[data-family-card]').forEach(card=>card.classList.toggle('is-selected',card.contains(button)));}));
</script>@endpush
