<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentHourController;
use App\Http\Controllers\StatisticController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ========================================================================
// 1. PUBLIC ROUTES (No Login Required)
// ========================================================================

Route::get('/', function () {
    return view('welcome');
})->name('home');

// --- Agent Locator / Map ---
// Used by regular users to find stores without logging in
Route::get('/find-agents', [AgentController::class, 'index'])->name('agents.map');
Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.public_profile');

// --- Become a Partner (Registration) ---
Route::get('/partner/register', [AgentController::class, 'create'])->name('agents.register');
Route::post('/partner/register', [AgentController::class, 'store'])->name('agents.store');

// --- Authentication (Laravel Breeze/Jetstream) ---
// require __DIR__.'/auth.php'; 


// ========================================================================
// 2. ADMIN ROUTES (Protected)
// ========================================================================
// Ensure you have a middleware (like 'role:admin') to protect these.

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // --- Dashboard ---
    // --- Dashboard ---
    Route::get('/dashboard', [StatisticController::class, 'dashboard'])->name('dashboard');

    // --- Manage System Admins ---
    Route::resource('admins', AdminController::class);

    // --- Reports ---
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::resource('reports', ReportController::class)->only(['index', 'create', 'store', 'destroy']);

    // --- Audit Logs ---
    Route::delete('/audit-logs/prune', [AuditLogController::class, 'prune'])->name('audit_logs.prune');
    Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show']);

    // --- Manage Agents (Approvals & Oversight) ---
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index'); // List view
    Route::patch('/agents/{agent}/status', [AgentController::class, 'updateStatus'])->name('agents.update_status'); // Approve/Suspend
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy'); // Delete Agent

    // --- Statistics ---
    Route::get('/statistics', [StatisticController::class, 'statistic'])->name('statistics');
});


// ========================================================================
// 3. AGENT PORTAL ROUTES (Protected)
// ========================================================================
// Routes for the Agent to manage their own store.

Route::middleware(['auth', 'role:agent'])->prefix('portal')->name('portal.')->group(function () {
    
    // --- My Store Details ---
    // We pass {agent} here, but your controller should verify the logged-in user owns this agent ID
    Route::get('/my-store/{agent}/edit', [AgentController::class, 'edit'])->name('agents.edit');
    Route::put('/my-store/{agent}', [AgentController::class, 'update'])->name('agents.update');

    // --- Working Hours ---
    Route::get('/my-store/{agent}/hours', [AgentHourController::class, 'index'])->name('hours.index');
    Route::get('/my-store/{agent}/hours/edit', [AgentHourController::class, 'edit'])->name('hours.edit');
    Route::put('/my-store/{agent}/hours', [AgentHourController::class, 'update'])->name('hours.update');
});


// ========================================================================
// 4. AUTHENTICATED USER ROUTES (Shared)
// ========================================================================

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});