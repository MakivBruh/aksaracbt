@extends('layouts.admin')

@section('title', 'Buat Token Tryout')

@section('content')
@include('admin.sessions.form', [
    'action' => route('admin.sessions.store'),
    'method' => 'POST',
    'session' => null,
])
@endsection
