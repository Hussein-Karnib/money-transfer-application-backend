@extends('layouts.app')

@section('content')
<h2>Beneficiaries</h2>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Add Beneficiary</h5>
                <form method="POST" action="{{ route('beneficiaries.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <select name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
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
                        <label class="form-label">Transfer Method</label>
                        <select name="transfer_method_id" class="form-select @error('transfer_method_id') is-invalid @enderror" required>
                            <option value="">Select Method</option>
                            @foreach(App\Models\Transfer_Method::all() as $method)
                                <option value="{{ $method->id }}" {{ old('transfer_method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @error('transfer_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Add Beneficiary</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($beneficiaries->count() > 0)
    <table class="table table-hover">
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
                <td>{{ $beneficiary->id }}</td>
                <td>{{ $beneficiary->full_name }}</td>
                <td>{{ $beneficiary->country->name ?? '-' }}</td>
                <td>{{ $beneficiary->method->name ?? '-' }}</td>
                <td>
                    <form method="POST" action="{{ route('beneficiaries.destroy', $beneficiary->id) }}" class="d-inline" onsubmit="return confirm('Delete this beneficiary?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="alert alert-info">
        <p class="mb-0">No beneficiaries found. Add your first beneficiary above.</p>
    </div>
@endif
@endsection
