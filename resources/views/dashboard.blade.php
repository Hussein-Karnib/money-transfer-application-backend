@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <h2 class="mb-3">Dashboard</h2>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Last Transfer Status</h6>
                        <p class="h5">{{ $lastTransferStatus }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Transfers</h6>
                        <p class="h5">{{ $totalTransfers }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Unread Notifications</h6>
                        <p class="h5">{{ $unreadCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <h4>Recent Transfers</h4>
        <table class="table table-striped">
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
            @forelse($transfers as $transfer)
                <tr>
                    <td>{{ $transfer->id }}</td>
                    <td>{{ $transfer->beneficiary->full_name ?? '-' }}</td>
                    <td>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</td>
                    <td>{{ $transfer->currency_from }} → {{ $transfer->currency_to }}</td>
                    <td>
                        <span class="badge bg-{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($transfer->status) }}
                        </span>
                    </td>
                    <td>{{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No transfers yet</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="col-md-4">
        <h4>Quick Actions</h4>
        <div class="d-grid gap-2">
            <a href="{{ route('app.transfers.create') }}" class="btn btn-primary">
                New Transfer
            </a>
            <a href="{{ route('app.beneficiaries.index') }}" class="btn btn-outline-secondary">
                Manage Beneficiaries
            </a>
            <a href="{{ route('app.bank-accounts.index') }}" class="btn btn-outline-secondary">
                Manage Bank Accounts
            </a>
            <a href="{{ route('app.notifications.index') }}" class="btn btn-outline-secondary">
                View Notifications
            </a>
        </div>
    </div>
</div>
@endsection
