<?php
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentHourController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\UserBankAccountController;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuthController;

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('social.callback');

// ========================================================================
// 1. PUBLIC ROUTES (No Login Required)
// ========================================================================

// Home page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// --- Agent Views ---
// Agent map/list view (shows all approved agents)
Route::get('/agents', function (Request $request) {
    $query = App\Models\Agent::with(['user', 'hours'])
        ->where('status', 'approved');
    
    $agents = $query->get();
    
    return view('agents.map', compact('agents'));
})->name('agents.map');

// Agent registration form view
Route::get('/partner/register', function () {
    return view('agents.create');
})->name('agents.register');

// Agent public profile view
Route::get('/agents/{id}', function ($id) {
    $agent = App\Models\Agent::with(['user', 'hours'])->findOrFail($id);
    return view('agents.show', compact('agent'));
})->name('agents.public_profile');

// Agent registration form submission
Route::post('/partner/register', [AgentController::class, 'store'])->name('agents.store');

// --- Login Routes (for web views) ---
Route::get('/login', function (Request $request) {
    // Ensure session is started to generate CSRF token
    $request->session()->regenerateToken();
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// --- Register Routes (for web views) ---
Route::get('/register', function () {
    if (!view()->exists('auth.register')) {
        abort(500, 'Register view not found');
    }
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

// ========================================================================
// 2. ADMIN ROUTES (Protected)
// ========================================================================
// Ensure you have a middleware (like 'role:admin') to protect these.

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // --- Dashboard ---
    Route::get('/dashboard', [StatisticController::class, 'dashboard'])->name('dashboard');

    // --- Manage System Admins ---
    Route::resource('admins', AdminController::class);

    // --- Reports ---
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::resource('reports', ReportController::class)->only(['index', 'create', 'store', 'destroy']);

    // --- Audit Logs ---
    Route::delete('/auditTable/prune', [AuditLogController::class, 'prune'])->name('auditTable.prune');
    Route::get('/auditTable', [AuditLogController::class, 'index'])->name('auditTable');
    Route::get('/auditTable/{id}', [AuditLogController::class, 'show'])->name('auditTable.show');

    // --- Manage Agents (Approvals & Oversight) ---
    // Admin agents list view - Load from database
    Route::get('/agents', function (Request $request) {
        $query = App\Models\Agent::with('user');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $agents = $query->paginate(10);
        
        return view('admin.agents.index', compact('agents'));
    })->name('agents.index');
    
    Route::patch('/agents/{agent}/status', [AgentController::class, 'updateStatus'])->name('agents.update_status'); // Approve/Suspend
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy'); // Delete Agent

    // --- Statistics ---
    Route::get('/statistics', [StatisticController::class, 'statistic'])->name('statistics');
    Route::get('/statistics/search', [StatisticController::class, 'searchDate'])->name('searchDate');
});


// ========================================================================
// 3. AGENT PORTAL ROUTES (Protected)
// ========================================================================
// Routes for the Agent to manage their own store.

Route::middleware(['auth', 'role:agent'])->prefix('portal')->name('portal.')->group(function () {
    
    // --- My Store Details ---
    // Agent edit store details view
    Route::get('/my-store/{agent}/edit', function (App\Models\Agent $agent) {
        $agent->load('user');
        return view('portal.agents.edit', compact('agent'));
    })->name('agents.edit');
    
    // Agent update store details
    Route::put('/my-store/{agent}', [AgentController::class, 'update'])->name('agents.update');

    // --- Working Hours ---
    // Agent hours index view
    Route::get('/my-store/{agent}/hours', function (App\Models\Agent $agent) {
        $hours = $agent->hours()->orderBy('day_of_week')->get();
        $days = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
        ];
        return view('agents.hours.index', compact('agent', 'hours', 'days'));
    })->name('hours.index');
    
    // Agent hours edit view
    Route::get('/my-store/{agent}/hours/edit', function (App\Models\Agent $agent) {
        $hours = $agent->hours->keyBy('day_of_week');
        $days = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
        ];
        return view('agents.hours.edit', compact('agent', 'hours', 'days'));
    })->name('hours.edit');
    
    // Agent hours update
    Route::put('/my-store/{agent}/hours', [AgentHourController::class, 'update'])->name('hours.update');

    // --- Commissions ---
    // Agent commissions view
    Route::get('/my-store/{agent}/commissions', function (App\Models\Agent $agent, Request $request) {
        // Security: Ensure the logged-in user owns this agent profile
        if (Auth::id() !== $agent->user_id) {
            abort(403, 'Unauthorized access to commission records.');
        }

        $query = $agent->transactions();

        // Filter by date range if provided
        if ($request->has('from')) {
            $query->whereDate('processed_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('processed_at', '<=', $request->to);
        }

        $transactions = $query->with('transfer')
            ->latest('processed_at')
            ->paginate(20);

        // Calculate totals
        $totalCommission = $agent->transactions()->sum('commission');
        $monthlyCommission = $agent->transactions()
            ->whereYear('processed_at', now()->year)
            ->whereMonth('processed_at', now()->month)
            ->sum('commission');
        $todayCommission = $agent->transactions()
            ->whereDate('processed_at', today())
            ->sum('commission');

        // Filtered totals
        $filteredCommission = $query->sum('commission');

        return view('portal.commissions', compact(
            'agent',
            'transactions',
            'totalCommission',
            'monthlyCommission',
            'todayCommission',
            'filteredCommission'
        ));
    })->name('commissions');
});


// ========================================================================
// 4. AUTHENTICATED USER ROUTES (Shared)
// ========================================================================

Route::middleware(['auth'])->group(function () {
    // Dashboard - Load data from database
    Route::get('/dashboard', function (Request $request) {
        $user = Auth::user();
        
        // Get transfers from database
        $transfers = App\Models\Transfer::where('sender_id', $user->id)
            ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment'])
            ->orderBy('initiated_at', 'desc')
            ->limit(5)
            ->get();
        
        $totalTransfers = App\Models\Transfer::where('sender_id', $user->id)->count();
        $lastTransferStatus = $transfers->first() ? $transfers->first()->status : 'N/A';
        
        // Get unread notifications count from database using Laravel's Notifiable trait
        $unreadCount = $user->unreadNotifications()->count();
        
        return view('dashboard', compact('transfers', 'totalTransfers', 'lastTransferStatus', 'unreadCount'));
    })->name('dashboard');
    
    // App routes with 'app.' prefix for views
    Route::prefix('app')->name('app.')->group(function () {
        // --- Transfers ---
        // Transfers list view - Load from database
        Route::get('/transfers', function (Request $request) {
            $user = Auth::user();
            
            $query = App\Models\Transfer::where('sender_id', $user->id)
                ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            $transfers = $query->orderBy('initiated_at', 'desc')->paginate(15);
            
            return view('transfers.index', compact('transfers'));
        })->name('transfers.index');
        
        // Create transfer view
        Route::get('/transfers/create', function () {
            // Load beneficiaries and currencies for the form
            $user = Auth::user();
            $beneficiaries = App\Models\Beneficiary::where('user_id', $user->id)
                ->with(['country', 'method'])
                ->get();
            $currencies = App\Models\Currency::all();
            
            return view('transfers.create', compact('beneficiaries', 'currencies'));
        })->name('transfers.create');
        
        // --- Beneficiaries ---
        // Beneficiaries list view - Load from database
        Route::get('/beneficiaries', function () {
            $user = Auth::user();
            $beneficiaries = App\Models\Beneficiary::where('user_id', $user->id)
                ->with(['country', 'method'])
                ->get();
            
            return view('beneficiaries.index', compact('beneficiaries'));
        })->name('beneficiaries.index');
        
        // --- Bank Accounts ---
        // Bank accounts list view - Load from database
        Route::get('/bank-accounts', function () {
            $user = Auth::user();
            $bankAccounts = App\Models\UserBankAccount::where('user_id', $user->id)->get();
            
            return view('bank_accounts.index', compact('bankAccounts'));
        })->name('bank-accounts.index');
        
        // --- KYC ---
        // KYC view - Load from database
        Route::get('/kyc', function () {
            $user = Auth::user();
            $verification = App\Models\UserVerification::where('user_id', $user->id)->first();
            
            return view('kyc.show', compact('verification'));
        })->name('kyc.show');
        
        // --- Notifications ---
        // Notifications list view - Load from database
        Route::get('/notifications', function () {
            $user = Auth::user();
            
            $notifications = $user->notifications()->orderBy('created_at', 'desc')->paginate(20);
            $unreadCount = $user->unreadNotifications()->count();
            
            return view('notifications.index', compact('notifications', 'unreadCount'));
        })->name('notifications.index');
    });
});


// ========================================================================
// 6. FORM SUBMISSIONS & ACTIONS (POST/PUT/DELETE)
// ========================================================================

Route::middleware(['auth'])->group(function () {
    // Auth logout
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    
    // Transfer actions
    Route::get('/transfers/summary', [TransferController::class, 'summary'])->name('transfers.summary');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', function (App\Models\Transfer $transfer) {
        $transfer->load(['beneficiary.country', 'beneficiary.method', 'events', 'payment']);
        return view('transfers.show', compact('transfer'));
    })->name('transfers.show');
    
    // Beneficiary actions
    Route::post('/beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
    Route::delete('/beneficiaries/{id}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');
    
    // Bank Account actions
    Route::post('/bank-accounts', [UserBankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::delete('/bank-accounts/{id}', [UserBankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
    
    // Notification actions
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    
    // Transfer cancel action
    Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
});
