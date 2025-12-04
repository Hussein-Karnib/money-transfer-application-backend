@extends('layouts.app')

@section('content')
<h2>Notifications</h2>

<button class="btn btn-sm btn-outline-primary mb-3" id="btn-mark-all">
    Mark all as read
</button>

<ul class="list-group" id="notifications-list">
    {{-- JS --}}
</ul>
@endsection

@section('scripts')
<script>
    async function loadNotifications() {
        const res = await fetch("{{ route('notifications.index') }}");
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const list = document.getElementById('notifications-list');
        list.innerHTML = '';

        data.data.forEach(n => {
            const li = document.createElement('li');
            const read = n.read_at !== null;

            li.className = 'list-group-item d-flex justify-content-between align-items-center ' +
                (read ? '' : 'list-group-item-info');

            const payload = n.data || {};
            const text = payload.message || payload.status || 'Transfer update';

            li.innerHTML = `
                <div>
                    <strong>${text}</strong><br>
                    <small>Transfer #${payload.transfer_id ?? ''}</small>
                </div>
                <div>
                    ${read ? '' : `
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="markAsRead('${n.id}')">Mark as read</button>
                    `}
                </div>
            `;
            list.appendChild(li);
        });
    }

    async function markAsRead(id) {
        const res = await fetch(`/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (data.success) {
            loadNotifications();
        }
    }

    async function markAllAsRead() {
        const res = await fetch(`/notifications/read-all`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (data.success) {
            loadNotifications();
        }
    }

    document.getElementById('btn-mark-all').addEventListener('click', markAllAsRead);
    loadNotifications();
</script>
@endsection
