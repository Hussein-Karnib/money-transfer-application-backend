@extends('layouts.app')

@section('title', 'New Transfer')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-send me-2"></i>New Transfer</h1>
        <p>Send money to your beneficiaries quickly and securely</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>Transfer Details
            </div>
            <div class="card-body">
                <form id="transfer-form">
                    @csrf

                    <div class="mb-3">
                        <label for="beneficiary_id" class="form-label-modern">Beneficiary</label>
                        <select id="beneficiary_id" class="form-select form-control-modern" required>
                            <option value="">Select beneficiary...</option>
                            @foreach($beneficiaries as $beneficiary)
                                <option value="{{ $beneficiary->id }}">
                                    {{ $beneficiary->full_name }} ({{ $beneficiary->country->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Amount to send</label>
                        <input type="number" step="0.01" class="form-control form-control-modern" id="amount" value="1000" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-modern">Currency From</label>
                            <input type="text" class="form-control form-control-modern" id="currency_from" value="USD" maxlength="3">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-modern">Currency To</label>
                            <input type="text" class="form-control form-control-modern" id="currency_to" value="LBP" maxlength="3">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Promo Code (optional)</label>
                        <input type="text" class="form-control form-control-modern" id="promo_code" placeholder="WELCOME10">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Speed</label>
                        <select id="speed" class="form-select form-control-modern">
                            <option value="standard">Standard</option>
                            <option value="express">Express</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-modern btn-modern flex-fill" id="btn-preview">
                            <i class="bi bi-eye me-2"></i>Preview
                        </button>
                        <button type="button" class="btn btn-primary-modern btn-modern flex-fill d-none" id="btn-confirm">
                            <i class="bi bi-check-circle me-2"></i>Confirm & Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-receipt me-2"></i>Transfer Summary
            </div>
            <div class="card-body">
                <div id="summary-card" class="d-none">
                    <div class="mb-3">
                        <small class="text-muted">Beneficiary</small>
                        <p class="mb-0 fw-bold" id="summary-beneficiary">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Send Amount</small>
                        <p class="mb-0 fw-bold" id="summary-send">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Receive Amount</small>
                        <p class="mb-0 fw-bold text-success" id="summary-receive">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Exchange Rate</small>
                        <p class="mb-0" id="summary-rate">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Fee</small>
                        <p class="mb-0" id="summary-fee">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Discount</small>
                        <p class="mb-0 text-success" id="summary-discount">-</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <small class="text-muted">Total to Pay</small>
                        <p class="mb-0 h5 text-primary" id="summary-total">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Estimated Delivery</small>
                        <p class="mb-0" id="summary-delivery">-</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Promo</small>
                        <p class="mb-0" id="summary-promo">-</p>
                    </div>
                </div>
                <div id="summary-placeholder" class="text-center py-5">
                    <i class="bi bi-info-circle" style="font-size: 3rem; color: #cbd5e0;"></i>
                    <p class="text-muted mt-3">Click "Preview" to see transfer summary</p>
                </div>
                <div id="summary-error" class="alert alert-danger d-none"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let lastPreviewPayload = null;

    async function previewTransfer() {
        const beneficiary_id = document.getElementById('beneficiary_id').value;
        const amount         = document.getElementById('amount').value;
        const currency_from  = document.getElementById('currency_from').value.toUpperCase();
        const currency_to    = document.getElementById('currency_to').value.toUpperCase();
        const promo_code     = document.getElementById('promo_code').value.trim();
        const speed          = document.getElementById('speed').value;

        const errorBox   = document.getElementById('summary-error');
        const summaryBox = document.getElementById('summary-card');
        errorBox.classList.add('d-none');
        summaryBox.classList.add('d-none');

        const params = new URLSearchParams({
            beneficiary_id,
            amount,
            currency_from,
            currency_to,
            speed,
        });

        if (promo_code !== '') {
            params.append('promo_code', promo_code);
        }

        const res = await fetch(`/transfers/summary?` + params.toString(), {
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await res.json();

        if (!data.success) {
            errorBox.textContent = data.message || 'Error while calculating summary.';
            errorBox.classList.remove('d-none');
            return;
        }

        const d = data.data;
        lastPreviewPayload = {
            beneficiary_id,
            amount,
            currency_from,
            currency_to,
            speed,
            promo_code
        };

        document.getElementById('summary-beneficiary').textContent = d.beneficiary.full_name;
        document.getElementById('summary-send').textContent        = `${d.amount.send} ${d.amount.currency_from}`;
        document.getElementById('summary-receive').textContent     = `${d.receiver_amount.amount} ${d.receiver_amount.currency}`;
        document.getElementById('summary-rate').textContent        = d.exchange_rate;
        document.getElementById('summary-fee').textContent         = d.fee;
        document.getElementById('summary-discount').textContent    = d.discount_amount;
        document.getElementById('summary-total').textContent       = d.total_amount;
        document.getElementById('summary-delivery').textContent    = d.estimated_delivery_time;

        if (d.promotion) {
            document.getElementById('summary-promo').textContent =
                `${d.promotion.code} (${d.promotion.description})`;
        } else {
            document.getElementById('summary-promo').textContent = 'None';
        }

        document.getElementById('summary-placeholder').classList.add('d-none');
        summaryBox.classList.remove('d-none');
        document.getElementById('btn-confirm').classList.remove('d-none');
    }

    async function confirmTransfer() {
        if (!lastPreviewPayload) {
            alert('Preview first.');
            return;
        }

        const res = await fetch("/transfers", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(lastPreviewPayload)
        });

        const data = await res.json();
        if (!data.success) {
            alert(data.message || 'Error creating transfer');
            return;
        }

        alert('Transfer created successfully (ref: ' + data.data.reference + ')');
        window.location.href = "{{ route('app.transfers.index') }}";
    }

    document.getElementById('btn-preview').addEventListener('click', previewTransfer);
    document.getElementById('btn-confirm').addEventListener('click', confirmTransfer);
</script>
@endsection
