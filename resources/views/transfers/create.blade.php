@extends('layouts.app')

@section('content')
<h2 class="mb-3">New Transfer</h2>

<div class="row">
    <div class="col-md-6">
        <form id="transfer-form">

            @csrf

            <div class="mb-3">
                <label for="beneficiary_id" class="form-label">Beneficiary</label>
                <select id="beneficiary_id" class="form-select" required>
                    <option value="">Select beneficiary...</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Amount to send</label>
                <input type="number" step="0.01" class="form-control" id="amount" value="1000" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Currency From</label>
                    <input type="text" class="form-control" id="currency_from" value="USD" maxlength="3">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Currency To</label>
                    <input type="text" class="form-control" id="currency_to" value="LBP" maxlength="3">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Promo Code (optional)</label>
                <input type="text" class="form-control" id="promo_code" placeholder="WELCOME10">
            </div>

            <div class="mb-3">
                <label class="form-label">Speed</label>
                <select id="speed" class="form-select">
                    <option value="standard">Standard</option>
                    <option value="express">Express</option>
                </select>
            </div>

            <button type="button" class="btn btn-outline-primary" id="btn-preview">
                Preview (Fees & Promo)
            </button>

            <button type="button" class="btn btn-primary ms-2 d-none" id="btn-confirm">
                Confirm & Create Transfer
            </button>
        </form>
    </div>

    <div class="col-md-6">
        <h4>Summary</h4>
        <div id="summary-card" class="card d-none">
            <div class="card-body">
                <p><strong>Beneficiary:</strong> <span id="summary-beneficiary"></span></p>
                <p><strong>Send:</strong> <span id="summary-send"></span></p>
                <p><strong>Receive:</strong> <span id="summary-receive"></span></p>
                <p><strong>Exchange rate:</strong> <span id="summary-rate"></span></p>
                <p><strong>Fee:</strong> <span id="summary-fee"></span></p>
                <p><strong>Discount:</strong> <span id="summary-discount"></span></p>
                <p><strong>Total to pay:</strong> <span id="summary-total"></span></p>
                <p><strong>Estimated delivery:</strong> <span id="summary-delivery"></span></p>
                <p><strong>Promo:</strong> <span id="summary-promo"></span></p>
            </div>
        </div>

        <div id="summary-error" class="alert alert-danger d-none mt-3"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let lastPreviewPayload = null;

    async function loadBeneficiaries() {
        const res = await fetch("{{ route('beneficiaries.index') }}");
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const select = document.getElementById('beneficiary_id');
        data.data.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = `${b.full_name} (${b.country?.name ?? ''})`;
            select.appendChild(opt);
        });
    }

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

    loadBeneficiaries();
</script>
@endsection
