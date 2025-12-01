<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\User_Verification;
use App\Models\Agent;
use App\Models\Transfer;

class StatisticController extends Controller
{
    public function statistic()
    {
        $data = [
            'users_count' => User::count(),
            'verified_users_count' => User_Verification::where('status', 'approved')->count(),
            'agents_count' => Agent::count(),
            'pending_agents_count' => Agent::where('status', 'pending')->count(),
            'transfers_count' => Transfer::count(),
            'total_transfer_volume' => Transfer::sum('amount'),
        ];

        return view('admin.statistics', ['data' => $data]);
    }

    public function dashboard()
    {
        $data = [
            'active_agents_count' => Agent::where('status', 'approved')->count(),
            'total_transactions_count' => Transfer::count(),
            'pending_approvals_count' => Agent::where('status', 'pending')->count(),
        ];

        return view('admin.dashboard', ['data' => $data]);
    }
}
