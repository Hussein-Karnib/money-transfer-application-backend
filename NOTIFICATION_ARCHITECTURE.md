# 📢 Notification System Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     NOTIFICATION SYSTEM                          │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│                           THREE ROLES                                     │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  👤 USERS                   🤝 AGENTS                   👨‍💼 ADMINS           │
│  ├─ Transfer initiated      ├─ Transfer assigned        ├─ Fraud detected   │
│  ├─ Status updates          ├─ Commission earned        ├─ Verifications    │
│  ├─ Beneficiary actions     ├─ Settlement pending       ├─ System alerts    │
│  ├─ Verification required   ├─ Cash position alerts     ├─ Performance      │
│  ├─ Promotion alerts        ├─ Working hours changes    ├─ Unusual activity │
│  └─ Security alerts         └─                          └─                 │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│                        FIVE CHANNELS                                      │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  📧 EMAIL           💬 SMS              🔔 PUSH            📱 IN-APP       │
│  └─ SMTP            └─ Twilio/Nexmo     └─ Firebase/Expo   └─ Database    │
│                     └─ Optional         └─ Optional        └─ Real-time    │
│                                                                            │
│  🎯 DASHBOARD                                                             │
│  └─ Web notifications & real-time feed                                   │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────┘
```

## Data Flow Architecture

```
┌──────────────────┐
│ Trigger Event    │
│ (e.g., Transfer  │
│  Created)        │
└────────┬─────────┘
         │
         ▼
┌──────────────────────────────┐
│ NotificationHelper::          │
│ transferInitiated($transfer)  │
└────────┬─────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ Check User Preferences                   │
│ - Is muted?                              │
│ - Has type enabled?                      │
│ - Get enabled channels                   │
└────────┬─────────────────────────────────┘
         │
         ├─ YES ──────────────────┐
         │                        │
         ▼                        ▼
   ┌─────────────┐        ┌──────────────────┐
   │ Create      │        │ Skip notification│
   │Notification │        │ Return early     │
   └─────┬───────┘        └──────────────────┘
         │
         ▼
    ┌─────────────────────────────┐
    │ Send to selected channels:  │
    │ - Email (Laravel Mail)      │
    │ - SMS (optional)            │
    │ - Push (optional)           │
    │ - In-app (database)         │
    │ - Dashboard (broadcast)     │
    └──────────┬──────────────────┘
               │
               ▼
    ┌──────────────────────┐
    │ Log to notification_ │
    │ logs table           │
    │ - Type               │
    │ - Channels sent      │
    │ - Data payload       │
    │ - Delivery status    │
    └──────────┬───────────┘
               │
    ┌──────────▼──────────┐
    │ Queue for sending   │
    │ (async)             │
    └─────────────────────┘
```

## Database Schema

```sql
┌─────────────────────────────────┐
│  notification_preferences       │
├─────────────────────────────────┤
│ id (PK)                         │
│ notifiable_type                 │ ◄─── Polymorphic
│ notifiable_id                   │
│                                 │
│ --- Notification Types ---       │
│ transfer_initiated              │
│ transfer_status_update          │
│ beneficiary_action              │
│ verification_required           │
│ promotion_alert                 │
│ security_alert                  │
│ commission_earned (Agent)       │
│ settlement_alert (Agent)        │
│ admin_message                   │
│ system_alert                    │
│                                 │
│ --- Channels ---                │
│ channel_email                   │
│ channel_sms                     │
│ channel_push                    │
│ channel_in_app                  │
│ channel_dashboard               │
│                                 │
│ --- Frequency ---               │
│ email_frequency (enum)          │
│ sms_frequency (enum)            │
│ digest_send_time                │
│                                 │
│ --- Mute Settings ---           │
│ mute_all                        │
│ mute_until                      │
│                                 │
│ created_at, updated_at          │
└─────────────────────────────────┘

