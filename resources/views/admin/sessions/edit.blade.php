@extends('layouts.admin')

@section('title', 'Edit Token Tryout')

@section('content')
@include('admin.sessions.form', [
    'action' => route('admin.sessions.update', $session),
    'method' => 'PUT',
    'session' => $session,
])
@endsection
