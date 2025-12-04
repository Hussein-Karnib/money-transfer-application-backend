@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Commission Report - {{ $agent->store_name }}</h2>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Commission</h6>
                        <p class="h4 text-primary mb-0">${{ number_format($totalCommission, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">This Month</h6>
                        <p class="h4 text-success mb-0">${{ number_format($monthlyCommission, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Today</h6>
                        <p class="h4 text-info mb-0">${{ number_format($todayCommission, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Filtered Total</h6>
                        <p class="h4 text-warning mb-0">${{ number_format($filteredCommission, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('portal.commissions', $agent) }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from" name="from" 
                               value="{{ request('from') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to" name="to" 
                               value="{{ request('to') }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="{{ route('portal.commissions', $agent) }}" class="btn btn-outline-secondary">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Transaction History</h5>
            </div>
            <div class="card-body">
                @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Transfer ID</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Processed At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->id }}</td>
                                        <td>
                                            @if($transaction->transfer)
                                                <a href="{{ route('transfers.show', $transaction->transfer->id) }}">
                                                    #{{ $transaction->transfer->id }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->transfer)
                                                {{ number_format($transaction->transfer->amount, 2) }} 
                                                {{ $transaction->transfer->currency_from }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="text-success">
                                            <strong>${{ number_format($transaction->commission, 2) }}</strong>
                                        </td>
                                        <td>{{ $transaction->processed_at ? \Carbon\Carbon::parse($transaction->processed_at)->format('M d, Y H:i') : 'N/A' }}</td>
                                        <td>
                                            @if($transaction->transfer)
                                                <span class="badge bg-{{ $transaction->transfer->status === 'completed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($transaction->transfer->status) }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($transactions->hasPages())
                        <div class="mt-4">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info">
                        <p class="mb-0">No commission transactions found for the selected period.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Set max date for "to" field to today
    document.addEventListener('DOMContentLoaded', function() {
        const toInput = document.getElementById('to');
        if (toInput) {
            toInput.max = new Date().toISOString().split('T')[0];
        }

        // Set max date for "from" field based on "to" field
        const fromInput = document.getElementById('from');
        const toInputField = document.getElementById('to');
        
        if (toInputField && fromInput) {
            toInputField.addEventListener('change', function() {
                fromInput.max = this.value;
            });
        }
    });
</script>
@endsection

