@extends('layouts.app')

@section('content')
<h2>KYC Verification</h2>

<div class="row">
    <div class="col-md-6">
        <div id="kyc-status" class="mb-3">
            Loading...
        </div>

        <form id="kyc-form" class="card card-body d-none">
            @csrf
            <h5 class="mb-3">Submit KYC</h5>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" id="kyc_full_name" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">ID Number</label>
                <input type="text" id="kyc_id_number" class="form-control">
            </div>
            {{-- Add whatever fields your KYC controller expects --}}
            <button type="button" class="btn btn-primary" id="btn-submit-kyc">
                Submit KYC
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadKyc() {
        const box = document.getElementById('kyc-status');
        box.textContent = 'Loading...';

        const res = await fetch("/kyc", {
            headers: { 'Accept': 'application/json' }
        });

        const data = await res.json();
        if (!data.success) {
            box.textContent = data.message || 'Unable to load KYC.';
            return;
        }

        const kyc = data.data;
        if (!kyc) {
            box.innerHTML = '<div class="alert alert-warning">No KYC submitted yet.</div>';
            document.getElementById('kyc-form').classList.remove('d-none');
            return;
        }

        box.innerHTML = `
            <div class="alert alert-info">
                Status: <strong>${kyc.status}</strong><br>
                Submitted at: ${kyc.created_at ?? ''}
            </div>
        `;

        if (kyc.status === 'pending' || kyc.status === 'rejected') {
            document.getElementById('kyc-form').classList.remove('d-none');
        }
    }

    async function submitKyc() {
        const payload = {
            full_name: document.getElementById('kyc_full_name').value,
            id_number: document.getElementById('kyc_id_number').value
        };

        const res = await fetch("/kyc", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!data.success) {
            alert(data.message || 'Failed to submit KYC');
            return;
        }

        alert('KYC submitted.');
        loadKyc();
    }

    document.getElementById('btn-submit-kyc').addEventListener('click', submitKyc);
    loadKyc();
</script>
@endsection
