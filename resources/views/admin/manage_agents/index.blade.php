@extends('layouts.app')

@section('title', 'Manage Admins')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Admins</h1>
    <a href="{{ route('admins.create') }}" class="btn btn-primary">Add Admin</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0 table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Privilege</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                    <tr>
                        <td>{{ $admin->id }}</td>
                        <td>{{ $admin->user->name }}</td>
                        <td>{{ $admin->user->email }}</td>
                        <td>{{ $admin->privilege_level }}</td>
                        <td class="text-end">
                            <a href="{{ route('admins.show', $admin) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('admins.edit', $admin) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admins.destroy', $admin) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Remove admin privileges?')">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No admins found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $admins->links() }}
    </div>
</div>
@endsection
