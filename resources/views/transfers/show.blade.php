@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Transfer Details #{{ $transfer->id }}</h2>
            <a href="{{ route('app.transfers.index') }}" class="btn btn-outline-secondary">Back to Transfers</a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Transfer Information</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Transfer ID:</dt>
                    <dd class="col-sm-9">#{{ $transfer->id }}</dd>

                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($transfer->status) }}
                        </span>
                    </dd>

                    <dt class="col-sm-3">Amount:</dt>
                    <dd class="col-sm-9">
                        <strong>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</strong>
                        @if($transfer->currency_from !== $transfer->currency_to)
                            -> {{ number_format($transfer->amount_received, 2) }} {{ $transfer->currency_to }}
                        @endif
                    </dd>

                    <dt class="col-sm-3">Fees (incl. offers):</dt>
                    <dd class="col-sm-9">{{ number_format($transfer->fee, 2) }} {{ $transfer->currency_from }}</dd>

                    <dt class="col-sm-3">Total to Pay:</dt>
                    <dd class="col-sm-9">{{ number_format($transfer->total_amount, 2) }} {{ $transfer->currency_from }}</dd>

                    <dt class="col-sm-3">Selected Offers:</dt>
                    <dd class="col-sm-9">
                        @if($transfer->offers && count($transfer->offers) > 0)
                            <div class="d-flex flex-column gap-2">
                                @foreach($transfer->offers as $offer)
                                    <div class="d-flex justify-content-between align-items-center border rounded p-2">
                                        <span class="badge bg-primary badge-modern">{{ $offer['name'] ?? $offer }}</span>
                                        <strong class="text-success">
                                            {{ number_format($offer['price'] ?? 0, 2) }} {{ $transfer->currency_from }}
                                        </strong>
                                    </div>
                                @endforeach
                                <div class="mt-2 pt-2 border-top">
                                    <strong>Total Offers Cost: 
                                        <span class="text-primary">{{ number_format($transfer->offers_total ?? 0, 2) }} {{ $transfer->currency_from }}</span>
                                    </strong>
                                </div>
                            </div>
                        @else
                            <span class="text-muted">None</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Beneficiary:</dt>
                    <dd class="col-sm-9">
                        {{ $transfer->beneficiary->full_name ?? 'N/A' }}
                        @if($transfer->beneficiary->country)
                            <br><small class="text-muted">{{ $transfer->beneficiary->country->name ?? '' }}</small>
                        @endif
                    </dd>

                    @if($transfer->transferMethod)
                    <dt class="col-sm-3">Payout Method:</dt>
                    <dd class="col-sm-9">{{ $transfer->transferMethod->name }}</dd>
                    @endif

                    <dt class="col-sm-3">Initiated At:</dt>
                    <dd class="col-sm-9">{{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : 'N/A' }}</dd>

                    @if($transfer->completed_at)
                    <dt class="col-sm-3">Completed At:</dt>
                    <dd class="col-sm-9">{{ \Carbon\Carbon::parse($transfer->completed_at)->format('M d, Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($transfer->events && $transfer->events->count() > 0)
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Transfer Events</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Note</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->events as $event)
                            <tr>
                                <td>{{ ucfirst($event->status ?? 'N/A') }}</td>
                                <td>{{ $event->note ?? 'N/A' }}</td>
                                <td>{{ $event->created_at ? \Carbon\Carbon::parse($event->created_at)->format('M d, Y H:i') : 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
