@extends('layouts.app')

@section('title', 'Transaction History')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-list-ul me-2"></i>Transaction History</h1>
                <p>View all your processed cash-in and cash-out transactions</p>
            </div>
            <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-modern btn-modern">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

@if($transactions->count() > 0)
    <div class="card-modern">
        <div class="card-header">
            <i class="bi bi-clock-history me-2"></i>All Transactions
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Transfer Reference</th>
                            <th>Amount</th>
                            <th>Commission</th>
                            <th>Processed At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td><strong>#{{ $transaction->id }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $transaction->type === 'cash_in' ? 'primary' : 'success' }} badge-modern">
                                        {{ $transaction->type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->transfer)
                                        <code>{{ $transaction->transfer->reference }}</code>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->transfer)
                                        <strong>{{ number_format($transaction->transfer->amount, 2) }} {{ $transaction->transfer->currency_from }}</strong>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-success">
                                    <strong>${{ number_format($transaction->commission, 2) }}</strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $transaction->processed_at ? \Carbon\Carbon::parse($transaction->processed_at)->format('M d, Y H:i') : 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    @if($transaction->transfer)
                                        <span class="badge bg-{{ $transaction->transfer->status === 'completed' ? 'success' : 'warning' }} badge-modern">
                                            {{ ucfirst($transaction->transfer->status) }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary badge-modern">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->transfer)
                                        <a href="{{ route('transfers.show', $transaction->transfer) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('portal.transactions.show', $transaction) }}" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-info-circle"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card-modern">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e0;"></i>
            <h4 class="mt-3 mb-2">No Transactions Yet</h4>
            <p class="text-muted">You haven't processed any cash-in or cash-out transactions yet.</p>
            <a href="{{ route('portal.dashboard') }}" class="btn btn-primary-modern btn-modern mt-3">
                <i class="bi bi-arrow-left me-2"></i>Go to Dashboard
            </a>
        </div>
    </div>
@endif
@endsection

