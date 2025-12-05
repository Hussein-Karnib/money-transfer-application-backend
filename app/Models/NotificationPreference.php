<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'transfer_initiated',
        'transfer_status_update',
        'beneficiary_action',
        'verification_required',
        'promotion_alert',
        'security_alert',
        'commission_earned',
        'settlement_alert',
        'admin_message',
        'system_alert',
        'channel_email',
        'channel_sms',
        'channel_push',
        'channel_in_app',
        'channel_dashboard',
        'email_frequency',
        'sms_frequency',
        'digest_send_time',
        'mute_all',
        'mute_until',
    ];

    protected $casts = [
        'transfer_initiated' => 'boolean',
        'transfer_status_update' => 'boolean',
        'beneficiary_action' => 'boolean',
        'verification_required' => 'boolean',
        'promotion_alert' => 'boolean',
        'security_alert' => 'boolean',
        'commission_earned' => 'boolean',
        'settlement_alert' => 'boolean',
        'admin_message' => 'boolean',
        'system_alert' => 'boolean',
        'channel_email' => 'boolean',
        'channel_sms' => 'boolean',
        'channel_push' => 'boolean',
        'channel_in_app' => 'boolean',
        'channel_dashboard' => 'boolean',
        'digest_send_time' => 'datetime:H:i',
        'mute_until' => 'datetime',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Get enabled channels for this user
     */
    public function getEnabledChannels(): array
    {
        return array_keys(array_filter([
            'email' => $this->channel_email,
            'sms' => $this->channel_sms,
            'push' => $this->channel_push,
            'in_app' => $this->channel_in_app,
            'dashboard' => $this->channel_dashboard,
        ]));
    }

    /**
     * Check if user is currently muted
     */
    public function isMuted(): bool
    {
        if (!$this->mute_all) {
            return false;
        }

        if ($this->mute_until && $this->mute_until->isFuture()) {
            return true;
        }

        if ($this->mute_until && $this->mute_until->isPast()) {
            $this->update(['mute_all' => false, 'mute_until' => null]);
            return false;
        }

        return true;
    }

    /**
     * Check if notification type is enabled
     */
    public function isNotificationTypeEnabled(string $type): bool
    {
        $typeKey = match ($type) {
            'transfer_initiated' => 'transfer_initiated',
            'transfer_status_update' => 'transfer_status_update',
            'beneficiary_action' => 'beneficiary_action',
            'verification_required' => 'verification_required',
            'promotion_alert' => 'promotion_alert',
            'security_alert' => 'security_alert',
            'commission_earned' => 'commission_earned',
            'settlement_alert' => 'settlement_alert',
            'admin_message' => 'admin_message',
            'system_alert' => 'system_alert',
            default => null,
        };

        return $typeKey ? $this->{$typeKey} : false;
    }

    /**
     * Check if channel is enabled
     */
    public function isChannelEnabled(string $channel): bool
    {
        $channelKey = "channel_{$channel}";
        return $this->{$channelKey} ?? false;
    }
}
