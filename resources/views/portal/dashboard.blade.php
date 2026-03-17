@extends('layouts.app')

@section('title', 'Agent Dashboard')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-shop me-2"></i>Agent Dashboard</h1>
        <p>Welcome back, {{ $agent->store_name }}! Manage your transfer operations.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-wallet2" style="font-size: 2rem; color: #667eea;"></i>
            </div>
            <div class="stat-value">${{ number_format($agent->balance ?? 100000, 2) }}</div>
            <div class="stat-label">Agent Balance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-cash-coin" style="font-size: 2rem; color: #48bb78;"></i>
            </div>
            <div class="stat-value">${{ number_format($todayCommission, 2) }}</div>
            <div class="stat-label">Today's Commission</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-arrow-down-circle" style="font-size: 2rem; color: #ed8936;"></i>
            </div>
            <div class="stat-value">{{ $pendingCashIn->count() }}</div>
            <div class="stat-label">Pending Cash-In</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-arrow-up-circle" style="font-size: 2rem; color: #f56565;"></i>
            </div>
            <div class="stat-value">{{ $pendingCashOut->count() }}</div>
            <div class="stat-label">Pending Cash-Out</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-arrow-down-circle me-2"></i>Pending Cash-In Requests
                <a href="{{ route('portal.transfers.pending', ['type' => 'cash_in']) }}" class="btn btn-sm btn-outline-light float-end">
                    View All
                </a>
            </div>
            <div class="card-body">
                @if($pendingCashIn->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Sender</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingCashIn as $transfer)
                                    <tr>
                                        <td><code>{{ $transfer->reference }}</code></td>
                                        <td><strong>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</strong></td>
                                        <td>{{ $transfer->sender->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-warning badge-modern">{{ ucfirst($transfer->status) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('portal.transfers.process', ['reference' => $transfer->reference, 'type' => 'cash_in']) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-check-circle me-1"></i>Process
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
                        <p class="text-muted mt-2">No pending cash-in requests</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-arrow-up-circle me-2"></i>Pending Cash-Out (Payouts)
                <a href="{{ route('portal.transfers.pending', ['type' => 'cash_out']) }}" class="btn btn-sm btn-outline-light float-end">
                    View All
                </a>
            </div>
            <div class="card-body">
                @if($pendingCashOut->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Beneficiary</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingCashOut as $transfer)
                                    <tr>
                                        <td><code>{{ $transfer->reference }}</code></td>
                                        <td><strong>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</strong></td>
                                        <td>{{ $transfer->beneficiary->full_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info badge-modern">{{ ucfirst($transfer->status) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('portal.transfers.process', ['reference' => $transfer->reference, 'type' => 'cash_out']) }}" 
                                               class="btn btn-sm btn-success">
                                                <i class="bi bi-cash me-1"></i>Pay Out
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
                        <p class="text-muted mt-2">No pending cash-out requests</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Recent Transactions
                <a href="{{ route('portal.transactions.index', $agent) }}" class="btn btn-sm btn-outline-light float-end">
                    View All
                </a>
            </div>
            <div class="card-body">
                @if($recentTransactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Transfer Ref</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $transaction->type === 'cash_in' ? 'primary' : 'success' }} badge-modern">
                                                {{ $transaction->type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction->transfer)
                                                <code>{{ $transaction->transfer->reference }}</code>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->transfer)
                                                {{ number_format($transaction->transfer->amount, 2) }} {{ $transaction->transfer->currency_from }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="text-success">
                                            <strong>${{ number_format($transaction->commission, 2) }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $transaction->processed_at ? \Carbon\Carbon::parse($transaction->processed_at)->format('M d, H:i') : 'N/A' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
                        <p class="text-muted mt-2">No transactions yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4 layout-stack">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-bell me-2"></i>Notifications
                @if($unreadCount > 0)
                    <span class="badge bg-danger badge-modern ms-2">{{ $unreadCount }}</span>
                @endif
            </div>
            <div class="card-body">
                @if($notifications->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($notifications as $notification)
                            @php
                                $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                                $isRead = $notification->read_at !== null;
                            @endphp
                            <div class="list-group-item {{ !$isRead ? 'border-start border-primary border-4' : '' }}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 {{ $isRead ? 'text-muted' : '' }}">
                                        {{ $data['message'] ?? 'Transfer update' }}
                                    </h6>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                @if(isset($data['transfer_id']))
                                    <small class="text-muted">Transfer #{{ $data['transfer_id'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('app.notifications.index') }}" class="btn btn-outline-modern btn-modern w-100">
                            View All Notifications
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-bell-slash" style="font-size: 2rem; color: #cbd5e0;"></i>
                        <p class="text-muted mt-2">No notifications</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('portal.transfers.pending', ['type' => 'cash_in']) }}" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-arrow-down-circle me-2"></i>Process Cash-In
                    </a>
                    <a href="{{ route('portal.transfers.pending', ['type' => 'cash_out']) }}" class="btn btn-success btn-modern">
                        <i class="bi bi-arrow-up-circle me-2"></i>Process Cash-Out
                    </a>
                    <a href="{{ route('portal.transactions.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-list-ul me-2"></i>Transaction History
                    </a>
                    <a href="{{ route('portal.commissions') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-graph-up me-2"></i>View Commissions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
