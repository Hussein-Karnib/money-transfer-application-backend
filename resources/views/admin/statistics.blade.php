@extends('layouts.app')

@section('title', 'Admin Statistics')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-graph-up me-2"></i>Statistics</h1>
        <p>View system statistics and metrics</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-calendar-range me-2"></i>Filter by Date Range
            </div>
            <div class="card-body">
                <form action="{{ route('admin.searchDate') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="from_date" class="form-label-modern">Start Date</label>
                        <input type="date" name="from_date" id="from_date" class="form-control form-control-modern" required>
                    </div>
                    <div class="col-md-4">
                        <label for="to_date" class="form-label-modern">End Date</label>
                        <input type="date" name="to_date" id="to_date" class="form-control form-control-modern" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                            <i class="bi bi-funnel me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6 col-lg-4">
        <div class="stat-card primary">
            <div class="stat-label">Total Users</div>
            <div class="stat-value">{{ number_format($data['users_count']) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card success">
            <div class="stat-label">Verified Users</div>
            <div class="stat-value">{{ number_format($data['verified_users_count']) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card warning">
            <div class="stat-label">Total Agents</div>
            <div class="stat-value">{{ number_format($data['agents_count']) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card danger">
            <div class="stat-label">Pending Agents</div>
            <div class="stat-value">{{ number_format($data['pending_agents_count']) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card primary">
            <div class="stat-label">Total Transfers</div>
            <div class="stat-value">{{ number_format($data['transfers_count']) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card success">
            <div class="stat-label">Total Transfer Volume</div>
            <div class="stat-value">{{ number_format($data['total_transfer_volume'], 2) }}</div>
        </div>
    </div>
</div>
@endsection
