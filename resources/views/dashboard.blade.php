@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <h2 class="mb-3">Dashboard</h2>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Last Transfer Status</h6>
                        <p class="h5" id="last-transfer-status">Loading...</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Transfers</h6>
                        <p class="h5" id="total-transfers">0</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Unread Notifications</h6>
                        <p class="h5" id="dashboard-unread-count">0</p>
                    </div>
                </div>
            </div>
        </div>

        <h4>Recent Transfers</h4>
        <table class="table table-striped" id="recent-transfers-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Beneficiary</th>
                <th>Amount</th>
                <th>From → To</th>
                <th>Status</th>
                <th>Initiated</th>
            </tr>
            </thead>
            <tbody>
            {{-- Filled by JS --}}
            </tbody>
        </table>
    </div>

    <div class="col-md-4">
        <h4>Quick Actions</h4>
        <div class="d-grid gap-2">
            <a href="{{ route('app.transfers.create') }}" class="btn btn-primary">
                New Transfer
            </a>
            <a href="{{ route('app.beneficiaries.index') }}" class="btn btn-outline-secondary">
                Manage Beneficiaries
            </a>
            <a href="{{ route('app.bank-accounts.index') }}" class="btn btn-outline-secondary">
                Manage Bank Accounts
            </a>
            <a href="{{ route('app.notifications.index') }}" class="btn btn-outline-secondary">
                View Notifications
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (async function loadDashboard() {
        try {
            // Get transfers (existing JSON endpoint)
            const res = await fetch("{{ route('transfers.index') }}");
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            const page = data.data;
            const transfers = page.data ?? page;

            const tbody = document.querySelector('#recent-transfers-table tbody');
            tbody.innerHTML = '';

            let lastStatus = 'N/A';

            transfers.slice(0, 5).forEach((t, i) => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td>${t.id}</td>
                    <td>${t.beneficiary?.full_name ?? '-'}</td>
                    <td>${t.amount} ${t.currency_from}</td>
                    <td>${t.currency_from} → ${t.currency_to}</td>
                    <td>${t.status}</td>
                    <td>${t.initiated_at ?? ''}</td>
                `;
                tbody.appendChild(tr);

                if (i === 0) lastStatus = t.status;
            });

            document.getElementById('last-transfer-status').textContent = lastStatus;
            document.getElementById('total-transfers').textContent = page.total ?? transfers.length;
        } catch (e) {
            console.error(e);
        }

        // Unread notifications count
        try {
            const res2 = await fetch("{{ route('notifications.unread') }}");
            if (!res2.ok) return;
            const data2 = await res2.json();
            if (data2.success) {
                const count = data2.data.length || 0;
                document.getElementById('dashboard-unread-count').textContent = count;
            }
        } catch (e) {
            console.error(e);
        }
    })();
</script>
@endsection
