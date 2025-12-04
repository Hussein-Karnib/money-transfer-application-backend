@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-bell me-2"></i>Notifications</h1>
                <p>Stay updated with your transfer notifications</p>
            </div>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.markAllRead') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-check-all me-2"></i>Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if($notifications->count() > 0)
    <div class="card-modern">
        <div class="card-body">
            @foreach($notifications as $notification)
                @php
                    $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                    $isRead = $notification->read_at !== null;
                @endphp
                <div class="notification-item p-3 mb-3 rounded {{ $isRead ? 'bg-light' : 'border-start border-primary border-4' }}" style="background: {{ $isRead ? '#f7fafc' : '#fff' }};">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-{{ $isRead ? 'check-circle text-muted' : 'circle-fill text-primary' }} me-2"></i>
                                <strong class="{{ $isRead ? 'text-muted' : '' }}">{{ $data['message'] ?? ($data['new_status'] ?? 'Transfer update') }}</strong>
                            </div>
                            @if(isset($data['transfer_id']))
                                <small class="text-muted">
                                    <i class="bi bi-arrow-left-right me-1"></i>Transfer #{{ $data['transfer_id'] }}
                                </small>
                            @endif
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->created_at->format('M d, Y H:i') }}
                                </small>
                            </div>
                        </div>
                        <div>
                            @if(!$isRead)
                                <form method="POST" action="{{ route('notifications.markAsRead', $notification->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-check me-1"></i>Mark as read
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-secondary badge-modern">Read</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($notifications->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card-modern">
        <div class="card-body text-center py-5">
            <i class="bi bi-bell-slash" style="font-size: 4rem; color: #cbd5e0;"></i>
            <h4 class="mt-3 mb-2">No Notifications</h4>
            <p class="text-muted">You're all caught up! No new notifications.</p>
        </div>
    </div>
@endif
@endsection
