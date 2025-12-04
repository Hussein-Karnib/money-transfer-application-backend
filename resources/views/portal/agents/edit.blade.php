@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Edit Store Details</h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.agents.update', $agent) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="store_name" class="form-label">Store Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('store_name') is-invalid @enderror" 
                               id="store_name" name="store_name" 
                               value="{{ old('store_name', $agent->store_name) }}" required>
                        @error('store_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Store Address <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="3" required>{{ old('address', $agent->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label">Latitude (Optional)</label>
                            <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" 
                                   id="latitude" name="latitude" 
                                   value="{{ old('latitude', $agent->latitude) }}" 
                                   placeholder="e.g., 40.7128">
                            <small class="form-text text-muted">For map location</small>
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label">Longitude (Optional)</label>
                            <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" 
                                   id="longitude" name="longitude" 
                                   value="{{ old('longitude', $agent->longitude) }}" 
                                   placeholder="e.g., -74.0060">
                            <small class="form-text text-muted">For map location</small>
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> Updating your store details will require admin approval if your status changes.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Store Details</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Store Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Owner:</dt>
                    <dd class="col-sm-8">{{ $agent->user->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8">{{ $agent->user->email ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        @if($agent->status === 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($agent->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @else
                            <span class="badge bg-danger">Suspended</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

