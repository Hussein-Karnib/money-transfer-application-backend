@extends('layouts.app')

@section('content')
<h2>Bank Accounts</h2>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Add Bank Account</h5>
                <form method="POST" action="{{ route('bank-accounts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" required>
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select name="currency_code" class="form-select @error('currency_code') is-invalid @enderror" required>
                            <option value="">Select Currency</option>
                            @foreach(App\Models\Currency::all() as $currency)
                                <option value="{{ $currency->code }}" {{ old('currency_code') == $currency->code ? 'selected' : '' }}>{{ $currency->code }} - {{ $currency->name }}</option>
                            @endforeach
                        </select>
                        @error('currency_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Add Bank Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($bankAccounts->count() > 0)
    <table class="table table-hover">
        <thead>
        <tr>
            <th>#</th>
            <th>Bank Name</th>
            <th>Account Number</th>
            <th>Currency</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($bankAccounts as $account)
            <tr>
                <td>{{ $account->id }}</td>
                <td>{{ $account->bank_name }}</td>
                <td>{{ $account->account_number ?? 'N/A' }}</td>
                <td>{{ $account->currency_code }}</td>
                <td>
                    <span class="badge bg-{{ $account->status === 'verified' ? 'success' : 'warning' }}">
                        {{ ucfirst($account->status ?? 'pending') }}
                    </span>
                </td>
                <td>
                    <form method="POST" action="{{ route('bank-accounts.destroy', $account->id) }}" class="d-inline" onsubmit="return confirm('Delete this bank account?');">
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
        <p class="mb-0">No bank accounts found. Add your first bank account above.</p>
    </div>
@endif
@endsection
