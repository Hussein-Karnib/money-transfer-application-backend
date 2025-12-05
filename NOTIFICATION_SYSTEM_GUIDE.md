# Notification System - Complete Implementation Guide

## Overview

A complete multi-role, multi-channel notification system for the Money Transfer Application with:
- **17+ notification types** (User, Agent, Admin)
- **5 delivery channels** (Email, SMS, Push, In-app, Dashboard)
- **User preference management** (enable/disable types and channels)
- **Complete audit trail** (notification logging and tracking)
- **REST API** with 8+ endpoints

---

## System Architecture

```
User Event (Transfer Created)
         ↓
NotificationHelper.transferInitiated($transfer)
         ↓
Check User Preferences
         ↓
Get Enabled Channels
         ↓
Send Notification
         ↓
Log to NotificationLog
```

---

## Implementation Summary

### ✅ Completed Components

1. **NotificationHelper** (app/Support/NotificationHelper.php) - 400+ lines
   - 17+ static methods covering all notification types
   - Preference checking
   - Audit logging
   - Mute/unmute functionality

2. **NotificationController** (app/Http/Controllers/NotificationController.php)
   - 8 REST API endpoints
   - Preference management
   - Notification listing with filters
   - Mute/unmute operations

3. **Notification Classes** (17 total)
   - **User** (6): TransferInitiated, TransferStatusChanged, BeneficiaryAction, VerificationRequired, PromotionAlert, SecurityAlert
   - **Agent** (6): NewTransferAssigned, CommissionEarned, SettlementPending, WorkingHoursChanged, CashPositionAlert, + Legacy support
   - **Admin** (5): FraudDetected, VerificationsPending, SystemAlert, AgentPerformance, UnusualActivity

4. **Models**
   - `NotificationLog` - Complete audit trail
   - `NotificationPreference` - User preferences (created during KYC implementation)
   - User/Agent/Admin relationships

5. **API Routes** (11 endpoints)
   - List notifications with filtering
   - Mark as read (single/all)
   - Delete notifications
   - Get unread count
   - Preference management
   - Mute/unmute

---

## Usage Examples

### 1. Sending Notifications

#### User Notifications

```php
use App\Support\NotificationHelper;
use App\Models\Transfer;

$transfer = Transfer::find(1);

// Transfer initiated
NotificationHelper::transferInitiated($transfer);

// Transfer status updated
NotificationHelper::transferStatusChanged(
    $transfer, 
    'pending',      // old status
    'completed',    // new status
    'John'          // recipient name
);

// Beneficiary action
NotificationHelper::beneficiaryAction(
    $transfer,
    'pickup',       // action: pickup, confirmed, rejected
    'Picked up at agent store'  // optional details
);

// Verification required
NotificationHelper::verificationRequired(
    $user,
    'Identity Verification Required',
    'Complete ID verification to add bank accounts'
);

// Promotion alert
NotificationHelper::promotionAlert(
    $user,
    '5% Discount on Transfers',
    'Get 5% discount on transfers over $500',
    5,              // discount percentage
    'SAVE5'         // promo code
);

// Security alert
NotificationHelper::securityAlert(
    $user,
    'New Bank Account Added',
    'A new bank account was added to your profile'
);
```

#### Agent Notifications

```php
// New transfer assigned
NotificationHelper::transferAssigned(
    $agent,
    $transfer,
    95.00,          // payout amount
    'EUR'           // payout currency
);

// Settlement pending
NotificationHelper::settlementPending(
    $agent,
    5000.00,        // settlement amount
    'USD',          // currency
    25              // number of transfers
);

// Commission earned
NotificationHelper::commissionEarned(
    $agent,
    12.50,          // commission amount
    'USD',
    'TRN-12345',    // transfer ID
    100             // transfer amount
);

// Working hours changed
NotificationHelper::workingHoursChanged(
    $agent,
    '9:00 AM - 6:00 PM',  // new schedule
    'Updated schedule effective tomorrow'
);

// Cash position alert
NotificationHelper::cashPositionAlert(
    $agent,
    150.00,         // current balance
    'USD',
    'low'           // alert type: low, critical
);
```

#### Admin Notifications

