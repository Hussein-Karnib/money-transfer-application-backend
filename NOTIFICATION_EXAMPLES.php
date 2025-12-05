<?php

/**
 * NOTIFICATION SYSTEM - QUICK START EXAMPLES
 * 
 * Copy and paste these examples into your controllers/jobs to start using notifications
 */

namespace App\Examples;

use App\Support\NotificationHelper;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Agent;
use App\Models\Admin;
use App\Models\Promotion;

// ============================================
// EXAMPLE 1: Send Transfer Initiated Notification
// ============================================
// Location: TransferController::store()

public function createTransfer(Request $request)
{
    $transfer = Transfer::create([
        'sender_id' => auth()->id(),
        'beneficiary_id' => $request->beneficiary_id,
        'amount' => $request->amount,
        'currency_from' => $request->currency_from,
        'currency_to' => $request->currency_to,
        'transfer_method_id' => $request->method_id,
        'status' => 'pending',
        'initiated_at' => now(),
        'estimated_delivery_at' => now()->addHours(4),
    ]);

    // ✅ Send notification to user
    NotificationHelper::transferInitiated($transfer);

    // ✅ Optionally notify assigned agent if one exists
    if ($agent = Agent::findByTransfer($transfer)) {
        NotificationHelper::agentTransferAssigned($transfer, $agent);
    }

    return response()->json(['success' => true, 'transfer' => $transfer]);
}


// ============================================
// EXAMPLE 2: Update Transfer Status & Notify
// ============================================
// Location: TransferController::updateStatus()

public function updateStatus(Transfer $transfer, Request $request)
{
    $oldStatus = $transfer->status;
    $newStatus = $request->status;

    $transfer->update(['status' => $newStatus]);

    // ✅ Notify user of status change
    NotificationHelper::transferStatusChanged($transfer, $oldStatus, $newStatus);

    // ✅ If funds sent, also notify beneficiary (sender)
    if ($newStatus === 'funds_sent') {
        // Notify beneficiary receiver via SMS/WhatsApp
        NotificationHelper::beneficiaryActionPerformed($transfer, 'funds_sent');
    }

    return response()->json(['success' => true]);
}


// ============================================
// EXAMPLE 3: Notify User They Need Verification
// ============================================
// Location: UserBankAccountController::store()

public function addBankAccount(Request $request)
{
    $user = auth()->user();

    // Check if user is verified
    if (!$user->isVerified()) {
        // ✅ Send verification required notification
        NotificationHelper::verificationRequired($user, 'id_verification');

        return response()->json([
            'error' => 'Please complete identity verification first',
        ], 422);
    }

    // Create bank account
    $bankAccount = $user->bankAccounts()->create($request->validated());

    // ✅ Notify user of security event
    NotificationHelper::securityAlert($user, 'new_bank_account', [
        'account_name' => $bankAccount->account_holder_name,
        'bank_name' => $bankAccount->bank_name,
        'account_number' => substr($bankAccount->account_number, -4),
    ]);

    return response()->json(['success' => true, 'account' => $bankAccount]);
}


// ============================================
// EXAMPLE 4: Agent Notifications
// ============================================
// Location: AgentTransactionController::processTransfer()

public function processTransfer(Transfer $transfer, Agent $agent)
{
    $oldStatus = $transfer->status;

    // Process the transfer
    $transfer->update([
        'status' => 'processing',
        'agent_id' => $agent->id,
    ]);

    // ✅ Notify agent of new assignment
    NotificationHelper::agentTransferAssigned($transfer, $agent);

    // ... Process transfer ...

    // Complete the transfer
    $transfer->update(['status' => 'completed', 'completed_at' => now()]);

    // ✅ Calculate and notify commission earned
    $commission = $transfer->amount * ($agent->commission_rate / 100);
    $totalEarned = $agent->getTotalCommissionAttribute();

    NotificationHelper::agentCommissionEarned(
        $agent,
        $commission,
        $transfer->currency_from,
        $transfer->reference,
        $totalEarned
    );

    return response()->json(['success' => true]);
}


