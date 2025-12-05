# Multi-Role, Multi-Channel Notification System

## Overview

A comprehensive notification system for the Money Transfer Application supporting:
- **3 User Roles**: Users, Agents, Admins
- **5 Notification Channels**: Email, SMS, Push, In-app, Dashboard
- **Preference Management**: Users can customize notification types and channels
- **Batch Notifications**: Daily/weekly digest emails
- **Real-time Tracking**: Delivery status, read/unread status

---

## Database Tables

### 1. `notification_preferences`
Stores user notification preferences and channel settings.

**Key Columns:**
- `notifiable_type`, `notifiable_id` - Polymorphic relation (User, Agent, Admin)
- Notification Type Toggles: `transfer_initiated`, `transfer_status_update`, `beneficiary_action`, etc.
- Channel Toggles: `channel_email`, `channel_sms`, `channel_push`, `channel_in_app`, `channel_dashboard`
- Frequency Settings: `email_frequency`, `sms_frequency`, `digest_send_time`
- Mute Settings: `mute_all`, `mute_until`

### 2. `notification_logs`
Complete audit trail of all notifications sent.

**Key Columns:**
- `notifiable_type`, `notifiable_id` - Who received it
- `type` - Notification type (transfer_initiated, fraud_detected, etc.)
- `title`, `message`, `description`
- `channels` - JSON array of channels used
- `related_type`, `related_id` - Polymorphic relation to Transfer, Promotion, etc.
- `data` - JSON payload with notification details
- `sent_at`, `read_at`, `opened_at` - Tracking timestamps
- `delivery_status` - JSON tracking delivery per channel

---

## Models

### NotificationPreference
```php
// Access user preferences
$preferences = $user->getOrCreateNotificationPreference();

// Check if muted
if ($preferences->isMuted()) { ... }

// Get enabled channels
$channels = $preferences->getEnabledChannels();

// Check if notification type is enabled
if ($preferences->isNotificationTypeEnabled('transfer_initiated')) { ... }
```

### NotificationLog
```php
// Get unread count
$unreadCount = NotificationLog::getUnreadCount($user);

// Get unread notifications
$unread = NotificationLog::getUnread($user);

// Mark as read
$notification->markAsRead();

// Mark as opened (for email/push tracking)
$notification->markAsOpened();
```

---

## Notification Classes

### USER NOTIFICATIONS

#### 1. TransferInitiated
Triggered when user creates a transfer.
```php
use App\Notifications\TransferInitiated;

$user->notify(new TransferInitiated($transfer, $senderName, $beneficiaryName));
// OR use helper
NotificationHelper::transferInitiated($transfer);
```

#### 2. TransferStatusChanged
Triggered when transfer status changes.
```php
use App\Notifications\TransferStatusChanged;

NotificationHelper::transferStatusChanged($transfer, 'pending', 'processing');
```

#### 3. BeneficiaryActionNotification
Triggered when beneficiary picks up cash or confirms receipt.
```php
NotificationHelper::beneficiaryActionPerformed($transfer, 'pickup');
```

#### 4. VerificationRequiredNotification
Reminds user to complete verification.
```php
NotificationHelper::verificationRequired($user, 'id_verification');
```

#### 5. PromotionAlertNotification
Sends promotional offers to users.
```php
NotificationHelper::promotionAlert($user, $promotion, 'Limited time offer!');
```

#### 6. SecurityAlertNotification
Alerts user of account security events.
```php
NotificationHelper::securityAlert($user, 'new_bank_account', [
    'account_name' => 'John Doe Bank Account',
    'ip' => '192.168.1.1',
]);
```

### AGENT NOTIFICATIONS

#### 1. NewTransferAssignedNotification
Alerts agent of new transfer to process.
```php
NotificationHelper::agentTransferAssigned($transfer, $agent);
```

#### 2. CommissionEarnedNotification
Notifies agent of earned commission.
```php
NotificationHelper::agentCommissionEarned($agent, 150.00, 'USD', 'TRN-001', 2500.00);
```

#### 3. SettlementPendingNotification
Informs agent of pending settlement.
```php
NotificationHelper::agentSettlementPending($agent, 5000.00, 'USD', 25, '2025-12-06');
```

#### 4. CashPositionAlertNotification
Alerts agent when cash balance is low.
```php
NotificationHelper::agentCashPositionAlert($agent, 'low_balance', 800.00, 1000.00, 'USD');
// For critical balance
NotificationHelper::agentCashPositionAlert($agent, 'critical_balance', 200.00, 1000.00, 'USD');
```

