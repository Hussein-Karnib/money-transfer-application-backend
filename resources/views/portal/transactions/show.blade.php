@extends('layouts.app')

@section('title', 'Transaction Details')

@section('content')
<<<<<<< Updated upstream
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-receipt me-2"></i>Transaction Details</h1>
                <p>View detailed information about this transaction</p>
            </div>
            <a href="{{ route('portal.transactions.index') }}" class="btn btn-outline-modern btn-modern">
                <i class="bi bi-arrow-left me-2"></i>Back to History
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Transaction Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Transaction ID</small>
                        <p class="mb-0"><strong>#{{ $transaction->id }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Type</small>
                        <p class="mb-0">
                            <span class="badge bg-{{ $transaction->type === 'cash_in' ? 'primary' : 'success' }} badge-modern">
                                {{ $transaction->type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Amount</small>
                        <p class="mb-0 h5">{{ number_format($transaction->amount, 2) }} 
                            @if($transaction->transfer)
                                {{ $transaction->transfer->currency_from }}
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Commission Earned</small>
                        <p class="mb-0 h5 text-success">${{ number_format($transaction->commission, 2) }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Processed At</small>
                        <p class="mb-0">
                            {{ $transaction->processed_at ? \Carbon\Carbon::parse($transaction->processed_at)->format('M d, Y H:i:s') : 'N/A' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Created At</small>
                        <p class="mb-0">
                            {{ $transaction->created_at->format('M d, Y H:i:s') }}
                        </p>
                    </div>
                </div>

                @if($transaction->transfer)
                    <hr>
                    <h5 class="mb-3">Transfer Details</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">Transfer Reference</small>
                            <p class="mb-0"><code>{{ $transaction->transfer->reference }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Transfer Status</small>
                            <p class="mb-0">
                                <span class="badge bg-{{ $transaction->transfer->status === 'completed' ? 'success' : 'warning' }} badge-modern">
                                    {{ ucfirst($transaction->transfer->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">Sender</small>
                            <p class="mb-0">{{ $transaction->transfer->sender->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Beneficiary</small>
                            <p class="mb-0">{{ $transaction->transfer->beneficiary->full_name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">From Currency</small>
                            <p class="mb-0">{{ $transaction->transfer->currency_from }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">To Currency</small>
                            <p class="mb-0">{{ $transaction->transfer->currency_to }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('transfers.show', $transaction->transfer) }}" class="btn btn-primary-modern btn-modern">
                            <i class="bi bi-eye me-2"></i>View Full Transfer Details
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-shop me-2"></i>Agent Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Store Name</small>
                    <p class="mb-0"><strong>{{ $agent->store_name }}</strong></p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Address</small>
                    <p class="mb-0">{{ $agent->address }}</p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Commission Rate</small>
                    <p class="mb-0">{{ number_format(($agent->commission_rate ?? 0.01) * 100, 2) }}%</p>
                </div>
            </div>
        </div>

        <div class="card-modern mt-4">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('portal.transactions.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-list-ul me-2"></i>All Transactions
                    </a>
                    <a href="{{ route('portal.commissions') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-graph-up me-2"></i>View Commissions
                    </a>
                    <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

=======
<h1 class="h4 mb-3">Transaction #{{ $transaction->id }}</h1>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Type</dt>
            <dd class="col-sm-9 text-uppercase">{{ str_replace('_', ' ', $transaction->type) }}</dd>

            <dt class="col-sm-3">Amount</dt>
            <dd class="col-sm-9">{{ number_format($transaction->amount, 2) }}</dd>

            <dt class="col-sm-3">Commission</dt>
            <dd class="col-sm-9">{{ number_format($transaction->commission, 2) }}</dd>

            <dt class="col-sm-3">Processed At</dt>
            <dd class="col-sm-9">{{ $transaction->processed_at }}</dd>
        </dl>
    </div>
</div>

@if($transaction->transfer)
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5>Linked Transfer</h5>
            <dl class="row mb-0">
                <dt class="col-sm-3">Reference</dt>
                <dd class="col-sm-9">{{ $transaction->transfer->reference }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9 text-capitalize">{{ $transaction->transfer->status }}</dd>
            </dl>
        </div>
    </div>
@endif

<a href="{{ route('portal.transactions.index', $agent) }}" class="btn btn-outline-secondary">Back</a>
@endsection
>>>>>>> Stashed changes
