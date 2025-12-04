@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Notifications</h2>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.markAllRead') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">Mark all as read</button>
        </form>
    @endif
</div>

@if($notifications->count() > 0)
    <ul class="list-group">
        @foreach($notifications as $notification)
            @php
                $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                $isRead = $notification->read_at !== null;
            @endphp
            <li class="list-group-item d-flex justify-content-between align-items-center {{ $isRead ? '' : 'list-group-item-info' }}">
                <div>
                    <strong>{{ $data['message'] ?? ($data['new_status'] ?? 'Transfer update') }}</strong><br>
                    @if(isset($data['transfer_id']))
                        <small>Transfer #{{ $data['transfer_id'] }}</small>
                    @endif
                    <br>
                    <small class="text-muted">{{ $notification->created_at->format('M d, Y H:i') }}</small>
                </div>
                <div>
                    @if(!$isRead)
                        <form method="POST" action="{{ route('notifications.markAsRead', $notification->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as read</button>
                        </form>
                    @else
                        <span class="badge bg-secondary">Read</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
@else
    <div class="alert alert-info">
        <p class="mb-0">No notifications found.</p>
    </div>
@endif
@endsection
