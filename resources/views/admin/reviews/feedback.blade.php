@extends('layouts.app')

@section('title', 'User Feedback')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-chat-text me-2"></i>User Feedback</h1>
        <p>View all messages and feedback sent by users.</p>
    </div>
</div>

<div class="card-modern">
    <div class="card-body">
        @if($reviews->count() > 0)
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                            <tr>
                                <td style="width: 20%;">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                            {{ strtoupper(substr($review->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $review->user->name ?? 'Unknown User' }}</div>
                                            <div class="text-muted small">{{ $review->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $review->message }}
                                </td>
                                <td style="width: 15%;">
                                    <small class="text-muted">
                                        {{ $review->created_at->format('M d, Y H:i') }}
                                    </small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $reviews->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-chat-square-text" style="font-size: 4rem; color: #cbd5e0;"></i>
                <p class="text-muted mt-3">No feedback received yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
