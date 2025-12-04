@extends('layouts.app')

@section('title', 'My Transfers')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-arrow-left-right me-2"></i>My Transfers</h1>
                <p>View and manage all your money transfers</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('app.transfers.search') }}" class="btn btn-outline-modern btn-modern">
                    <i class="bi bi-search me-2"></i>Search Services
                </a>
                <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern">
                    <i class="bi bi-plus-circle me-2"></i>New Transfer
                </a>
            </div>
        </div>
    </div>
</div>

@if($transfers->count() > 0)
    <div class="card-modern">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Beneficiary</th>
                            <th>Amount</th>
                            <th>From → To</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th>Initiated</th>
                            <th>Actions</th>
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
                                <td><code>{{ $transfer->reference ?? '-' }}</code></td>
                                <td>
                                    <small class="text-muted">
                                        {{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : '-' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($transfer->status === 'queued')
                                            <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this transfer?');">
                                                @csrf
                                                @method('POST')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card-modern">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e0;"></i>
            <h4 class="mt-3 mb-2">No Transfers Yet</h4>
            <p class="text-muted mb-4">Start sending money by creating your first transfer</p>
            <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern">
                <i class="bi bi-plus-circle me-2"></i>Create Your First Transfer
            </a>
        </div>
    </div>
@endif
@endsection
