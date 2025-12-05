<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Agent;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReportController extends Controller
{

    public function index()
    {
        // Show newest reports first
        $reports = Report::with('author')->latest('generated_at')->paginate(10);
        return view('admin.reports', compact('reports'));
    }


    public function create()
    {
        return view('admin.reports.create');
    }


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


        $data = [];
        $headers = [];
        
        if ($type === 'transactions') {
            $data = Transfer::with(['sender', 'beneficiary'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            $headers = ['ID', 'Sender', 'Beneficiary', 'Amount', 'Currency', 'Status', 'Date'];
        } elseif ($type === 'platform_usage') {

            $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
            $newAgents = Agent::whereBetween('created_at', [$startDate, $endDate])->count();
            $activeAgents = Agent::where('status', 'approved')->count();
            $totalTransfers = Transfer::whereBetween('created_at', [$startDate, $endDate])->count();
            

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
            $data = Review::with('user')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->latest()
                ->get();
            $headers = ['ID', 'User', 'Email', 'Message', 'Date'];
        }


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
                $row[] = $item->id;
                $row[] = $item->user ? $item->user->name : 'N/A';
                $row[] = $item->user ? $item->user->email : 'N/A';
                // Sanitize message for CSV
                $messageJSON = json_encode($item->message); // Escape quotes/newlines using JSON, or simple replace
                // Simple replace is safely standard for simple CSV exports
                $safeMessage = str_replace(["\r", "\n", ","], [" ", " ", ";"], $item->message);
                $row[] = $safeMessage;
                $row[] = $item->created_at->toDateTimeString();
            }
            
            $csvContent .= implode(',', $row) . "\n";
        }


        $filename = 'report_' . $type . '_' . time() . '.csv';
        $filePath = 'reports/' . $filename;


        Storage::put($filePath, $csvContent);


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

        return redirect()->route('admin.reports.index')->with('success', 'Report generated successfully.');
    }


    public function download(Report $report)
    {
        if (!Storage::exists($report->file_path)) {
            return back()->with('error', 'File not found.');
        }

        return Storage::download($report->file_path);
    }


    public function destroy(Report $report)
    {

        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }


        $report->delete();

        return redirect()->route('admin.reports.index')->with('success', 'Report deleted.');
    }
}

