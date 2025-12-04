@extends('layouts.app')

@section('title', 'Beneficiaries')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-people me-2"></i>Beneficiaries</h1>
        <p>Manage your beneficiaries for quick money transfers</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-person-plus me-2"></i>Add Beneficiary
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('beneficiaries.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-modern">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-modern @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Country</label>
                        <select name="country_id" class="form-select form-control-modern @error('country_id') is-invalid @enderror" required>
                            <option value="">Select Country</option>
                            @foreach(App\Models\Country::all() as $country)
                                <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                            @endforeach
                        </select>
                        @error('country_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Transfer Method</label>
                        <select name="transfer_method_id" class="form-select form-control-modern @error('transfer_method_id') is-invalid @enderror" required>
                            <option value="">Select Method</option>
                            @foreach(App\Models\Transfer_Method::all() as $method)
                                <option value="{{ $method->id }}" {{ old('transfer_method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @error('transfer_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                        <i class="bi bi-plus-circle me-2"></i>Add Beneficiary
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($beneficiaries->count() > 0)
            <div class="card-modern">
                <div class="card-header">
                    <i class="bi bi-list-ul me-2"></i>Your Beneficiaries ({{ $beneficiaries->count() }})
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Country</th>
                                    <th>Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($beneficiaries as $beneficiary)
                                    <tr>
                                        <td><strong>#{{ $beneficiary->id }}</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($beneficiary->full_name, 0, 1)) }}
                                                </div>
                                                <strong>{{ $beneficiary->full_name }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-globe me-1"></i>{{ $beneficiary->country->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info badge-modern">
                                                {{ $beneficiary->method->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('beneficiaries.destroy', $beneficiary->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this beneficiary?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="card-modern">
                <div class="card-body text-center py-5">
                    <i class="bi bi-people" style="font-size: 4rem; color: #cbd5e0;"></i>
                    <h4 class="mt-3 mb-2">No Beneficiaries Yet</h4>
                    <p class="text-muted">Add your first beneficiary to start sending money transfers</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
