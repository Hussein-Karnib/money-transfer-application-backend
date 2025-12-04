<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserVerification;
use App\Models\Agent;
use App\Models\Transfer;

class StatisticController extends Controller
{
    public function statistic()
    {
        $data = [
            'users_count' => User::count(),
            'verified_users_count' => UserVerification::where('status', 'approved')->count(),
            'agents_count' => Agent::count(),
            'pending_agents_count' => Agent::where('status', 'pending')->count(),
            'transfers_count' => Transfer::count(),
            'total_transfer_volume' => Transfer::sum('amount'),
        ];

        return view('admin.statistics', ['data' => $data]);
    }
    
    public function searchDate(Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $data = [
            'users_count' => User::where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->count(),
            'verified_users_count' => UserVerification::where('status', 'approved')->where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->count(),
            'agents_count' => Agent::where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->count(),
            'pending_agents_count' => Agent::where('status', 'pending')->where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->count(),
            'transfers_count' => Transfer::where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->count(),
            'total_transfer_volume' => Transfer::where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->sum('amount'),
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
