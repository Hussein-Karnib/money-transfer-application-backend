@extends('layouts.app')

@section('title', 'Process Transfer')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1>
            <i class="bi bi-{{ $type === 'cash_in' ? 'arrow-down-circle' : 'arrow-up-circle' }} me-2"></i>
            Process {{ $type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}
        </h1>
        <p>{{ $type === 'cash_in' ? 'Accept payment from sender' : 'Pay out to beneficiary' }}</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-receipt me-2"></i>Transfer Details
            </div>
            <div class="card-body">
                @if($transfer)
                    @if($statusError)
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Status Mismatch:</strong> {{ $statusError }}
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <small class="text-muted">Reference</small>
                        <p class="mb-0"><code>{{ $transfer->reference }}</code></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Amount</small>
                        <p class="mb-0 h5">
                            {{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}
                        </p>
                    </div>
                    @if($type === 'cash_in')
                        <div class="mb-3">
                            <small class="text-muted">Sender</small>
                            <p class="mb-0">{{ $transfer->sender->name ?? 'N/A' }}</p>
                        </div>
                    @else
                        <div class="mb-3">
                            <small class="text-muted">Beneficiary</small>
                            <p class="mb-0">{{ $transfer->beneficiary->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Country</small>
                            <p class="mb-0">{{ $transfer->beneficiary->country->name ?? 'N/A' }}</p>
                        </div>
                    @endif
                    <div class="mb-3">
                        <small class="text-muted">Status</small>
                        <p class="mb-0">
                            @php
                                $statusColors = [
                                    'available_for_pickup' => 'info',
                                    'queued' => 'warning',
                                    'paid' => 'warning',
                                    'completed' => 'success',
                                    'in_progress' => 'primary',
                                ];
                                $statusColor = $statusColors[$transfer->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusColor }} badge-modern">
                                {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Estimated Commission</small>
                        <p class="mb-0 h6 text-success">
                            ${{ number_format($transfer->amount * ($agent->commission_rate ?? 0.01), 2) }}
                        </p>
                    </div>
                    @if($type === 'cash_out')
                        @php
                            $agentBalance = $agent->balance ?? 100000;
                            $hasSufficientBalance = $agentBalance >= $transfer->amount;
                        @endphp
                        <div class="mb-3">
                            <small class="text-muted">Agent Balance</small>
                            <p class="mb-0 h6 {{ $hasSufficientBalance ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($agentBalance, 2) }}
                            </p>
                            @if(!$hasSufficientBalance)
                                <div class="alert alert-danger mt-2 mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Insufficient Balance!</strong> You need ${{ number_format($transfer->amount, 2) }} but only have ${{ number_format($agentBalance, 2) }}. You cannot process this cash-out.
                                </div>
                            @else
                                <small class="text-muted">After payout: ${{ number_format($agentBalance - $transfer->amount, 2) }}</small>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Enter a transfer reference to view details
                    </div>
                @endif
                
                @if(isset($availableTransfers) && $availableTransfers->count() > 0)
                    <hr class="my-3">
                    <div class="mb-2">
                        <small class="text-muted fw-semibold">
                            <i class="bi bi-list-check me-1"></i>
                            Available Transfers for {{ $type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}:
                        </small>
                    </div>
                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                        @foreach($availableTransfers as $availableTransfer)
                            <a href="?reference={{ $availableTransfer->reference }}&type={{ $type }}" 
                               class="list-group-item list-group-item-action {{ $transfer && $transfer->id === $availableTransfer->id ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><code>{{ $availableTransfer->reference }}</code></strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ number_format($availableTransfer->amount, 2) }} {{ $availableTransfer->currency_from }}
                                            @if($type === 'cash_out' && $availableTransfer->beneficiary)
                                                - {{ $availableTransfer->beneficiary->full_name }}
                                            @elseif($type === 'cash_in' && $availableTransfer->sender)
                                                - {{ $availableTransfer->sender->name }}
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $type === 'cash_in' ? 'warning' : 'info' }} badge-modern">
                                        {{ ucfirst(str_replace('_', ' ', $availableTransfer->status)) }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>Process Transfer
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('portal.transactions.store') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label-modern">Transfer Reference</label>
                        <input type="text" 
                               class="form-control form-control-modern @error('transfer_reference') is-invalid @enderror" 
                               name="transfer_reference" 
                               value="{{ old('transfer_reference', $transfer->reference ?? '') }}" 
                               required
                               placeholder="Enter transfer reference">
                        @error('transfer_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Enter the transfer reference code to process</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Transaction Type</label>
                        <select class="form-select form-control-modern" name="type" required>
                            <option value="cash_in" {{ old('type', $type) === 'cash_in' ? 'selected' : '' }}>
                                Cash-In (Accept Payment)
                            </option>
                            <option value="cash_out" {{ old('type', $type) === 'cash_out' ? 'selected' : '' }}>
                                Cash-Out (Pay Out)
                            </option>
                        </select>
                    </div>

                    <div class="alert alert-{{ $statusError ? 'danger' : 'warning' }}">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong>
                        <ul class="mb-0 mt-2">
                            @if($type === 'cash_in')
                                <li><strong>Cash-In:</strong> Transfer must be in <code>'queued'</code> or <code>'paid'</code> status</li>
                                <li>You will receive commission after processing</li>
                                <li class="mt-2"><small>Only transfers with status: <span class="badge bg-warning">queued</span> or <span class="badge bg-warning">paid</span> can be processed</small></li>
                            @else
                                <li><strong>Cash-Out:</strong> Transfer must be in <code>'available_for_pickup'</code> status</li>
                                <li>Verify beneficiary identity before payout</li>
                                <li class="mt-2"><small>Only transfers with status: <span class="badge bg-info">available_for_pickup</span> can be processed</small></li>
                            @endif
                        </ul>
                    </div>
                    
                    @if($statusError)
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            <strong>Cannot Process:</strong> This transfer cannot be processed because its status doesn't match the required status for {{ $type === 'cash_in' ? 'cash-in' : 'cash-out' }} operations.
                        </div>
                    @endif

                        <div class="d-grid gap-2">
                            @php
                                $canProcess = true;
                                if ($type === 'cash_out' && $transfer) {
                                    $agentBalance = $agent->balance ?? 100000;
                                    $canProcess = $agentBalance >= $transfer->amount;
                                }
                            @endphp
                            <button type="submit" 
                                    class="btn btn-{{ $type === 'cash_in' ? 'primary' : 'success' }}-modern btn-modern"
                                    {{ !$canProcess ? 'disabled' : '' }}>
                                <i class="bi bi-{{ $type === 'cash_in' ? 'check-circle' : 'cash' }} me-2"></i>
                                Process {{ $type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}
                            </button>
                            @if(!$canProcess)
                                <small class="text-danger text-center">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    Insufficient balance to process this cash-out
                                </small>
                            @endif
                        <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-modern btn-modern">
                            <i class="bi bi-arrow-left me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-fetch transfer details when reference is entered
    document.querySelector('input[name="transfer_reference"]')?.addEventListener('blur', function() {
        const reference = this.value;
        if (reference && reference.length > 0) {
            // Reload page with reference parameter
            const url = new URL(window.location);
            url.searchParams.set('reference', reference);
            window.location = url.toString();
        }
    });
</script>
@endsection
