<?php

namespace App\Support;

use App\Models\Agent;
use App\Models\Transfer;
use App\Models\User;

/**
 * Thin wrapper for Person 4's notification helper.
 * Replace the internals of these methods when the real implementation is ready.
 */
class NotificationHelper
{
    public static function agentStatusChanged(Agent $agent, string $status): void
    {
        $user = $agent->user;

        if (! $user instanceof User) {
            return;
        }

        // Person 4: implement real notification call here.
        // Example placeholder:
        // Person4Notifier::send($user, 'agent_status_changed', [...]);
    }

    public static function transferReadyForPickup(Transfer $transfer): void
    {
        // Person 4: notify sender/beneficiary that transfer is ready for pickup.
    }

    public static function transferCashedOut(Transfer $transfer): void
    {
        // Person 4: notify sender/beneficiary that transfer has been cashed out.
    }
}


