<?php

namespace App\Http\Controllers;

use App\Models\Audit_Log;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     * Includes filtering options for the Admin.
     */
    public function index(Request $request)
    {
        // Start the query
        $query = Audit_Log::with('user')->latest();

        // Filter by Action (e.g., "login", "transfer_approved")
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Filter by User ID
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Paginate results (Logs can get very large, so 20 per page is reasonable)
        $logs = $query->paginate(20)->withQueryString();

        return view('admin.audit_logs.index', compact('logs'));
    }

    /**
     * Display the specified audit log details.
     * Useful for inspecting the 'metadata' JSON column.
     */
    public function show($id)
    {
        // We use findOrFail with the ID directly since route model binding 
        // might conflict if the class name 'Audit_Log' doesn't match standard naming conventions.
        $log = Audit_Log::with('user')->findOrFail($id);

        return view('admin.audit_logs.show', compact('log'));
    }

    /**
     * Remove logs older than a specific date (Maintenance/Pruning).
     * Usually, you don't delete individual logs, but you might clear old ones.
     */
    public function prune(Request $request)
    {
        $request->validate([
            'days_retention' => 'required|integer|min:30', // Ensure we keep at least 30 days
        ]);

        $date = now()->subDays($request->days_retention);

        $deletedCount = Audit_Log::where('created_at', '<', $date)->delete();

        return redirect()->route('audit_logs.index')
            ->with('success', "Pruned $deletedCount logs older than {$request->days_retention} days.");
    }
}