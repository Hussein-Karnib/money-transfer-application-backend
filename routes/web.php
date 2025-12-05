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
use App\Http\Controllers\TransferSearchController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\UserBankAccountController;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AgentTransactionController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Storage;

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('social.callback');

// Google OAuth redirect
Route::get('/auth/google/redirect', function () {
    return app(SocialAuthController::class)->redirect('google');
})->name('google.redirect');

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

// All-agents Leaflet map with search
Route::get('/agents/map-all', [AgentController::class, 'mapAll'])->name('agents.map_all');

// Dedicated internal Leaflet map for a single agent (future-friendly for collections)
Route::get('/agents/{agent}/map', [AgentController::class, 'showMap'])->name('agents.map.single');

// Agent registration form view
Route::get('/partner/register', function (Request $request) {
    // Ensure session is started to generate CSRF token
    $request->session()->regenerateToken();
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
    Route::get('/approvals', [AdminController::class, 'approvals'])->name('approvals');
    Route::patch('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve');

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
    
    // --- Bank Account Verification ---
    Route::get('/bank-accounts', function (Request $request) {
        $query = App\Models\UserBankAccount::with(['user', 'currency']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to pending accounts
            $query->where('status', 'pending');
        }
        
        $bankAccounts = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('admin.bank_accounts.index', compact('bankAccounts'));
    })->name('bank-accounts.index');
    
    Route::post('/bank-accounts/{id}/verify', [App\Http\Controllers\UserBankAccountController::class, 'verify'])->name('bank-accounts.verify');
});


// ========================================================================
// 3. AGENT PORTAL ROUTES (Protected)
// ========================================================================
// Routes for the Agent to manage their own store.

