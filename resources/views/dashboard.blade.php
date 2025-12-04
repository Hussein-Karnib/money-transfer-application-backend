@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p>Welcome back, {{ auth()->user()->name }}! Here's your account overview.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-arrow-left-right" style="font-size: 2rem; color: #667eea;"></i>
            </div>
            <div class="stat-value">{{ $totalTransfers }}</div>
            <div class="stat-label">Total Transfers</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card {{ $lastTransferStatus === 'completed' ? 'success' : ($lastTransferStatus === 'pending' ? 'warning' : 'danger') }}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-info-circle" style="font-size: 2rem; color: {{ $lastTransferStatus === 'completed' ? '#48bb78' : ($lastTransferStatus === 'pending' ? '#ed8936' : '#f56565') }};"></i>
            </div>
            <div class="stat-value" style="font-size: 1.5rem;">{{ ucfirst($lastTransferStatus) }}</div>
            <div class="stat-label">Last Transfer Status</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-bell" style="font-size: 2rem; color: #ed8936;"></i>
            </div>
            <div class="stat-value">{{ $unreadCount }}</div>
            <div class="stat-label">Unread Notifications</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Recent Transfers
            </div>
            <div class="card-body">
                @if($transfers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Beneficiary</th>
                                    <th>Amount</th>
                                    <th>From → To</th>
                                    <th>Status</th>
                                    <th>Initiated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfers as $transfer)
                                    <tr>
                                        <td><strong>#{{ $transfer->id }}</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($transfer->beneficiary->full_name ?? 'N', 0, 1)) }}
                                                </div>
                                                {{ $transfer->beneficiary->full_name ?? '-' }}
                                            </div>
                                        </td>
                                        <td><strong>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</strong></td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $transfer->currency_from }}</span>
                                            <i class="bi bi-arrow-right mx-2"></i>
                                            <span class="badge bg-light text-dark">{{ $transfer->currency_to }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    'queued' => 'info',
                                                    'cancelled' => 'danger',
                                                    'failed' => 'danger'
                                                ];
                                                $color = $statusColors[$transfer->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }} badge-modern">
                                                {{ ucfirst($transfer->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : '-' }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e0;"></i>
                        <p class="text-muted mt-3">No transfers yet. Create your first transfer to get started!</p>
                        <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern mt-3">
                            <i class="bi bi-plus-circle me-2"></i>Create Transfer
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-plus-circle me-2"></i>New Transfer
                    </a>
                    <a href="{{ route('app.beneficiaries.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-people me-2"></i>Manage Beneficiaries
                    </a>
                    <a href="{{ route('app.bank-accounts.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-bank me-2"></i>Manage Bank Accounts
                    </a>
                    <a href="{{ route('app.notifications.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-bell me-2"></i>View Notifications
                        @if($unreadCount > 0)
                            <span class="badge bg-danger badge-modern ms-2">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        <div class="card-modern mt-4">
            <div class="card-header">
                <i class="bi bi-graph-up me-2"></i>Account Summary
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Account Status</span>
                        <span class="badge bg-success badge-modern">Active</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Member Since</span>
                        <strong>{{ auth()->user()->created_at->format('M Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