// ============================================
// EXAMPLE 5: Alert Agent of Low Cash
// ============================================
// Location: Agent balance check middleware/job

public function checkAgentCashPosition(Agent $agent)
{
    $minimumThreshold = 1000.00;
    $currentBalance = $agent->getCurrentCashBalance(); // your method

    if ($currentBalance < ($minimumThreshold * 0.5)) {
        // Critical - below 50% of threshold
        NotificationHelper::agentCashPositionAlert(
            $agent,
            'critical_balance',
            $currentBalance,
            $minimumThreshold,
            'USD'
        );
    } elseif ($currentBalance < $minimumThreshold) {
        // Low - below threshold
        NotificationHelper::agentCashPositionAlert(
            $agent,
            'low_balance',
            $currentBalance,
            $minimumThreshold,
            'USD'
        );
    }
}


// ============================================
// EXAMPLE 6: Notify Agent of Working Hours Change
// ============================================
// Location: AgentHourController::update()

public function updateWorkingHours(Request $request, Agent $agent)
{
    $oldHours = $agent->hours()->where('day_of_week', $request->day)->first();
    $oldHoursString = $oldHours ? $oldHours->open_time . '-' . $oldHours->close_time : null;

    // Update hours
    $agent->hours()->updateOrCreate(
        ['day_of_week' => $request->day],
        [
            'open_time' => $request->open_time,
            'close_time' => $request->close_time,
            'is_closed' => $request->is_closed,
        ]
    );

    $newHoursString = $request->is_closed ? null : $request->open_time . '-' . $request->close_time;

    // ✅ Notify agent of schedule change
    NotificationHelper::agentWorkingHoursChanged(
        $agent,
        now()->addDay()->format('Y-m-d'),
        $oldHoursString,
        $newHoursString,
        $request->is_closed
    );

    return response()->json(['success' => true]);
}


// ============================================
// EXAMPLE 7: Send Promotion Alerts
// ============================================
// Location: PromotionController::activate() or scheduled job

public function launchPromotion(Promotion $promotion)
{
    // Get all users for this promotion
    $users = User::where('status', 'active')
        ->where('role_id', Role::where('name', 'User')->first()->id)
        ->get();

    foreach ($users as $user) {
        // Check if user has promotions enabled
        $prefs = $user->getOrCreateNotificationPreference();
        if ($prefs->promotion_alert) {
            // ✅ Send promotion notification
            NotificationHelper::promotionAlert(
                $user,
                $promotion,
                "Limited time: {$promotion->discount_percentage}% off transfers over \${$promotion->min_amount}"
            );
        }
    }

    return response()->json(['success' => true]);
}


// ============================================
// EXAMPLE 8: Admin - Fraud Detection Alert
// ============================================
// Location: FraudDetectionService or job

public function detectFraud(User $user)
{
    $riskScore = 0;
    $reasons = [];
    $details = [];

    // Check for too many bank accounts
    $bankAccountCount = $user->bankAccounts()->count();
    if ($bankAccountCount >= 8) {
        $riskScore += 30;
        $reasons[] = 'User has too many bank accounts (' . $bankAccountCount . ')';
        $details['bank_account_count'] = $bankAccountCount;
    }

    // Check for large recent transfer
    $recentTransfer = $user->sentTransfers()
        ->where('created_at', '>=', now()->subDay())
        ->sum('amount');
    if ($recentTransfer > $user->balance * 10) {
        $riskScore += 40;
        $reasons[] = 'Unusually large transfer detected';
        $details['recent_transfer_volume'] = $recentTransfer;
    }

    // If risk score is high, alert admins
    if ($riskScore >= 50) {
        // ✅ Alert all admins of fraud
        NotificationHelper::fraudDetected(
            $user->name,
            $user->id,
            $riskScore,
            implode(', ', $reasons),
            $details
        );
    }
}


// ============================================
// EXAMPLE 9: Admin - Pending Verifications Alert
// ============================================
// Location: Scheduled job running daily