Route::middleware(['auth', 'role:agent'])->prefix('portal')->name('portal.')->group(function () {
    
    // Helper to get current agent
    $getAgent = function() {
        $user = Auth::user();
        $agent = App\Models\Agent::where('user_id', $user->id)->firstOrFail();
        return $agent;
    };
    
    // --- My Store Details ---
    // Agent edit store details view
    Route::get('/my-store/edit', function () use ($getAgent) {
        $agent = $getAgent();
        $agent->load('user');
        return view('portal.agents.edit', compact('agent'));
    })->name('agents.edit');
    
    // Agent update store details
    Route::put('/my-store', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        return app(AgentController::class)->update($request, $agent);
    })->name('agents.update');

    // --- Working Hours ---
    // Agent hours index view
    Route::get('/my-store/hours', function () use ($getAgent) {
        $agent = $getAgent();
        $hours = $agent->hours()->orderBy('day_of_week')->get();
        $days = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
        ];
        return view('agents.hours.index', compact('agent', 'hours', 'days'));
    })->name('hours.index');
    
    // Agent hours edit view
    Route::get('/my-store/hours/edit', function () use ($getAgent) {
        $agent = $getAgent();
        $hours = $agent->hours->keyBy('day_of_week');
        $days = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
        ];
        return view('agents.hours.edit', compact('agent', 'hours', 'days'));
    })->name('hours.edit');
    
    // Agent hours update
    Route::put('/my-store/hours', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        return app(AgentHourController::class)->update($request, $agent);
    })->name('hours.update');

    // --- Agent Dashboard ---
    Route::get('/dashboard', function (Request $request) {
        $user = Auth::user();
        $agent = App\Models\Agent::where('user_id', $user->id)->firstOrFail();
        
        // Get pending transfers for cash-in (queued, paid)
        $pendingCashIn = App\Models\Transfer::whereIn('status', ['queued', 'paid'])
            ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
            ->orderBy('initiated_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get pending transfers for cash-out (available_for_pickup)
        $pendingCashOut = App\Models\Transfer::where('status', 'available_for_pickup')
            ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
            ->orderBy('initiated_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get recent transactions
        $recentTransactions = $agent->transactions()
            ->with('transfer')
            ->latest('processed_at')
            ->limit(5)
            ->get();
        
        // Get notifications
        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get();
        $unreadCount = $user->unreadNotifications()->count();
        
        // Stats
        $todayCommission = $agent->transactions()
            ->whereDate('processed_at', today())
            ->sum('commission');
        $monthlyCommission = $agent->transactions()
            ->whereYear('processed_at', now()->year)
            ->whereMonth('processed_at', now()->month)
            ->sum('commission');
        $totalTransactions = $agent->transactions()->count();
        
        return view('portal.dashboard', compact(
            'agent',
            'pendingCashIn',
            'pendingCashOut',
            'recentTransactions',
            'notifications',
            'unreadCount',
            'todayCommission',
            'monthlyCommission',
            'totalTransactions'
        ));
    })->name('dashboard');
    
    // --- Commissions ---
    // Agent commissions view
    Route::get('/my-store/commissions', function (Request $request) use ($getAgent) {
        $agent = $getAgent();

        $query = $agent->transactions();

        // Filter by date range if provided
        if ($request->filled('from')) {
            $query->whereDate('processed_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
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
    
    // --- Transfer Requests ---
    // View pending transfers for processing
    Route::get('/transfers/pending', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        
        $type = $request->get('type', 'cash_in'); // cash_in or cash_out
        
        if ($type === 'cash_in') {
            $transfers = App\Models\Transfer::whereIn('status', ['queued', 'paid'])
                ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
                ->orderBy('initiated_at', 'desc')
                ->paginate(15);
        } else {
            $transfers = App\Models\Transfer::where('status', 'available_for_pickup')
                ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
                ->orderBy('initiated_at', 'desc')
                ->paginate(15);
        }
        
        return view('portal.transfers.pending', compact('agent', 'transfers', 'type'));
    })->name('transfers.pending');
    
    // Process transfer (cash-in or cash-out)
    Route::get('/transfers/process', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        return app(AgentTransactionController::class)->create($agent, $request);
    })->name('transfers.process');
    
    Route::post('/transfers/process', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        return app(AgentTransactionController::class)->store($request, $agent);
    })->name('transfers.process.store');
    
    // Transaction history
    Route::get('/transactions', function () use ($getAgent) {
        $agent = $getAgent();
        return app(AgentTransactionController::class)->index($agent);
    })->name('transactions.index');
    
    // Create transaction (process transfer)
    Route::get('/transactions/create', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        // Pass agent as first parameter to match controller signature
        return app(AgentTransactionController::class)->create($agent, $request);
    })->name('transactions.create');
    
    // Store transaction (process transfer)
    Route::post('/transactions', function (Request $request) use ($getAgent) {
        $agent = $getAgent();
        return app(AgentTransactionController::class)->store($request, $agent);
    })->name('transactions.store');
    
    Route::get('/transactions/{transaction}', function (App\Models\Agent_Transaction $transaction) use ($getAgent) {
        $agent = $getAgent();
        return app(AgentTransactionController::class)->show($agent, $transaction);
    })->name('transactions.show');
});


// ========================================================================
// 4. AUTHENTICATED USER ROUTES (Shared)
// ========================================================================

