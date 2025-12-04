    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\AgentHourApiController;
    use App\Http\Controllers\Api\AgentTransactionApiController;
    use App\Http\Controllers\CurrencyController;
    use App\Http\Controllers\ExchangeRateController;
    use App\Http\Controllers\UserBankAccountController;
    use App\Http\Controllers\BeneficiaryController;
    use App\Http\Controllers\TransferController;
    use App\Http\Controllers\PaymentController;
    use App\Http\Controllers\TransferEventController;
    use App\Http\Controllers\TransferFeeController;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\UserVerificationController;
    use App\Http\Controllers\UserController;
    use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;

    /*
    |--------------------------------------------------------------------------
    | WEB ROUTES
    |--------------------------------------------------------------------------
    | These are for your Laravel web app (Blade views, session auth).
    | Middleware group: "web" (sessions, cookies, CSRF).
    | No automatic /api prefix here.
    */

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES
    |--------------------------------------------------------------------------
    */

    // Home page placeholder – later we’ll make it a dashboard / landing page
    Route::get('/', function () {
        return view('welcome'); // or a custom home view
    })->name('home');

    // Currencies
    Route::prefix('currencies')->group(function () {
        Route::get('/',      [CurrencyController::class, 'index'])->name('currencies.index');
        Route::get('/{code}',[CurrencyController::class, 'show'])->name('currencies.show');
    });

    // Exchange Rates
    Route::prefix('exchange-rates')->group(function () {
        Route::get('/',           [ExchangeRateController::class, 'index'])->name('rates.index');
        Route::get('/convert',    [ExchangeRateController::class, 'convert'])->name('rates.convert');
        Route::get('/{from}/{to}',[ExchangeRateController::class, 'show'])->name('rates.show');
    });

    // Auth (for now still JSON-style login/register)
    // Later we can replace this with proper web auth (Breeze/Fortify/etc.)
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login',    [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/social',   [AuthController::class, 'socialLogin'])->name('auth.social');

    /*
    |--------------------------------------------------------------------------
    | AGENT PUBLIC / PROTECTED ROUTES
    |--------------------------------------------------------------------------
    */

    // Public agent hours (no login required)
    Route::get('/agents/{agent}/hours', [AgentHourApiController::class, 'show'])
        ->name('agents.hours.show');

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED USER ROUTES (WEB GUARD)
    |--------------------------------------------------------------------------
    | Now we switch from auth:sanctum to auth (session-based).
    | These are the routes your web UI will use after login.
    */

    Route::middleware(['auth'])->group(function () {

        // ---- Notifications (for web "bell" icon & notifications page) ----
        Route::prefix('notifications')->group(function () {
            Route::get('/',           [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/unread',     [NotificationController::class, 'unread'])->name('notifications.unread');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('/read-all',  [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
        });

        // ---- Auth / Profile ----
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/me',  [UserController::class, 'me'])->name('profile.show');
        Route::put('/me',  [UserController::class, 'update'])->name('profile.update');

        /*
        |--------------------------------------------------------------------------
        | KYC ROUTES
        |--------------------------------------------------------------------------
        */

        // User KYC submit + view (any logged-in user)
        Route::post('/kyc', [UserVerificationController::class, 'store'])->name('kyc.store');
        Route::get('/kyc',  [UserVerificationController::class, 'show'])->name('kyc.show');

        // KYC admin (only Admin role by NAME)
        Route::middleware('role:Admin')->prefix('kyc')->group(function () {
            Route::get('/pending',       [UserVerificationController::class, 'pending'])->name('kyc.pending');
            Route::post('/{id}/approve', [UserVerificationController::class, 'approve'])->name('kyc.approve');
            Route::post('/{id}/reject',  [UserVerificationController::class, 'reject'])->name('kyc.reject');
        });

        /*
        |--------------------------------------------------------------------------
        | BANK ACCOUNTS (CUSTOMER) – KYC VERIFIED USERS
        |--------------------------------------------------------------------------
        */

        Route::middleware('kyc_verified')->prefix('bank-accounts')->group(function () {
            Route::get('/',        [UserBankAccountController::class, 'index'])->name('bank-accounts.index');
            Route::post('/',       [UserBankAccountController::class, 'store'])->name('bank-accounts.store');
            Route::get('/{id}',    [UserBankAccountController::class, 'show'])->name('bank-accounts.show');
            Route::put('/{id}',    [UserBankAccountController::class, 'update'])->name('bank-accounts.update');
            Route::delete('/{id}', [UserBankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
        });

        // Verification by Admin/Agent (no KYC needed on THEIR account)
        Route::middleware('role:Admin')->post(
            '/bank-accounts/{id}/verify',
            [UserBankAccountController::class, 'verify']
        )->name('bank-accounts.verify');

        /*
        |--------------------------------------------------------------------------
        | BENEFICIARIES
        |--------------------------------------------------------------------------
        */

        Route::prefix('beneficiaries')->group(function () {
            Route::get('/',        [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
            Route::post('/',       [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
            Route::get('/{id}',    [BeneficiaryController::class, 'show'])->name('beneficiaries.show');
            Route::put('/{id}',    [BeneficiaryController::class, 'update'])->name('beneficiaries.update');
            Route::delete('/{id}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | TRANSFERS
        |--------------------------------------------------------------------------
        */

        Route::prefix('transfers')->group(function () {
            Route::get('/',             [TransferController::class, 'index'])->name('transfers.index');
            Route::get('/summary',      [TransferController::class, 'summary'])->name('transfers.summary'); // preview
            Route::post('/',            [TransferController::class, 'store'])->name('transfers.store');
            Route::get('/{id}',         [TransferController::class, 'show'])->name('transfers.show');
            Route::get('/{id}/track',   [TransferController::class, 'track'])->name('transfers.track');
            Route::post('/{id}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
            Route::post('/{id}/refund', [TransferController::class, 'refund'])->name('transfers.refund');
            Route::get('/{id}/events',  [TransferEventController::class, 'index'])->name('transfers.events');
        });

        /*
        |--------------------------------------------------------------------------
        | PAYMENTS
        |--------------------------------------------------------------------------
        */

        Route::prefix('payments')->group(function () {
            Route::post('/',             [PaymentController::class, 'store'])->name('payments.store');
            Route::get('/{id}',          [PaymentController::class, 'show'])->name('payments.show');
            Route::post('/{id}/capture', [PaymentController::class, 'capture'])->name('payments.capture');
            Route::post('/{id}/refund',  [PaymentController::class, 'refund'])->name('payments.refund');
        });

        /*
        |--------------------------------------------------------------------------
        | TRANSFER FEES
        |--------------------------------------------------------------------------
        */

        Route::prefix('transfer-fees')->group(function () {
            Route::get('/',            [TransferFeeController::class, 'index'])->name('transfer-fees.index');
            Route::post('/calculate',  [TransferFeeController::class, 'calculate'])->name('transfer-fees.calculate');
            Route::get('/{id}',        [TransferFeeController::class, 'show'])->name('transfer-fees.show');

            // Admin-only rules
            Route::middleware('role:Admin')->group(function () {
                Route::post('/',       [TransferFeeController::class, 'store'])->name('transfer-fees.store');
                Route::put('/{id}',    [TransferFeeController::class, 'update'])->name('transfer-fees.update');
                Route::delete('/{id}', [TransferFeeController::class, 'destroy'])->name('transfer-fees.destroy');
            });
        });

        // Reports
        Route::prefix('admin/reports')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('admin.reports');
            Route::post('/', [ReportController::class, 'store'])->name('admin.reports.store');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('admin.reports.download');
            Route::delete('/{report}', [ReportController::class, 'destroy'])->name('admin.reports.destroy');
        });

        // ================== WEB PAGES (Blade) ==================

        // Main dashboard
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        // Transfers pages
        Route::prefix('app/transfers')->group(function () {
            Route::get('/', function () {
                return view('transfers.index');
            })->name('app.transfers.index');

            Route::get('/create', function () {
                return view('transfers.create');
            })->name('app.transfers.create');
        });

        // Beneficiaries page
        Route::get('/app/beneficiaries', function () {
            return view('beneficiaries.index');
        })->name('app.beneficiaries.index');

        // Bank accounts page
        Route::get('/app/bank-accounts', function () {
            return view('bank_accounts.index');
        })->name('app.bank-accounts.index');

        // KYC page
        Route::get('/app/kyc', function () {
            return view('kyc.show');
        })->name('app.kyc.show');

        // Notifications page
        Route::get('/app/notifications', function () {
            return view('notifications.index');
        })->name('app.notifications.index');

    });
