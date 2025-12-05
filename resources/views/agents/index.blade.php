@extends('layouts.app')

@section('title', 'Agents')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Agents</h1>
    <a href="{{ route('agents.create') }}" class="btn btn-primary">Register New Agent</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0 table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Store</th>
                <th>Owner</th>
                <th>Status</th>
                <th>Address</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($agents as $agent)
                <tr>
                    <td>{{ $agent->id }}</td>
                    <td>{{ $agent->store_name }}</td>
                    <td>{{ $agent->user->name }}</td>
                    <td><span class="badge bg-secondary text-capitalize">{{ $agent->status }}</span></td>
                    <td>{{ $agent->address }}</td>
                    <td class="text-end">
                        <a href="{{ route('agents.show', $agent) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        <a href="{{ route('agents.edit', $agent) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No agents found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $agents->links() }}
    </div>
</div>
@endsection
