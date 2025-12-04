@extends('layouts.app')

@section('title', 'Pending Transfers')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>
                    <i class="bi bi-{{ $type === 'cash_in' ? 'arrow-down-circle' : 'arrow-up-circle' }} me-2"></i>
                    Pending {{ $type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }} Transfers
                </h1>
                <p>View and process {{ $type === 'cash_in' ? 'incoming' : 'outgoing' }} transfer requests</p>
            </div>
            <div class="btn-group">
                <a href="{{ route('portal.transfers.pending', ['type' => 'cash_in']) }}" 
                   class="btn btn-{{ $type === 'cash_in' ? 'primary' : 'outline-primary' }}-modern btn-modern">
                    <i class="bi bi-arrow-down-circle me-2"></i>Cash-In
                </a>
                <a href="{{ route('portal.transfers.pending', ['type' => 'cash_out']) }}" 
                   class="btn btn-{{ $type === 'cash_out' ? 'success' : 'outline-success' }}-modern btn-modern">
                    <i class="bi bi-arrow-up-circle me-2"></i>Cash-Out
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
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>{{ $type === 'cash_in' ? 'Sender' : 'Beneficiary' }}</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Initiated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr>
                                <td><code>{{ $transfer->reference }}</code></td>
                                <td>
                                    <strong>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        → {{ number_format($transfer->amount * ($transfer->exchange_rate ?? 1), 2) }} {{ $transfer->currency_to }}
                                    </small>
                                </td>
                                <td>
                                    @if($type === 'cash_in')
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                {{ strtoupper(substr($transfer->sender->name ?? 'N', 0, 1)) }}
                                            </div>
                                            {{ $transfer->sender->name ?? 'N/A' }}
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                {{ strtoupper(substr($transfer->beneficiary->full_name ?? 'N', 0, 1)) }}
                                            </div>
                                            {{ $transfer->beneficiary->full_name ?? 'N/A' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-globe me-1"></i>
                                        {{ $transfer->beneficiary->country->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $transfer->status === 'available_for_pickup' ? 'info' : 'warning' }} badge-modern">
                                        {{ ucfirst($transfer->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('portal.transfers.process', ['reference' => $transfer->reference, 'type' => $type]) }}" 
                                           class="btn btn-sm btn-{{ $type === 'cash_in' ? 'primary' : 'success' }}-modern">
                                            <i class="bi bi-{{ $type === 'cash_in' ? 'check-circle' : 'cash' }} me-1"></i>
                                            Process
                                        </a>
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
            <h4 class="mt-3 mb-2">No Pending {{ $type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }} Transfers</h4>
            <p class="text-muted">There are no transfers waiting for {{ $type === 'cash_in' ? 'cash-in' : 'cash-out' }} processing at the moment.</p>
        </div>
    </div>
@endif
@endsection

