@extends('layouts.dashboard')
@section('title', 'Add Student')
@section('eyebrow', 'Admissions')
@section('content')
<div class="page-header d-flex justify-content-between align-items-start gap-3"><div><span class="eyebrow">New admission</span><h1>Add a student</h1><p>Create identity, guardian and current enrollment data in one record.</p></div><a class="btn btn-outline-primary" href="{{ route('students.index') }}">Students</a></div>
@include('partials.flash')
<form action="{{ route('students.store') }}" method="POST">@csrf @include('students._form',['submitLabel'=>'Save student'])</form>
@endsection
