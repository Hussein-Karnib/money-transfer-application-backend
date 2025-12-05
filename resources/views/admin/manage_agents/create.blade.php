@extends('layouts.app')

@section('title', 'Create Admin')

@section('content')
<h1 class="h4 mb-3">Create New Admin</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admins.store') }}" method="POST">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Privilege Level (1–5)</label>
                    <input type="number" name="privilege_level" class="form-control"
                           min="1" max="5" value="{{ old('privilege_level', 1) }}" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Create Admin</button>
            <a href="{{ route('admins.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
