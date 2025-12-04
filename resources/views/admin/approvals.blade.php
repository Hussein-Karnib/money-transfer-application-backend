@extends('layouts.app')

@section('title', 'Admin Approvals')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-check-circle me-2"></i>Approvals & New Users</h1>
        <p>Review and approve pending agents and new users</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-people me-2"></i>Pending Agents
            </div>
            <div class="card-body">
                @if($pendingAgents->isEmpty())
                    <p class="text-muted mb-0">No pending agents.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Store Name</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingAgents as $agent)
                                <tr>
                                    <td>{{ $agent->user->name ?? 'N/A' }}</td>
                                    <td><strong>{{ $agent->store_name }}</strong></td>
                                    <td>{{ $agent->user->email ?? 'N/A' }}</td>
                                    <td>
                                        <form action="{{ route('admin.agents.update_status', $agent->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-sm btn-primary-modern btn-modern">
                                                <i class="bi bi-check-circle me-1"></i>Approve
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-person-plus me-2"></i>New Users
            </div>
            <div class="card-body">
                @if($newUsers->isEmpty())
                    <p class="text-muted mb-0">No new users.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($newUsers as $user)
                                <tr>
                                    <td><strong>{{ $user->name }}</strong></td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <form action="{{ route('admin.users.approve', $user->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-primary-modern btn-modern">
                                                <i class="bi bi-check-circle me-1"></i>Approve
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