```php
// Fraud detected
NotificationHelper::fraudDetected(
    'User',         // userType
    user_id,        // userId
    85,             // riskScore (0-100)
    'Multiple bank accounts created in 24 hours',
    ['accounts_created' => 8, 'days' => 2]
);

// Verifications pending
NotificationHelper::verificationsPending(
    500,            // pendingCount
    15              // daysOldest
);

// System alert
NotificationHelper::systemAlert(
    'High Load',
    'Server load at 85%, CPU usage high',
    ['cpu' => 85, 'memory' => 92, 'disk' => 78],
    'warning'       // severity: info, warning, critical
);

// Agent performance report
NotificationHelper::agentPerformance(
    $agent,
    150,            // transferCount
    12500.00,       // totalVolume
    'USD',
    45              // averageTimeMinutes
);

// Unusual activity
NotificationHelper::unusualActivity(
    'volume_spike',
    'Transfer volume increased 200% in last hour',
    ['previous_hour' => 50, 'current_hour' => 150]
);
```

### 2. API Endpoints

#### List Notifications
```bash
GET /api/notifications
Query parameters:
  ?page=1
  ?per_page=20
  ?type=transfer_initiated
  ?unread_only=true
```

#### Get Unread Count
```bash
GET /api/notifications/unread-count
Response: { "unread_count": 5 }
```

#### Get Unread Notifications
```bash
GET /api/notifications/unread
```

#### Mark as Read
```bash
POST /api/notifications/{id}/read
```

#### Mark All as Read
```bash
POST /api/notifications/read-all
```

#### Delete Notification
```bash
DELETE /api/notifications/{id}
```

#### Get Preferences
```bash
GET /api/notifications/preferences
```

#### Update Preferences
```bash
POST /api/notifications/preferences
{
  "notification_types": {
    "transfer_initiated": true,
    "promotion_alert": false
  },
  "channels": {
    "email": true,
    "sms": false,
    "push": true,
    "in_app": true
  },
  "email_frequency": "daily",
  "sms_frequency": "instant"
}
```

#### Mute Notifications
```bash
POST /api/notifications/mute
{
  "duration_hours": 24
}
// OR
{
  "until": "2024-12-06 10:00:00"
}
```

#### Unmute Notifications
```bash
POST /api/notifications/unmute
```

---

## Notification Types

### User Notifications (6)
- **transfer_initiated** - New transfer created
- **transfer_status_update** - Transfer status changed
- **beneficiary_action** - Beneficiary pickup/confirmation
- **verification_required** - ID verification needed
- **promotion_alert** - Promotional offers
- **security_alert** - Account security events

### Agent Notifications (6)
- **transfer_assigned** - New transfer assignment
- **commission_earned** - Commission received
- **settlement_pending** - Settlement queue alert
- **working_hours_changed** - Schedule update
- **cash_position_alert** - Low/critical cash balance
- Plus legacy: agentStatusChanged, transferReadyForPickup, transferCashedOut

### Admin Notifications (5)
- **fraud_detected** - High-risk transfer alert
- **verifications_pending** - Documents awaiting review
- **system_alert** - System health/maintenance
- **agent_performance** - Agent KPI reports
- **unusual_activity** - Unusual platform activity

---

## Channels

Notifications can be delivered via:
- **email** - Full message with details
- **sms** - Short, urgent messages only
- **push** - Mobile push notifications
- **in_app** - Database notifications (always)
- **dashboard** - Real-time dashboard alerts

Users can control each channel and set frequency (instant, daily, weekly, never).

---

## Preferences Management

### Default Preferences
All notification types enabled by default.
All channels enabled by default.
Email frequency: instant
SMS frequency: instant

### User-Controlled Settings
- Enable/disable notification types
- Enable/disable channels
- Email frequency (instant/daily/weekly/never)
- SMS frequency (instant/daily/weekly/never)
- Digest send time
- Mute temporarily or permanently

---

## Database Schema

### notification_logs table
```sql
- id
- notifiable_type (polymorphic: User, Agent, Admin)
- notifiable_id
- type (notification type)
- title
- message
- description (optional)
- channels (JSON array)
- related_type (polymorphic)
- related_id
- data (JSON payload)
- delivery_status (JSON with per-channel status)
- sent_at
- read_at
- opened_at
- created_at/updated_at
```

