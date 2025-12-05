<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource (Admin).
     */
    public function index()
    {
        $reviews = Review::with('user')->latest()->paginate(15);
        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Show the form for creating a new resource (User).
     */
    public function create()
    {
        return view('reviews.create');
    }

    /**
     * Store a newly created resource in storage (User).
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        // 1. Save to Database
        $review = Review::create([
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        // 2. Log to CSV
        // Format: Timestamp, User ID, User Name, User Email, Message
        $csvLine = [
            now()->toDateTimeString(),
            $user->id,
            $user->name,
            $user->email,
            str_replace(["\r", "\n", ","], [" ", " ", ";"], $request->message) // basic sanitization for CSV
        ];

        $csvContent = implode(',', $csvLine) . "\n";
        
        // Append to storage/app/feedback.csv
        $fileName = 'feedback.csv';
        if (!Storage::exists($fileName)) {
            Storage::put($fileName, "Date,User ID,User Name,User Email,Message\n");
        }
        Storage::append($fileName, $csvContent);

        return redirect()->route('dashboard')->with('success', 'Thank you for your feedback! It has been sent to the admins.');
    }
}
