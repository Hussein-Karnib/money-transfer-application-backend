<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'notifiable_type',
        'notifiable_id',
        'type',
        'title',
        'message',
        'description',
        'channels',
        'related_type',
        'related_id',
        'data',
        'sent_at',
        'read_at',
        'opened_at',
        'delivery_status',
    ];

    protected $casts = [
        'channels' => 'array',
        'data' => 'array',
        'delivery_status' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function related()
    {
        return $this->morphTo();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Mark notification as opened (for email/push tracking)
     */
    public function markAsOpened(): void
    {
        $this->update(['opened_at' => now()]);
    }

    /**
     * Get unread count for a notifiable
     */
    public static function getUnreadCount($notifiable)
    {
        return self::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get unread notifications
     */
    public static function getUnread($notifiable)
    {
        return self::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id)
            ->whereNull('read_at')
            ->orderByDesc('sent_at')
            ->get();
    }
}
