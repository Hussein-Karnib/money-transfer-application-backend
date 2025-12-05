@extends('layouts.app')

@section('title', 'Identity Verification')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-shield-check me-2"></i>Identity Verification</h1>
        <p>Upload a clear, non-expired government-issued ID to enable bank accounts.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-upload me-2"></i>Submit your document
            </div>
            <div class="card-body">
                @include('partials.alerts')

                <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-modern">Document Type</label>
                        <select name="id_type" class="form-select form-control-modern @error('id_type') is-invalid @enderror" required>
                            <option value="">Select document type</option>
                            <option value="passport" {{ old('id_type') === 'passport' ? 'selected' : '' }}>Passport</option>
                            <option value="national_id" {{ old('id_type') === 'national_id' ? 'selected' : '' }}>National ID</option>
                            <option value="drivers_license" {{ old('id_type') === 'drivers_license' ? 'selected' : '' }}>Driver's License</option>
                        </select>
                        @error('id_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">ID Number</label>
                        <input type="text" name="id_number" class="form-control form-control-modern @error('id_number') is-invalid @enderror" value="{{ old('id_number') }}" required>
                        @error('id_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control form-control-modern @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}" required>
                        @error('expiry_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Upload Document (JPG, PNG, PDF)</label>
                        <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" class="form-control form-control-modern @error('document') is-invalid @enderror" required>
                        @error('document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">Make sure the image is clear, in-frame, and not expired.</small>
                    </div>

                    <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                        <i class="bi bi-cloud-upload me-2"></i>Submit for review
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Status & Tips
            </div>
            <div class="card-body">
                @if($latestVerification)
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-{{ $latestVerification->status === 'verified' ? 'success' : ($latestVerification->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($latestVerification->status) }}</span>
                            @if($latestVerification->expiry_date)
                                <span class="badge bg-light text-dark ms-2">Expires {{ $latestVerification->expiry_date->toFormattedDateString() }}</span>
                            @endif
                        </div>
                        <div class="small text-muted">
                            Submitted {{ $latestVerification->created_at->diffForHumans() }}
                            @if($latestVerification->review_comment)
                                <div class="mt-1">Reviewer note: {{ $latestVerification->review_comment }}</div>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-muted">No submissions yet.</p>
                @endif

                <ul class="small mb-0">
                    <li>Use a clear photo or scan, all corners visible.</li>
                    <li>Make sure the document is valid and not expired.</li>
                    <li>Blurry or cropped images will be rejected.</li>
                    <li>We’ll review and update your status shortly.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
