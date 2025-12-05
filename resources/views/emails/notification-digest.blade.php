@component('mail::message')

# {{ ucfirst($frequency) }} Notification Digest

Hello {{ $notifiable->name }},

You have **{{ count($notifications) }}** unread notifications from the past {{ $frequency }}.

@component('mail::table')
| Type | Message | Time |
|------|---------|------|
@foreach ($notifications as $notification)
| {{ ucfirst(str_replace('_', ' ', $notification->type)) }} | {{ Str::limit($notification->message, 50) }} | {{ $notification->sent_at->diffForHumans() }} |
@endforeach
@endcomponent

## Notification Summary

@php
    $grouped = $notifications->groupBy('type');
@endphp

@foreach ($grouped as $type => $notifs)
- **{{ ucfirst(str_replace('_', ' ', $type)) }}**: {{ count($notifs) }} notification{{ count($notifs) > 1 ? 's' : '' }}
@endforeach

---

### Recent Notifications

@foreach ($notifications->take(5) as $notification)

#### {{ $notification->title }}

**Type**: {{ ucfirst(str_replace('_', ' ', $notification->type)) }}

{{ $notification->message }}

{{ $notification->sent_at->format('M d, Y H:i A') }}

@if ($notification->related_id)
[View Details]({{ url('/' . strtolower(class_basename($notification->related_type)) . '/' . $notification->related_id) }})
@endif

---

@endforeach

@if (count($notifications) > 5)

You have {{ count($notifications) - 5 }} more notifications. Visit your dashboard to see all.

@endif

@component('mail::button', ['url' => url('/app/notifications')])
View All Notifications
@endcomponent

---

**Notification Preferences**: You're receiving {{ $frequency }} digests. [Manage your preferences]({{ url('/app/notification-preferences') }})

Thanks,<br>
{{ config('app.name') }}

@endcomponent
