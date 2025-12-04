@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>My Transfers</h2>
    <a href="{{ route('app.transfers.create') }}" class="btn btn-primary">New Transfer</a>
</div>

@if($transfers->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover">
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
                    <td>{{ $transfer->id }}</td>
                    <td>{{ $transfer->beneficiary->full_name ?? '-' }}</td>
                    <td>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</td>
                    <td>{{ $transfer->currency_from }} → {{ $transfer->currency_to }}</td>
                    <td>
                        <span class="badge bg-{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($transfer->status) }}
                        </span>
                    </td>
                    <td>{{ $transfer->reference ?? '-' }}</td>
                    <td>{{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : '-' }}</td>
                    <td>
                        <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-outline-secondary me-1">View</a>
                        @if($transfer->status === 'queued')
                            <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" class="d-inline" onsubmit="return confirm('Cancel this transfer?');">
                                @csrf
                                @method('POST')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $transfers->links() }}
    </div>
@else
    <div class="alert alert-info">
        <p class="mb-0">No transfers found. <a href="{{ route('app.transfers.create') }}">Create your first transfer</a></p>
    </div>
@endif
@endsection