public function checkPendingVerifications()
{
    $pending = \App\Models\UserVerification::where('status', 'pending')
        ->where('created_at', '<', now()->subDay())
        ->count();

    if ($pending > 0) {
        $averageDays = \App\Models\UserVerification::where('status', 'pending')
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->first()
            ->avg_days ?? 0;

        // ✅ Alert admins
        NotificationHelper::verificationsPending(
            $pending,
            'id_verification',
            (int)$averageDays
        );
    }
}


// ============================================
// EXAMPLE 10: Admin - System Alerts
// ============================================
// Location: System monitoring service

public function monitorSystemHealth()
{
    $load = sys_getloadavg()[0];
    $threshold = 0.8; // 80%

    if ($load > $threshold) {
        // ✅ Alert admins of high system load
        NotificationHelper::systemAlert(
            'high_load',
            'Server load is critical',
            'critical',
            [
                'current_load' => round($load * 100) . '%',
                'threshold' => '80%',
                'timestamp' => now(),
            ]
        );
    }
}


// ============================================
// EXAMPLE 11: Manage User Notification Preferences
// ============================================
// Location: NotificationPreferenceController::update()

public function updatePreferences(Request $request)
{
    $request->validate([
        'transfer_initiated' => 'boolean',
        'transfer_status_update' => 'boolean',
        'promotion_alert' => 'boolean',
        'channel_email' => 'boolean',
        'channel_sms' => 'boolean',
        'email_frequency' => 'in:instant,daily,weekly,never',
        'mute_until' => 'nullable|date',
    ]);

    // ✅ Update preferences
    $success = NotificationHelper::updatePreferences(
        auth()->user(),
        $request->validated()
    );

    return response()->json([
        'success' => $success,
        'message' => $success ? 'Preferences updated' : 'Failed to update',
    ]);
}


// ============================================
// EXAMPLE 12: Get Unread Notifications (API)
// ============================================
// Location: NotificationController::index()

public function getNotifications(Request $request)
{
    $limit = $request->query('limit', 20);
    $unreadOnly = $request->boolean('unread_only', false);

    // ✅ Get unread notifications
    if ($unreadOnly) {
        $notifications = NotificationHelper::getUnreadNotifications(
            auth()->user(),
            $limit
        );
    } else {
        $notifications = auth()->user()
            ->notificationLogs()
            ->latest('sent_at')
            ->paginate($limit);
    }

    return response()->json([
        'success' => true,
        'data' => $notifications,
        'unread_count' => NotificationHelper::getUnreadCount(auth()->user()),
    ]);
}


// ============================================
// EXAMPLE 13: Mark Notification as Read
// ============================================
// Location: NotificationController::markAsRead()

public function markAsRead($notificationId)
{
    $notification = \App\Models\NotificationLog::findOrFail($notificationId);

    // ✅ Mark as read
    NotificationHelper::markAsRead($notification);

    return response()->json(['success' => true]);
}


// ============================================
// EXAMPLE 14: Mute Notifications
// ============================================
// Location: NotificationController::mute()

public function muteNotifications(Request $request)
{
    $request->validate([
        'mute_all' => 'boolean|required',
        'until' => 'nullable|date|after:now',
    ]);

    // ✅ Mute notifications
    NotificationHelper::updatePreferences(
        auth()->user(),
        [
            'mute_all' => $request->boolean('mute_all'),
            'mute_until' => $request->input('until'),
        ]
    );

    return response()->json([
        'success' => true,
        'message' => $request->boolean('mute_all') 
            ? 'Notifications muted' 
            : 'Notifications unmuted',
    ]);
}


// ============================================
// EXAMPLE 15: Schedule Digest Emails
// ============================================
// Location: app/Console/Kernel.php

use App\Jobs\SendNotificationDigestJob;

protected function schedule(Schedule $schedule)
{
    // Send daily digest at 9 AM
    $schedule->job(new SendNotificationDigestJob('daily'))
        ->dailyAt('09:00')
        ->description('Send daily notification digest emails');

    // Send weekly digest every Monday at 9 AM
    $schedule->job(new SendNotificationDigestJob('weekly'))
        ->weeklyOn(1, '09:00')
        ->description('Send weekly notification digest emails');
}
