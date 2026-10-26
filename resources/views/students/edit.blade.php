@extends('layouts.dashboard')
@section('title', 'Edit Student')
@section('eyebrow', 'Admissions')
@section('content')
<div class="page-header d-flex justify-content-between align-items-start gap-3"><div><span class="eyebrow">{{ $student->admission_no ?: 'Student #'.$student->id }}</span><h1>Edit {{ $student->name }}</h1><p>Keep identity, guardian and current enrollment details accurate.</p></div><a class="btn btn-outline-primary" href="{{ route('students.show',$student) }}">Profile</a></div>
@include('partials.flash')
<form action="{{ route('students.update',$student) }}" method="POST">@csrf @method('PUT') @include('students._form',['submitLabel'=>'Update student'])</form>
@endsection
