@extends('layouts.app')

@section('title', 'Edit Admin')

@section('content')
<h1 class="h4 mb-3">Edit Admin: {{ $admin->user->name }}</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admins.update', $admin) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label">Privilege Level (1–5)</label>
                <input type="number" name="privilege_level" class="form-control"
                       min="1" max="5" value="{{ old('privilege_level', $admin->privilege_level) }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admins.index') }}" class="btn btn-outline-secondary">Back</a>
        </form>
    </div>
</div>
@endsection