┌──────────────────────────────────┐
│   notification_logs             │
├──────────────────────────────────┤
│ id (PK)                          │
│ notification_id                  │
│ notifiable_type                  │ ◄─── Who received
│ notifiable_id                    │
│                                  │
│ type (notification type)         │
│ title                            │
│ message                          │
│ description                      │
│                                  │
│ channels (JSON array)            │
│ related_type                     │ ◄─── What it's about
│ related_id                       │      (Transfer, etc.)
│ data (JSON payload)              │
│                                  │
│ --- Tracking ---                 │
│ sent_at                          │
│ read_at                          │
│ opened_at                        │
│ delivery_status (JSON)           │
│                                  │
│ created_at, updated_at           │
└──────────────────────────────────┘
```

## Notification Classes Structure

```
app/Notifications/
├── TransferInitiated.php
├── TransferStatusChanged.php
├── BeneficiaryActionNotification.php
├── VerificationRequiredNotification.php
├── PromotionAlertNotification.php
├── SecurityAlertNotification.php
│
├── Agent/
│   ├── NewTransferAssignedNotification.php
│   ├── CommissionEarnedNotification.php
│   ├── SettlementPendingNotification.php
│   ├── CashPositionAlertNotification.php
│   └── WorkingHoursChangedNotification.php
│
└── Admin/
    ├── FraudDetectedNotification.php
    ├── VerificationsPendingNotification.php
    ├── SystemAlertNotification.php
    ├── AgentPerformanceNotification.php
    └── UnusualActivityNotification.php
```

## API Endpoints

```
Authenticated Routes (middleware: auth:sanctum)

GET    /api/notifications
       Get paginated notifications with filters
       
GET    /api/notifications/unread-count
       Get count of unread notifications
       
POST   /api/notifications/{id}/read
       Mark single notification as read
       
POST   /api/notifications/read-all
       Mark all notifications as read
       
DELETE /api/notifications/{id}
       Delete notification
       
GET    /api/notifications/preferences
       Get user's notification preferences
       
POST   /api/notifications/preferences
       Update notification preferences
       
POST   /api/notifications/mute
       Mute/unmute notifications temporarily
```

## Notification Flow Example

### Scenario: User Creates Transfer

```
1. User calls TransferController::store()
   ├─ Create Transfer in DB
   ├─ Call: NotificationHelper::transferInitiated($transfer)
   │   │
   │   ├─ Get user preferences
   │   ├─ Check if "transfer_initiated" enabled
   │   ├─ Get enabled channels
   │   ├─ Create TransferInitiated notification instance
   │   │
   │   ├─ Send via each channel:
   │   │   ├─ Email ──► toMail() template
   │   │   ├─ SMS (if enabled)
   │   │   ├─ Push (if enabled)
   │   │   ├─ In-app ──► toArray() data
   │   │   └─ Dashboard broadcast
   │   │
   │   └─ Log to notification_logs:
   │       ├─ Type: transfer_initiated
   │       ├─ Channels: ['email', 'in_app', 'dashboard']
   │       ├─ Data: {transfer_id, amount, currency, etc}
   │       ├─ Sent_at: now()
   │       └─ Related: transfer
   │
   └─ Queue for async delivery (Laravel Queue)
   
2. Queue Worker processes notification
   ├─ Send emails (SMTP)
   ├─ Update delivery_status
   └─ Complete

3. User receives on channels:
   ├─ 📧 Email in inbox
   ├─ 📱 In-app notification
   └─ 🎯 Dashboard banner
```

## Preference Management Flow

```
User navigates to Settings → Notifications

1. Display current preferences
   ├─ Get: $user->getOrCreateNotificationPreference()
   └─ Show toggles for each:
      ├─ Notification types (transfer_initiated, etc.)
      ├─ Channels (email, sms, push, etc.)
      ├─ Frequency (instant, daily, weekly)
      └─ Mute option

2. User updates preferences
   ├─ POST /api/notifications/preferences
   ├─ NotificationHelper::updatePreferences($user, $data)
   └─ Update notification_preferences record

3. Future notifications respect new preferences
   ├─ Check: isNotificationTypeEnabled()
   ├─ Get: getEnabledChannels()
   ├─ Check: isMuted()
   └─ Send only to enabled channels
```

## Digest Email Flow

```
Scheduled Job: SendNotificationDigestJob

1. Daily at 09:00 (or custom time)
   ├─ Get all users with email_frequency = 'daily'
   ├─ For each user:
   │   ├─ Get unread notifications since yesterday
   │   ├─ Group by type
   │   ├─ Render email template
   │   ├─ Send via Email channel
   │   └─ Mark notifications as read
   └─ Log completion

