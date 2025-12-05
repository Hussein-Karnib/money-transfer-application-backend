@extends('layouts.app')

@section('title', 'KYC Reviews')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <div>
            <h1 class="mb-0"><i class="bi bi-person-badge me-2"></i>KYC Reviews</h1>
            <p class="mb-0 text-muted">Review pending identity documents</p>
        </div>
    </div>
</div>

<div class="card-modern">
    <div class="card-header">
        <i class="bi bi-list-check me-2"></i>Submissions
    </div>
    <div class="card-body">
        @include('partials.alerts')

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Expiry</th>
                        <th>Document</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>#{{ $item->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item->user?->name ?? 'N/A' }}</div>
                                <div class="small text-muted">{{ $item->user?->email }}</div>
                            </td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $item->id_type) }}</td>
                            <td>
                                <span class="badge bg-{{ $item->status === 'verified' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $item->created_at->diffForHumans() }}</td>
                            <td class="small">{{ $item->expiry_date ? $item->expiry_date->toDateString() : '—' }}</td>
                            <td>
                                @if($item->document_path)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.kyc.document', $item->id) }}" target="_blank">
                                        <i class="bi bi-file-earmark-text me-1"></i>View
                                    </a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($item->status === 'pending')
                                    <form method="POST" action="{{ route('admin.kyc.approve', $item->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i> Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.kyc.reject', $item->id) }}" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="review_comment" value="Document not clear/legible">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i> Reject</button>
                                    </form>
                                @else
                                    <div class="small text-muted">
                                        {{ ucfirst($item->status) }} {{ $item->verified_at ? 'on ' . $item->verified_at->toDateTimeString() : '' }}
                                    </div>
                                    @if($item->review_comment)
                                        <div class="small">{{ $item->review_comment }}</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No submissions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
