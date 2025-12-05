@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-person-circle me-2"></i>Your Profile</h1>
        <p>View and update your personal information.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}"
                             alt="Avatar" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white"
                             style="width: 120px; height: 120px; font-size: 2.5rem;">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                </div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->email }}</p>
                <span class="badge bg-{{ ($user->status ?? 'pending') === 'approved' ? 'success' : 'warning' }}">
                    {{ ucfirst($user->status ?? 'pending') }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>Edit Profile
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-modern">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-modern" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-modern" value="{{ old('phone', $user->phone) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Profile Picture</label>
                        <input type="file" name="avatar" class="form-control form-control-modern" accept="image/*">
                        <small class="text-muted">Upload an image from your device.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Card / Account Number</label>
                        <input type="text" class="form-control form-control-modern" value="{{ $primaryAccount?->account_number ?? 'N/A' }}" disabled>
                        <small class="text-muted">Pulled from your first linked bank account.</small>
                    </div>
                    <button type="submit" class="btn btn-primary-modern btn-modern">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
