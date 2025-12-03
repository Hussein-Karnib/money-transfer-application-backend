<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     * Includes filtering options for the Admin.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $data = $query->get();

        return view('admin.auditTable', ['data' => $data]);
    }

    /**
     * Display the specified audit log details.
     * Useful for inspecting the 'metadata' JSON column.
     */
    public function show($id)
    {
        $data = AuditLog::with('user')->findOrFail($id);

        return view('admin.auditShow', ['data' => $data]);
    }

    /**
     * Remove logs older than a specific date (Maintenance/Pruning).
     */
    public function prune(Request $request)
    {
        $request->validate([
            'days_retention' => 'required|integer|min:30', // keep at least 30 days
        ]);

        $date = now()->subDays($request->days_retention);

        $deletedCount = AuditLog::where('created_at', '<', $date)->delete();

        return redirect()->route('admin.auditTable')
            ->with('success', "Pruned $deletedCount logs older than {$request->days_retention} days.");
    }

    /**
     * Static helper to log an action from anywhere.
     */
    public static function logSystemAction(
        ?int $user_id,
        string $action,
        ?string $table_name = null,
        ?int $record_id = null,
        array $data = []
    ): void {
        AuditLog::create([
            'user_id'    => $user_id,
            'actor_type' => 'system',        // or 'user'/'admin' if you want to extend this
            'actor_id'   => $user_id,
            'action'     => $action,
            'table_name' => $table_name,
            'record_id'  => $record_id,
            'metadata'   => $data,
            'created_at' => now(),
        ]);
    }
}