2. Email contains:
   ├─ Summary: 15 notifications, grouped by type
   ├─ Recent notifications (last 5)
   ├─ Links to detailed view
   └─ Preference management link
```

## Channel Priority

```
Based on notification type and severity:

CRITICAL (Fraud, System Down):
   ├─ Email ✓ (forced)
   ├─ SMS ✓ (forced)
   ├─ Push ✓ (forced)
   ├─ In-app ✓ (forced)
   └─ Dashboard ✓ (forced)

HIGH (Agent Transfer, Security Alert):
   ├─ Email ✓
   ├─ SMS (if enabled)
   ├─ Push ✓ (if enabled)
   ├─ In-app ✓
   └─ Dashboard ✓

NORMAL (Transfer Status, Commission):
   ├─ Email (if enabled)
   ├─ SMS (if enabled)
   ├─ Push (if enabled)
   ├─ In-app ✓
   └─ Dashboard ✓

LOW (Promotion, General Info):
   ├─ Email (if enabled)
   ├─ SMS (if enabled)
   ├─ Push (if enabled)
   └─ In-app ✓
```

## State Transitions

```
Notification Lifecycle:

┌─────────────┐
│   Created   │ (notification sent)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Sent      │ (in notification_logs)
└──────┬──────┘
       │
       ▼ (user reads notification)
       │
   ┌───┴───┐
   │       │
   ▼       ▼
┌──────┐ ┌─────┐
│ Read │ │Open │ (email open tracked)
└──────┘ └─────┘
   │       │
   └───┬───┘
       │
       ▼
┌──────────────┐
│  Archived/   │ (old notifications)
│  Deleted     │
└──────────────┘
```

## Performance Metrics

```
Expected Performance:

Notification Creation:        ~5ms
Preference Lookup:            ~2ms
Channel Selection:            ~1ms
Notification Send (queued):   ~100ms-1s
Email Delivery:               ~5s (SMTP)
In-app Display:               Real-time (WebSocket optional)
Database Log:                 ~10ms
```

## Security & Privacy

```
Access Control:
├─ Users can only access their own notifications
├─ Agents can only access their assigned notifications
├─ Admins can access all notifications (audit)
└─ Role-based endpoint restrictions

Data Protection:
├─ Preferences stored in database (encrypted at rest)
├─ Sensitive data (account numbers) masked in logs
├─ Delivery status tracked per channel
├─ User can delete notifications
└─ GDPR compliance (deletion, export available)

Rate Limiting:
├─ API endpoints throttled: 60 req/min
├─ Email: Max 5 per hour per user
├─ SMS: Max 2 per hour per user
└─ Prevent notification spam
```

## Integration Points

```
Controllers that use NotificationHelper:

✅ TransferController
   ├─ store() ──► transferInitiated()
   └─ updateStatus() ──► transferStatusChanged()

✅ UserBankAccountController
   └─ store() ──► securityAlert()

✅ UserVerificationController
   └─ verifyIdCard() ──► verificationRequired()

✅ AgentTransactionController
   ├─ processTransfer() ──► agentTransferAssigned()
   └─ completeTransfer() ──► agentCommissionEarned()

✅ PromotionController
   └─ launch() ──► promotionAlert()

✅ FraudDetectionService
   └─ detectFraud() ──► fraudDetected()

✅ AdminController
   ├─ pendingVerifications() ──► verificationsPending()
   └─ systemMonitoring() ──► systemAlert()
```

---

## Quick Reference

| Component | Purpose | Location |
|-----------|---------|----------|
| NotificationPreference | Store user preferences | app/Models |
| NotificationLog | Audit trail | app/Models |
| TransferInitiated | Notify on transfer create | app/Notifications |
| NotificationHelper | Central API | app/Support |
| NotificationController | REST API | app/Http/Controllers/Api |
| SendNotificationDigestJob | Batch emails | app/Jobs |
| notification_digest.blade | Email template | resources/views/emails |

---

**System Status: ✅ FULLY IMPLEMENTED**

15 Notification Classes | 5 Channels | 3 Roles | 8 API Endpoints | Complete Documentation