Route::middleware(['auth'])->group(function () {
    // Dashboard - Load data from database (redirect based on role)
    Route::get('/dashboard', function (Request $request) {
        $user = Auth::user();
        $user->load('role');
        
        // Redirect admins to admin dashboard
        if ($user->role && strtolower($user->role->name) === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        // Redirect agents to portal dashboard
        if ($user->role && strtolower($user->role->name) === 'agent') {
            $agent = App\Models\Agent::where('user_id', $user->id)->first();
            if ($agent) {
                return redirect()->route('portal.dashboard');
            }
        }
        
        // Regular user dashboard (customer/user)
        // Get transfers from database
        $transfers = App\Models\Transfer::where('sender_id', $user->id)
            ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment'])
            ->orderBy('initiated_at', 'desc')
            ->limit(5)
            ->get();
        
        $totalTransfers = App\Models\Transfer::where('sender_id', $user->id)->count();
        $lastTransferStatus = $transfers->first() ? $transfers->first()->status : 'N/A';
        $accountBalance = $user->balance ?? 0;
        $balanceCurrency = $user->balance_currency ?? ($transfers->first()?->currency_from ?? 'USD');
        $accountStatus = $user->status ?? 'pending';
        $accountName = $user->name ?? 'User';
        
        // Get bank accounts for cash in/out
        $bankAccounts = App\Models\UserBankAccount::where('user_id', $user->id)
            ->where('status', 'verified')
            ->with('currency')
            ->get();
        
        // Get unread notifications count from database using Laravel's Notifiable trait
        $unreadCount = $user->unreadNotifications()->count();
        
        return view('dashboard', compact('transfers', 'totalTransfers', 'lastTransferStatus', 'unreadCount', 'accountBalance', 'balanceCurrency', 'accountStatus', 'accountName', 'bankAccounts'));
    })->name('dashboard');
    
    // App routes with 'app.' prefix for views
    Route::prefix('app')->name('app.')->group(function () {
        // --- Transfers ---
        // Transfers list view - Load from database
        Route::get('/transfers', function (Request $request) {
            $user = Auth::user();
            
            $query = App\Models\Transfer::where('sender_id', $user->id)
                ->with(['beneficiary.country', 'beneficiary.method', 'events', 'payment']);
            
            if ($request->has('status') && $request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            $transfers = $query->orderBy('initiated_at', 'desc')->paginate(15);
            
            return view('transfers.index', compact('transfers'));
        })->name('transfers.index');
        
        // Create transfer view
        Route::get('/transfers/create', function (Request $request) {
            // Load beneficiaries and currencies for the form from database
            $user = Auth::user();
            $beneficiaries = App\Models\Beneficiary::where('user_id', $user->id)
                ->with(['country', 'method'])
                ->orderBy('full_name')
                ->get();
            $currencies = App\Models\Currency::orderBy('code')->get();
            $methods = App\Models\Transfer_Method::all();
            $prefill = [
                'amount' => $request->input('amount'),
                'currency_from' => $request->input('currency_from'),
                'currency_to' => $request->input('currency_to'),
                'speed' => $request->input('speed'),
                'transfer_method_id' => $request->input('transfer_method_id'),
                'selected_offers' => $request->input('selected_offers', []),
            ];
            
            return view('transfers.create', compact('beneficiaries', 'currencies', 'methods', 'prefill'));
        })->name('transfers.create');
        
        // Search transfer services
        Route::get('/transfers/search', [TransferSearchController::class, 'index'])->name('transfers.search');
        
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
    // Auth logout (both POST and GET for flexibility)
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout.get');
    Route::get('/profile', function (Request $request) {
        $user = Auth::user();
        // Get the first verified bank account (card)
        $primaryAccount = $user->bankAccounts()
            ->where('status', 'verified')
            ->orderBy('verified_at', 'desc')
            ->first();
        return view('profile', compact('user', 'primaryAccount'));
    })->name('profile.show');
    Route::post('/profile', function (Request $request) {
        $user = Auth::user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $user->phone,
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $payload['avatar_url'] = Storage::url($path);
        }

        $user->update($payload);
        return redirect()->route('profile.show')->with('success', 'Profile updated.');
    })->name('profile.update');
    
    // Transfer actions
    Route::get('/transfers/summary', [TransferController::class, 'summary'])->name('transfers.summary');
    Route::post('/transfers/search', [TransferSearchController::class, 'search'])->name('transfers.search.post');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', function (App\Models\Transfer $transfer) {
        $transfer->load(['beneficiary.country', 'beneficiary.method', 'events', 'payment', 'transferMethod']);
        return view('transfers.show', compact('transfer'));
    })->name('transfers.show');
    
    // Beneficiary actions
    Route::post('/beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
    Route::delete('/beneficiaries/{id}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');
    
    // Bank Account actions
    Route::post('/bank-accounts', [UserBankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::delete('/bank-accounts/{id}', [UserBankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
    
    // Wallet actions (Cash In/Cash Out)
    Route::post('/wallet/cash-in', [App\Http\Controllers\WalletController::class, 'cashIn'])->name('wallet.cash-in');
    Route::post('/wallet/cash-out', [App\Http\Controllers\WalletController::class, 'cashOut'])->name('wallet.cash-out');
    
    // Notification actions
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    
    // Transfer cancel action
    Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
});



//google auth routes
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');
