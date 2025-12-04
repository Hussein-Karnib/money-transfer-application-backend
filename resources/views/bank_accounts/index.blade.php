@extends('layouts.app')

@section('content')
<h2>Bank Accounts</h2>

<div class="row mb-4">
    <div class="col-md-6">
        <form id="bank-account-form" class="card card-body">
            @csrf
            <h5 class="mb-3">Add Bank Account</h5>
            <div class="mb-3">
                <label class="form-label">Bank Name</label>
                <input type="text" id="ba_bank_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Currency Code</label>
                <input type="text" id="ba_currency_code" class="form-control" value="USD" required>
            </div>
            <button type="button" class="btn btn-primary" id="btn-add-bank-account">
                Add
            </button>
        </form>
    </div>
</div>

<table class="table" id="bank-accounts-table">
    <thead>
    <tr>
        <th>#</th>
        <th>Bank Name</th>
        <th>Account Number</th>
        <th>Currency</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    {{-- JS --}}
    </tbody>
</table>
@endsection

@section('scripts')
<script>
    async function loadBankAccounts() {
        const res = await fetch("{{ route('bank-accounts.index') }}");
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const tbody = document.querySelector('#bank-accounts-table tbody');
        tbody.innerHTML = '';

        data.data.forEach(a => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${a.id}</td>
                <td>${a.bank_name}</td>
                <td>${a.account_number}</td>
                <td>${a.currency?.code ?? a.currency_code}</td>
                <td>${a.status}</td>
                <td></td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function addBankAccount() {
        const payload = {
            bank_name: document.getElementById('ba_bank_name').value,
            currency_code: document.getElementById('ba_currency_code').value.toUpperCase(),
        };

        const res = await fetch("{{ route('bank-accounts.store') }}", {
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
            alert(data.message || 'Error adding bank account');
            return;
        }

        document.getElementById('ba_bank_name').value = '';
        loadBankAccounts();
    }

    document.getElementById('btn-add-bank-account').addEventListener('click', addBankAccount);
    loadBankAccounts();
</script>
@endsection