### notification_preferences table
```sql
- id
- notifiable_type
- notifiable_id
- transfer_initiated (boolean)
- transfer_status_update (boolean)
- beneficiary_action (boolean)
- verification_required (boolean)
- promotion_alert (boolean)
- security_alert (boolean)
- commission_earned (boolean)
- settlement_alert (boolean)
- admin_message (boolean)
- system_alert (boolean)
- channel_email, channel_sms, channel_push, channel_in_app, channel_dashboard (boolean)
- email_frequency (enum: instant, daily, weekly, never)
- sms_frequency (enum: instant, daily, weekly, never)
- digest_send_time (time)
- mute_all (boolean)
- mute_until (timestamp)
- created_at/updated_at
```

---

## Integration in Controllers

### Transfer Controller
```php
use App\Support\NotificationHelper;

// In store() method (create transfer)
NotificationHelper::transferInitiated($transfer);

// In update() method (status change)
NotificationHelper::transferStatusChanged($transfer, $oldStatus, $newStatus);
```

### Agent Transaction Controller
```php
// Assign transfer to agent
NotificationHelper::transferAssigned($agent, $transfer, $payout, $currency);

// Settlement pending
NotificationHelper::settlementPending($agent, $amount, $currency, $count);

// Commission earned
NotificationHelper::commissionEarned($agent, $amount, $currency, $transferId, $transferAmount);
```

### Admin Controllers
```php
// Fraud detection service
NotificationHelper::fraudDetected('User', $userId, $riskScore, $reason, $details);

// Verification admin
NotificationHelper::verificationsPending($pendingCount, $daysOldest);

// System monitoring
NotificationHelper::systemAlert('High Load', $message, $metrics, 'critical');
```

---

## Advanced Features

### Critical Alerts
Some notifications force all channels regardless of user preferences:
- **Fraud detected** (risk score > 80)
- **Critical cash position** (balance below critical threshold)
- **System critical alerts** (severity = critical)

### Batch Notifications
Daily/weekly digest emails can be configured via scheduler:
```php
// app/Console/Kernel.php
$schedule->job(new SendNotificationDigestJob, 'notifications')
    ->daily()
    ->at('08:00');
```

### Smart Filtering
Users can filter notifications:
```bash
GET /api/notifications?type=transfer_initiated&unread_only=true
```

---

## Error Handling

All notification methods wrap try-catch blocks to prevent fatal errors:
```php
try {
    $user->notify(new TransferInitiated($transfer));
} catch (\Exception $e) {
    \Log::error('Failed to send notification', ['error' => $e->getMessage()]);
}
```

Notifications failing won't break the application flow.

---

## Testing

### Quick Test
```bash
# Get current user's notifications
GET /api/notifications

# Update preferences
POST /api/notifications/preferences
{
  "channels": { "email": false }
}

# Send test notification
php artisan tinker
> NotificationHelper::transferInitiated(Transfer::first())

# Check notification was logged
> NotificationLog::latest()->first()
```

### Full Workflow
1. Create transfer → notification sent
2. Update transfer status → notification sent
3. User receives notifications in /api/notifications
4. User updates preferences
5. New notifications follow preferences
6. User marks as read
7. Unread count decreases

---

## Monitoring

Monitor notification system health:
```bash
# Pending notifications
SELECT COUNT(*) FROM notification_logs WHERE read_at IS NULL;

# By type
SELECT type, COUNT(*) FROM notification_logs GROUP BY type;

# Failed delivery
SELECT * FROM notification_logs WHERE delivery_status->>'email' = 'failed';

# Notifications by user
SELECT notifiable_id, COUNT(*) as count FROM notification_logs 
WHERE notifiable_type = 'App\\Models\\User' 
GROUP BY notifiable_id ORDER BY count DESC;
```

---

## Production Checklist

- [x] Notification classes created and tested
- [x] NotificationHelper with 17+ methods
- [x] API endpoints implemented
- [x] Database models and migrations
- [x] Preference management
- [x] Audit logging
- [x] Error handling
- [ ] Email templates customized
- [ ] SMS gateway configured
- [ ] Push notification service configured
- [ ] Scheduler configured for digests
- [ ] Monitoring and alerting setup
- [ ] Performance testing

---

## Support

For specific notification types, see comments in:
- `app/Notifications/` - User notifications
- `app/Notifications/Agent/` - Agent notifications
- `app/Notifications/Admin/` - Admin notifications
- `app/Support/NotificationHelper.php` - All methods

Each notification class includes `via()`, `toMail()`, `toDatabase()`, and `toArray()` methods.