#### 5. WorkingHoursChangedNotification
Notifies agent of working hours changes.
```php
NotificationHelper::agentWorkingHoursChanged($agent, '2025-12-06', '9:00-17:00', '9:00-18:00');
```

### ADMIN NOTIFICATIONS

#### 1. FraudDetectedNotification
Critical alert when suspicious activity detected.
```php
NotificationHelper::fraudDetected(
    'John Doe',
    'user-123',
    85, // risk score out of 100
    'User has 8 bank accounts in 2 days',
    [
        'bank_account_count' => 8,
        'time_period' => '2 days',
        'recent_transfers' => '$50,000',
    ]
);
```

#### 2. VerificationsPendingNotification
Alerts admin of pending document reviews.
```php
NotificationHelper::verificationsPending(500, 'id_verification', 3); // 3 days average wait
```

#### 3. SystemAlertNotification
General system alerts.
```php
NotificationHelper::systemAlert(
    'high_load',
    'Server load at 85%',
    'warning',
    [
        'current_load' => '85%',
        'threshold' => '80%',
        'cpu_usage' => '75%',
        'memory_usage' => '78%',
    ]
);
```

#### 4. AgentPerformanceNotification
Reports agent performance metrics.
```php
NotificationHelper::agentPerformanceReport(
    'Ahmed Store',
    150, // transfers completed
    25000.00, // total volume
    'USD',
    'today',
    [
        'avg_transfer_time' => '2 hours',
        'success_rate' => '98%',
        'customer_rating' => '4.8/5',
    ]
);
```

#### 5. UnusualActivityNotification
Alerts about unusual platform activity.
```php
NotificationHelper::unusualActivity(
    'volume_spike',
    'Transfer volume increased by 200% in last hour',
    [
        'previous_volume' => '$10,000/hour',
        'current_volume' => '$30,000/hour',
        'surge_factor' => '3x',
    ],
    250 // affected users
);
```

---

## NotificationHelper Usage

### Sending Notifications

```php
use App\Support\NotificationHelper;

// USER NOTIFICATIONS
NotificationHelper::transferInitiated($transfer);
NotificationHelper::transferStatusChanged($transfer, 'pending', 'processing');
NotificationHelper::beneficiaryActionPerformed($transfer, 'pickup');
NotificationHelper::verificationRequired($user, 'id_verification');
NotificationHelper::promotionAlert($user, $promotion);
NotificationHelper::securityAlert($user, 'new_bank_account', ['account_name' => '...']);

// AGENT NOTIFICATIONS
NotificationHelper::agentTransferAssigned($transfer, $agent);
NotificationHelper::agentCommissionEarned($agent, 150.00, 'USD', 'TRN-001', 2500.00);
NotificationHelper::agentSettlementPending($agent, 5000.00, 'USD', 25, '2025-12-06');
NotificationHelper::agentCashPositionAlert($agent, 'low_balance', 800.00, 1000.00, 'USD');
NotificationHelper::agentWorkingHoursChanged($agent, '2025-12-06', '9:00-17:00', '9:00-18:00');

// ADMIN NOTIFICATIONS
NotificationHelper::fraudDetected('User Name', 'user-123', 85, 'Fraud Reason', [...]);
NotificationHelper::verificationsPending(500, 'id_verification', 3);
NotificationHelper::systemAlert('high_load', 'Message', 'warning', [...]);
NotificationHelper::agentPerformanceReport('Agent Name', 150, 25000, 'USD', 'today', [...]);
NotificationHelper::unusualActivity('volume_spike', 'Description', [...], 250);
```

### Managing Preferences

```php
// Get user preferences
$prefs = NotificationHelper::getUserPreferences($user);

// Update preferences
NotificationHelper::updatePreferences($user, [
    'transfer_initiated' => true,
    'channel_email' => true,
    'channel_sms' => false,
    'email_frequency' => 'daily',
    'mute_all' => false,
]);

// Get unread count
$count = NotificationHelper::getUnreadCount($user);

// Get unread notifications
$notifications = NotificationHelper::getUnreadNotifications($user, limit: 10);

// Mark as read
NotificationHelper::markAsRead($notification);

// Mark all as read
NotificationHelper::markAllAsRead($user);
```

---

## API Endpoints

### Get Notifications
```http
GET /api/notifications?limit=20&offset=0&unread_only=false
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "transfer_initiated",
      "title": "Transfer Initiated",
      "message": "Your transfer of 100 USD to John is processing",
      "sent_at": "2025-12-05T10:30:00Z",
      "read_at": null,
      "data": {...}
    }
  ],
  "pagination": {
    "total": 150,
    "limit": 20,
    "offset": 0
  },
  "unread_count": 15
}
```

