@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-clock-history me-2"></i>Audit Logs</h1>
        <p>Track all system actions and changes</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-list-ul me-2"></i>System Audit Logs
            </div>
            <div class="card-body">
                @if($data->isEmpty())
                    <p class="text-muted mb-0">No audit logs found.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Metadata</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $logged)
                                <tr>
                                    <td>{{ $logged->id }}</td>
                                    <td>
                                        @if($logged->user)
                                            <strong>{{ $logged->user->name }}</strong>
                                            <br><small class="text-muted">{{ $logged->user->email }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info badge-modern">
                                            {{ $logged->action }}
                                        </span>
                                    </td>
                                    <td>{{ $logged->table_name ?? 'N/A' }}</td>
                                    <td>{{ $logged->record_id ?? 'N/A' }}</td>
                                    <td>
                                        @if($logged->metadata)
                                            <small class="text-muted">{{ json_encode($logged->metadata) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $logged->created_at->format('M d, Y H:i') }}</td>
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
