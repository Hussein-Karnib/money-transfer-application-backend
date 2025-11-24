<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Transfer; // Assuming you have this model based on schema
use App\Models\User;
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
        return view('admin.reports.index', compact('reports'));
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
            'type' => 'required|in:transactions,users,performance',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $type = $request->type;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // 1. Fetch Data based on Type
        $data = [];
        if ($type === 'transactions') {
            // Example: Fetch transfers within date range
            // Note: Ensure Transfer model exists or replace with DB query
            $data = Transfer::whereBetween('created_at', [$startDate, $endDate])->get();
        } elseif ($type === 'users') {
            $data = User::whereBetween('created_at', [$startDate, $endDate])->get();
        }

        // 2. Generate File Content (Simple CSV example)
        // In a real app, you might use a library like 'laravel-excel' or 'dompdf'
        $csvContent = "ID,Date,Details\n";
        foreach ($data as $item) {
            $csvContent .= "{$item->id},{$item->created_at},Generated Item\n";
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

        return redirect()->route('reports.index')->with('success', 'Report generated successfully.');
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

        return redirect()->route('reports.index')->with('success', 'Report deleted.');
    }
}