### Get Unread Count
```http
GET /api/notifications/unread-count
Authorization: Bearer {token}
```

### Mark as Read
```http
POST /api/notifications/{id}/read
Authorization: Bearer {token}
```

### Mark All as Read
```http
POST /api/notifications/read-all
Authorization: Bearer {token}
```

### Delete Notification
```http
DELETE /api/notifications/{id}
Authorization: Bearer {token}
```

### Get Preferences
```http
GET /api/notifications/preferences
Authorization: Bearer {token}
```

### Update Preferences
```http
POST /api/notifications/preferences
Authorization: Bearer {token}

{
  "transfer_initiated": true,
  "transfer_status_update": true,
  "channel_email": true,
  "channel_sms": false,
  "channel_push": true,
  "email_frequency": "daily",
  "digest_send_time": "09:00",
  "mute_all": false,
  "mute_until": null
}
```

### Mute Notifications
```http
POST /api/notifications/mute
Authorization: Bearer {token}

{
  "mute_all": true,
  "until": "2025-12-06T18:00:00Z"
}
```

---

## Scheduled Jobs

### Daily Digest Email
Add to `app/Console/Kernel.php`:

```php
use App\Jobs\SendNotificationDigestJob;

protected function schedule(Schedule $schedule)
{
    // Send daily digest at 9 AM
    $schedule->job(new SendNotificationDigestJob('daily'))
        ->dailyAt('09:00')
        ->onOneServer();

    // Send weekly digest every Monday at 9 AM
    $schedule->job(new SendNotificationDigestJob('weekly'))
        ->weeklyOn(1, '09:00')
        ->onOneServer();
}
```

---

## Integration Examples

### In Transfer Controller
```php
<?php

namespace App\Http\Controllers;

use App\Support\NotificationHelper;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $transfer = Transfer::create($request->validated());

        // Send notification
        NotificationHelper::transferInitiated($transfer);

        return response()->json(['success' => true, 'transfer' => $transfer]);
    }

    public function updateStatus(Transfer $transfer, $status)
    {
        $oldStatus = $transfer->status;
        $transfer->update(['status' => $status]);

        // Notify user
        NotificationHelper::transferStatusChanged($transfer, $oldStatus, $status);

        return response()->json(['success' => true]);
    }
}
```

### In Transfer Event Listener
```php
<?php

namespace App\Listeners;

use App\Events\TransferStatusChanged;
use App\Support\NotificationHelper;

class SendTransferNotification
{
    public function handle(TransferStatusChanged $event)
    {
        NotificationHelper::transferStatusChanged(
            $event->transfer,
            $event->oldStatus,
            $event->newStatus
        );
    }
}
```

---

## Testing

### Test Sending Notifications
```php
<?php

use App\Models\User;
use App\Models\Transfer;
use App\Support\NotificationHelper;

// Test user notification
$user = User::find(1);
NotificationHelper::transferInitiated($transfer);

// Check if notification was logged
$logs = $user->notificationLogs()->latest()->first();
assert($logs->type === 'transfer_initiated');

// Check unread count
assert(NotificationHelper::getUnreadCount($user) > 0);
```

---

## Configuration

### Email Setup
In `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@moneytransfer.app
MAIL_FROM_NAME="Money Transfer App"
```

### SMS Setup (Optional)
Add SMS driver in `config/services.php`:
```php
'twilio' => [
    'sid' => env('TWILIO_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM'),
],
```

### Queue Setup
In `.env`:
```env
QUEUE_CONNECTION=database
```

Run migrations to create jobs table:
```bash
php artisan queue:table
php artisan migrate
```

---

## Troubleshooting

### Notifications not sending?
1. Check queue is running: `php artisan queue:work`
2. Check `notification_logs` table for errors
3. Verify user has `channel_*` enabled
4. Check user is not muted

### Email digest not sending?
1. Ensure scheduler is running
2. Check `jobs` table for failed jobs
3. Verify `email_frequency` setting

### Preferences not saving?
1. Ensure `NotificationPreference` model migration ran
2. Check polymorphic relationship setup
3. Verify user has created preference record

---

## Future Enhancements

- [ ] Push notification support (Firebase, Expo)
- [ ] SMS delivery via Twilio
- [ ] Web socket real-time notifications
- [ ] Mobile app notification badges
- [ ] Notification templates in admin panel
- [ ] A/B testing for notification content
- [ ] Notification analytics dashboard
- [ ] In-app notification sound/vibration
- [ ] Notification grouping/threading
- [ ] Notification retry mechanisms

---

## Support

For issues or questions, contact: support@moneytransfer.app
