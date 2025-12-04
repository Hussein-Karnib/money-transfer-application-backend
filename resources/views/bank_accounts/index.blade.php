@extends('layouts.app')

@section('title', 'Bank Accounts')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-bank me-2"></i>Bank Accounts</h1>
        <p>Manage your linked bank accounts</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-plus-circle me-2"></i>Add Bank Account
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('bank-accounts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-modern">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control form-control-modern @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" required>
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Currency</label>
                        <select name="currency_code" class="form-select form-control-modern @error('currency_code') is-invalid @enderror" required>
                            <option value="">Select Currency</option>
                            @foreach(App\Models\Currency::all() as $currency)
                                <option value="{{ $currency->code }}" {{ old('currency_code') == $currency->code ? 'selected' : '' }}>{{ $currency->code }} - {{ $currency->name }}</option>
                            @endforeach
                        </select>
                        @error('currency_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                        <i class="bi bi-plus-circle me-2"></i>Add Bank Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($bankAccounts->count() > 0)
            <div class="card-modern">
                <div class="card-header">
                    <i class="bi bi-list-ul me-2"></i>Your Bank Accounts ({{ $bankAccounts->count() }})
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern">
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
                                        <td><strong>#{{ $account->id }}</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-bank me-2" style="color: #667eea;"></i>
                                                <strong>{{ $account->bank_name }}</strong>
                                            </div>
                                        </td>
                                        <td><code>{{ $account->account_number ?? 'N/A' }}</code></td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $account->currency_code }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $account->status === 'verified' ? 'success' : 'warning' }} badge-modern">
                                                {{ ucfirst($account->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('bank-accounts.destroy', $account->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this bank account?');">
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
                    <i class="bi bi-bank" style="font-size: 4rem; color: #cbd5e0;"></i>
                    <h4 class="mt-3 mb-2">No Bank Accounts Yet</h4>
                    <p class="text-muted">Add your first bank account to get started</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
