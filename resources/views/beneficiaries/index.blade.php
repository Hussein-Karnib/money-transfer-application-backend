@extends('layouts.app')

@section('content')
<h2>Beneficiaries</h2>

<div class="row mb-4">
    <div class="col-md-6">
        <form id="beneficiary-form" class="card card-body">
            @csrf
            <h5 class="mb-3">Add Beneficiary</h5>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" id="b_full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Country ID</label>
                <input type="number" id="b_country_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Transfer Method ID</label>
                <input type="number" id="b_transfer_method_id" class="form-control" required>
            </div>

            <button type="button" class="btn btn-primary" id="btn-add-beneficiary">
                Add
            </button>
        </form>
    </div>
</div>

<table class="table" id="beneficiaries-table">
    <thead>
    <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Country</th>
        <th>Method</th>
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
    async function loadBeneficiariesList() {
        const res = await fetch("{{ route('beneficiaries.index') }}");
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const tbody = document.querySelector('#beneficiaries-table tbody');
        tbody.innerHTML = '';

        data.data.forEach(b => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${b.id}</td>
                <td>${b.full_name}</td>
                <td>${b.country?.name ?? ''}</td>
                <td>${b.method?.name ?? ''}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteBeneficiary(${b.id})">Delete</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function addBeneficiary() {
        const payload = {
            full_name: document.getElementById('b_full_name').value,
            country_id: document.getElementById('b_country_id').value,
            transfer_method_id: document.getElementById('b_transfer_method_id').value
        };

        const res = await fetch("{{ route('beneficiaries.store') }}", {
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
            alert(data.message || 'Error adding beneficiary');
            return;
        }

        document.getElementById('b_full_name').value = '';
        loadBeneficiariesList();
    }

    async function deleteBeneficiary(id) {
        if (!confirm('Delete this beneficiary?')) return;

        const res = await fetch(`/beneficiaries/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (data.success) {
            loadBeneficiariesList();
        } else {
            alert(data.message || 'Error');
        }
    }

    document.getElementById('btn-add-beneficiary').addEventListener('click', addBeneficiary);
    loadBeneficiariesList();
</script>
@endsection
