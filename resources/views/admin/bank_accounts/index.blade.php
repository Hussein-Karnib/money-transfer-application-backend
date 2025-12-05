@extends('layouts.app')

@section('title', 'Bank Account Verification')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-bank me-2"></i>Bank Account Verification</h1>
        <p>Review and verify pending bank accounts</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card-modern">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-list-ul me-2"></i>Bank Accounts
            </div>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.bank-accounts.index', ['status' => 'pending']) }}" 
                   class="btn btn-sm {{ request('status') === 'pending' || !request('status') ? 'btn-primary-modern' : 'btn-outline-modern' }}">
                    Pending
                </a>
                <a href="{{ route('admin.bank-accounts.index', ['status' => 'verified']) }}" 
                   class="btn btn-sm {{ request('status') === 'verified' ? 'btn-primary-modern' : 'btn-outline-modern' }}">
                    Verified
                </a>
                <a href="{{ route('admin.bank-accounts.index', ['status' => 'rejected']) }}" 
                   class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-primary-modern' : 'btn-outline-modern' }}">
                    Rejected
                </a>
                <a href="{{ route('admin.bank-accounts.index') }}" 
                   class="btn btn-sm {{ !request('status') ? 'btn-primary-modern' : 'btn-outline-modern' }}">
                    All
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if($bankAccounts->count() > 0)
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Bank Name</th>
                            <th>Card Number</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bankAccounts as $account)
                            <tr>
                                <td><strong>#{{ $account->id }}</strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            {{ strtoupper(substr($account->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $account->user->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $account->user->email ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="bi bi-bank me-2" style="color: #667eea;"></i>
                                    <strong>{{ $account->bank_name }}</strong>
                                </td>
                                <td>
                                    @php
                                        $digits = preg_replace('/\D/', '', $account->account_number);
                                        $masked = strlen($digits) >= 4 
                                            ? substr($digits, 0, 4) . ' **** **** ' . substr($digits, -4)
                                            : $account->account_number;
                                    @endphp
                                    <code>{{ $masked }}</code>
                                    <br>
                                    <small class="text-muted">Full: {{ $account->account_number }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $account->currency_code }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'verified' => 'success',
                                            'pending' => 'warning',
                                            'rejected' => 'danger',
                                        ];
                                        $statusColor = $statusColors[$account->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }} badge-modern">
                                        {{ ucfirst($account->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $account->created_at->format('M d, Y H:i') }}
                                    </small>
                                </td>
                                <td>
                                    @if($account->status === 'pending')
                                        <form action="{{ route('admin.bank-accounts.verify', $account->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="verified">
                                            <button type="submit" class="btn btn-sm btn-success btn-modern" 
                                                    onclick="return confirm('Are you sure you want to verify this bank account?')">
                                                <i class="bi bi-check-circle me-1"></i>Verify
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.bank-accounts.verify', $account->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-danger btn-modern"
                                                    onclick="return confirm('Are you sure you want to reject this bank account?')">
                                                <i class="bi bi-x-circle me-1"></i>Reject
                                            </button>
                                        </form>
                                    @elseif($account->status === 'rejected')
                                        <form action="{{ route('admin.bank-accounts.verify', $account->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="verified">
                                            <button type="submit" class="btn btn-sm btn-success btn-modern">
                                                <i class="bi bi-check-circle me-1"></i>Approve
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">Verified</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($bankAccounts->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $bankAccounts->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e0;"></i>
                <h4 class="mt-3 mb-2">No Bank Accounts</h4>
                <p class="text-muted">
                    @if(request('status'))
                        No {{ request('status') }} bank accounts found.
                    @else
                        No bank accounts found.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
@endsection

