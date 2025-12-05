@extends('layouts.app')

@section('title', 'Manage Agents - Admin')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-people me-2"></i>Manage Agents</h1>
        <p>View and manage all registered agents</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-funnel me-2"></i>Filter Agents
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.agents.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label-modern">Filter by Status</label>
                        <select class="form-select form-control-modern" id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="{{ route('agents.map_all') }}" class="btn btn-outline-modern btn-modern w-100">
                            <i class="bi bi-map me-2"></i>View Agent Map
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-list-ul me-2"></i>Agents List
            </div>
            <div class="card-body">
                @if($agents->isEmpty())
                    <p class="text-muted mb-0">No agents found.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Store Name</th>
                                    <th>Owner</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agents as $agent)
                                <tr>
                                    <td>{{ $agent->id }}</td>
                                    <td><strong>{{ $agent->store_name ?? 'N/A' }}</strong></td>
                                    <td>{{ $agent->user->name ?? 'N/A' }}</td>
                                    <td>{{ $agent->user->email ?? 'N/A' }}</td>
                                    <td>{{ $agent->address ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($agent->status) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'suspended' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }} badge-modern">{{ $agent->status ?? 'pending' }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('agents.public_profile', $agent->id) }}" class="btn btn-outline-info" target="_blank">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
                                            @if($agent->status !== 'approved')
                                                <form method="POST" action="{{ route('admin.agents.update_status', $agent->id) }}" class="d-inline" onsubmit="return confirm('Approve this agent?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn btn-outline-success">
                                                        <i class="bi bi-check-circle me-1"></i>Approve
                                                    </button>
                                                </form>
                                            @endif
                                            @if($agent->status !== 'suspended')
                                                <form method="POST" action="{{ route('admin.agents.update_status', $agent->id) }}" class="d-inline" onsubmit="return confirm('Suspend this agent?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="suspended">
                                                    <button type="submit" class="btn btn-outline-warning">
                                                        <i class="bi bi-pause-circle me-1"></i>Suspend
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.agents.destroy', $agent->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this agent?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $agents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
