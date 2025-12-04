<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Transfer; // Assuming you have this model based on schema
use App\Models\User;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display a listing of the generated reports.
     */
    public function index()
    {
        // Show newest reports first
        $reports = Report::with('author')->latest('generated_at')->paginate(10);
        return view('admin.reports', compact('reports'));
    }

    /**
     * Show the form for generating a new report.
     */
    public function create()
    {
        return view('admin.reports.create');
    }

    /**
     * Handle the report generation logic.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:transactions,platform_usage,feedback',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $type = $request->type;
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // 1. Fetch Data based on Type
        $data = [];
        $headers = [];
        
        if ($type === 'transactions') {
            $data = Transfer::with(['sender', 'beneficiary'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            $headers = ['ID', 'Sender', 'Beneficiary', 'Amount', 'Currency', 'Status', 'Date'];
        } elseif ($type === 'platform_usage') {
            // Aggregate data for platform usage
            $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
            $newAgents = Agent::whereBetween('created_at', [$startDate, $endDate])->count();
            $activeAgents = Agent::where('status', 'active')->count(); // Snapshot
            $totalTransfers = Transfer::whereBetween('created_at', [$startDate, $endDate])->count();
            
            // We'll create a single row for this summary report
            $data = [
                [
                    'metric' => 'New Users',
                    'value' => $newUsers
                ],
                [
                    'metric' => 'New Agents',
                    'value' => $newAgents
                ],
                [
                    'metric' => 'Active Agents (Current)',
                    'value' => $activeAgents
                ],
                [
                    'metric' => 'Total Transfers',
                    'value' => $totalTransfers
                ]
            ];
            $headers = ['Metric', 'Value'];
        } elseif ($type === 'feedback') {
            // Placeholder for feedback
            $data = [];
            $headers = ['ID', 'User', 'Rating', 'Comment', 'Date'];
        }

        // 2. Generate File Content (CSV)
        $csvContent = implode(',', $headers) . "\n";
        
        foreach ($data as $item) {
            $row = [];
            if ($type === 'transactions') {
                $row[] = $item->id;
                $row[] = $item->sender ? $item->sender->name : 'N/A';
                $row[] = $item->beneficiary ? $item->beneficiary->name : 'N/A';
                $row[] = $item->amount;
                $row[] = $item->currency_from;
                $row[] = $item->status;
                $row[] = $item->created_at;
            } elseif ($type === 'platform_usage') {
                $row[] = $item['metric'];
                $row[] = $item['value'];
            } elseif ($type === 'feedback') {
                // Empty for now
            }
            
            $csvContent .= implode(',', $row) . "\n";
        }

        // 3. Define File Path
        $filename = 'report_' . $type . '_' . time() . '.csv';
        $filePath = 'reports/' . $filename;

        // 4. Save File to Storage (storage/app/reports)
        Storage::put($filePath, $csvContent);

        // 5. Create Database Record
        Report::create([
            'type' => $type,
            'generated_at' => now(),
            'generated_by' => Auth::id(),
            'file_path' => $filePath,
            'parameters' => [
                'start_date' => $startDate->toDateString(), 
                'end_date' => $endDate->toDateString()
            ],
        ]);

        return redirect()->route('admin.reports')->with('success', 'Report generated successfully.');
    }

    /**
     * Download the file associated with the report.
     */
    public function download(Report $report)
    {
        if (!Storage::exists($report->file_path)) {
            return back()->with('error', 'File not found.');
        }

        return Storage::download($report->file_path);
    }

    /**
     * Remove the report record and the file.
     */
    public function destroy(Report $report)
    {
        // Delete physical file
        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }

        // Delete DB record
        $report->delete();

        return redirect()->route('admin.reports')->with('success', 'Report deleted.');
    }
}

