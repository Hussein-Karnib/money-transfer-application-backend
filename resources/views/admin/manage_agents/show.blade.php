@extends('layouts.app')

@section('title', 'Admin Details')

@section('content')
<h1 class="h4 mb-3">Admin Details</h1>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $admin->user->name }}</dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9">{{ $admin->user->email }}</dd>

            <dt class="col-sm-3">Privilege Level</dt>
            <dd class="col-sm-9">{{ $admin->privilege_level }}</dd>
        </dl>
    </div>
</div>

<a href="{{ route('admins.edit', $admin) }}" class="btn btn-primary">Edit</a>
<a href="{{ route('admins.index') }}" class="btn btn-outline-secondary">Back</a>
@endsection
