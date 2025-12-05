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
                            <span class="badge bg-{{ $transfer->status === 'available_for_pickup' ? 'info' : 'warning' }} badge-modern">
                                {{ ucfirst($transfer->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Estimated Commission</small>
                        <p class="mb-0 h6 text-success">
                            ${{ number_format($transfer->amount * ($agent->commission_rate ?? 0.01), 2) }}
                        </p>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Enter a transfer reference to view details
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
                <form method="POST" action="{{ route('portal.transfers.process.store') }}">
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

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong>
                        <ul class="mb-0 mt-2">
                            @if($type === 'cash_in')
                                <li>Cash-In: Transfer must be in 'queued' or 'paid' status</li>
                                <li>You will receive commission after processing</li>
                            @else
                                <li>Cash-Out: Transfer must be in 'available_for_pickup' status</li>
                                <li>Verify beneficiary identity before payout</li>
                            @endif
                        </ul>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-{{ $type === 'cash_in' ? 'primary' : 'success' }}-modern btn-modern">
                            <i class="bi bi-{{ $type === 'cash_in' ? 'check-circle' : 'cash' }} me-2"></i>
                            Process {{ $type === 'cash_in' ? 'Cash-In' : 'Cash-Out' }}
                        </button>
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
    document.querySelector('input[name="transfer_reference"]').addEventListener('blur', function() {
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

