@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>My Transfers</h2>
    <a href="{{ route('app.transfers.create') }}" class="btn btn-primary">New Transfer</a>
</div>

<table class="table table-hover" id="transfers-table">
    <thead>
    <tr>
        <th>#</th>
        <th>Beneficiary</th>
        <th>Amount</th>
        <th>From → To</th>
        <th>Status</th>
        <th>Reference</th>
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
    async function loadTransfers() {
        const res = await fetch("{{ route('transfers.index') }}");
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const page = data.data;
        const transfers = page.data ?? page;
        const tbody = document.querySelector('#transfers-table tbody');
        tbody.innerHTML = '';

        transfers.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${t.id}</td>
                <td>${t.beneficiary?.full_name ?? '-'}</td>
                <td>${t.amount} ${t.currency_from}</td>
                <td>${t.currency_from} → ${t.currency_to}</td>
                <td>${t.status}</td>
                <td>${t.reference}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary me-1"
                        onclick="viewTransfer(${t.id})">View</button>
                    ${t.status === 'queued'
                        ? `<button class="btn btn-sm btn-outline-danger" onclick="cancelTransfer(${t.id})">Cancel</button>`
                        : ''
                    }
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function cancelTransfer(id) {
        if (!confirm('Cancel this transfer?')) return;

        const res = await fetch(`/transfers/${id}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (data.success) {
            alert('Transfer cancelled');
            loadTransfers();
        } else {
            alert(data.message || 'Error');
        }
    }

    function viewTransfer(id) {
        // For now just alert; later you can build a modal or detail page.
        window.location.href = `/transfers/${id}`;
    }

    loadTransfers();
</script>
@endsection
