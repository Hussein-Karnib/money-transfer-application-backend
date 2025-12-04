@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
        <p>Manage your money transfer platform</p>
    </div>
</div>

@if(session('success'))
    <div class="container-fluid px-4 mb-4">
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-label">Active Agents</div>
            <div class="stat-value">{{ $data['active_agents_count'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Total Transactions</div>
            <div class="stat-value">{{ $data['total_transactions_count'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">Pending Approvals</div>
            <div class="stat-value">{{ $data['pending_approvals_count'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">System Alerts</div>
            <div class="stat-value">0</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.approvals') }}" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Review Pending Approvals
                        @if(($data['pending_approvals_count'] ?? 0) > 0)
                            <span class="badge bg-danger badge-modern ms-2">{{ $data['pending_approvals_count'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-people me-2"></i>Manage Agents
                    </a>
                    <a href="{{ route('admin.statistics') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-graph-up me-2"></i>View Statistics
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-file-earmark-text me-2"></i>Reports
                    </a>
                    <a href="{{ route('admin.auditTable') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-clock-history me-2"></i>Audit Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>System Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Total Agents</small>
                    <p class="mb-0 h5">{{ $data['active_agents_count'] ?? 0 }} Active</p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Total Transactions</small>
                    <p class="mb-0 h5">{{ $data['total_transactions_count'] ?? 0 }} Transfers</p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Pending Approvals</small>
                    <p class="mb-0 h5 text-warning">{{ $data['pending_approvals_count'] ?? 0 }} Waiting</p>
                </div>
                <hr>
                <p class="text-muted small mb-0">
                    <i class="bi bi-shield-check me-1"></i>
                    Last updated: {{ now()->format('M d, Y H:i') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
