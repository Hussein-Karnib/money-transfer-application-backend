@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p>Welcome back, {{ auth()->user()->name }}! Here's your account overview.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-arrow-left-right" style="font-size: 2rem; color: #667eea;"></i>
            </div>
            <div class="stat-value">{{ $totalTransfers }}</div>
            <div class="stat-label">Total Transfers</div>
        </div>
    </div>

    <div class="col-md-4">
        @php
            $statusClassMap = [
                'completed' => 'success',
                'pending' => 'warning',
                'queued' => 'info',
                'paid' => 'info',
                'in_progress' => 'warning',
                'available_for_pickup' => 'info',
                'failed' => 'danger',
                'refunded' => 'danger',
            ];
            $lastClass = $statusClassMap[$lastTransferStatus] ?? 'secondary';
        @endphp
        <div class="stat-card {{ $lastClass }}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
            </div>
            <div class="stat-value" style="font-size: 1.5rem;">{{ ucfirst($lastTransferStatus) }}</div>
            <div class="stat-label">Last Transfer Status</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <i class="bi bi-bell" style="font-size: 2rem; color: #ed8936;"></i>
            </div>
            <div class="stat-value">{{ $unreadCount }}</div>
            <div class="stat-label">Unread Notifications</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Recent Transfers
            </div>
            <div class="card-body">
                @if($transfers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Beneficiary</th>
                                    <th>Amount</th>
                                    <th>From -> To</th>
                                    <th>Status</th>
                                    <th>Initiated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfers as $transfer)
                                    <tr>
                                        <td><strong>#{{ $transfer->id }}</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($transfer->beneficiary->full_name ?? 'N', 0, 1)) }}
                                                </div>
                                                {{ $transfer->beneficiary->full_name ?? '-' }}
                                            </div>
                                        </td>
                                        <td><strong>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</strong></td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $transfer->currency_from }}</span>
                                            <i class="bi bi-arrow-right mx-2"></i>
                                            <span class="badge bg-light text-dark">{{ $transfer->currency_to }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    'queued' => 'info',
                                                    'cancelled' => 'danger',
                                                    'failed' => 'danger'
                                                ];
                                                $color = $statusColors[$transfer->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }} badge-modern">
                                                {{ ucfirst($transfer->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $transfer->initiated_at ? \Carbon\Carbon::parse($transfer->initiated_at)->format('M d, Y H:i') : '-' }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e0;"></i>
                        <p class="text-muted mt-3">No transfers yet. Create your first transfer to get started!</p>
                        <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern mt-3">
                            <i class="bi bi-plus-circle me-2"></i>Create Transfer
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-plus-circle me-2"></i>New Transfer
                    </a>
                    <a href="{{ route('app.beneficiaries.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-people me-2"></i>Manage Beneficiaries
                    </a>
                    <a href="{{ route('app.bank-accounts.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-bank me-2"></i>Manage Bank Accounts
                    </a>
                    <a href="{{ route('app.notifications.index') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-bell me-2"></i>View Notifications
                        @if($unreadCount > 0)
                            <span class="badge bg-danger badge-modern ms-2">{{ $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('reviews.create') }}" class="btn btn-outline-modern btn-modern">
                        <i class="bi bi-chat-dots me-2"></i> Send Feedback
                    </a>
                </div>
            </div>
        </div>

        <div class="card-modern mt-4">
            <div class="card-header">
                <i class="bi bi-graph-up me-2"></i>Account Summary
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Full Name</span>
                        <strong>{{ $accountName ?? auth()->user()->name }}</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Available Balance</span>
                        <strong class="h5 mb-0">{{ number_format($accountBalance ?? 0, 2) }} {{ $balanceCurrency ?? 'USD' }}</strong>
                    </div>
                    <small class="text-muted">Updated from your profile wallet/linked accounts.</small>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Account Status</span>
                        @php
                            $status = strtolower($accountStatus ?? 'pending');
                            $statusClass = match ($status) {
                                'approved', 'active' => 'success',
                                'pending' => 'warning',
                                'rejected', 'suspended' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }} badge-modern">{{ ucfirst($status) }}</span>
                    </div>
                    @if(($accountStatus ?? 'pending') === 'pending')
                        <small class="text-muted">Pending admin approval after registration.</small>
                    @else
                        <small class="text-muted">Your first linked bank account is your company wallet; additional accounts are external and can move funds in both directions.</small>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Member Since</span>
                        <strong>{{ auth()->user()->created_at->format('M Y') }}</strong>
                    </div>
                </div>
                
                <!-- Cash In / Cash Out Section -->
                @if(($accountStatus ?? 'pending') !== 'pending' && isset($bankAccounts) && $bankAccounts->count() > 0)
                    <hr class="my-3">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-modern" data-bs-toggle="modal" data-bs-target="#cashInModal">
                            <i class="bi bi-arrow-down-circle me-2"></i>Cash In
                        </button>
                        <button type="button" class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#cashOutModal">
                            <i class="bi bi-arrow-up-circle me-2"></i>Cash Out
                        </button>
                    </div>
                @elseif(($accountStatus ?? 'pending') === 'pending')
                    <hr class="my-3">
                    <div class="alert alert-warning mb-0">
                        <small><i class="bi bi-info-circle me-1"></i>Please wait for account approval to use cash in/out features.</small>
                    </div>
                @else
                    <hr class="my-3">
                    <div class="alert alert-info mb-0">
                        <small><i class="bi bi-info-circle me-1"></i>Please add and verify a bank account to use cash in/out features.</small>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Cash In Modal -->
        <div class="modal fade" id="cashInModal" tabindex="-1" aria-labelledby="cashInModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashInModalLabel">
                            <i class="bi bi-arrow-down-circle me-2 text-success"></i>Cash In
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('wallet.cash-in') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="cashInBankAccount" class="form-label">Select Bank Account</label>
                                <select class="form-select" id="cashInBankAccount" name="bank_account_id" required>
                                    <option value="">Choose a bank account...</option>
                                    @foreach($bankAccounts ?? [] as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency_code }}">
                                            {{ $account->bank_name }} - {{ $account->currency_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cashInAmount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cashInAmount" name="amount" 
                                           step="0.01" min="0.01" placeholder="0.00" required>
                                    <span class="input-group-text" id="cashInCurrency">USD</span>
                                </div>
                                <small class="text-muted">Enter the amount to transfer from your bank account to your wallet.</small>
                            </div>
                            <input type="hidden" name="currency" id="cashInCurrencyHidden" value="USD">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Cash In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Cash Out Modal -->
        <div class="modal fade" id="cashOutModal" tabindex="-1" aria-labelledby="cashOutModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashOutModalLabel">
                            <i class="bi bi-arrow-up-circle me-2 text-primary"></i>Cash Out
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('wallet.cash-out') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="cashOutBankAccount" class="form-label">Select Bank Account</label>
                                <select class="form-select" id="cashOutBankAccount" name="bank_account_id" required>
                                    <option value="">Choose a bank account...</option>
                                    @foreach($bankAccounts ?? [] as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency_code }}">
                                            {{ $account->bank_name }} - {{ $account->currency_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cashOutAmount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cashOutAmount" name="amount" 
                                           step="0.01" min="0.01" max="{{ $accountBalance ?? 0 }}" 
                                           placeholder="0.00" required>
                                    <span class="input-group-text" id="cashOutCurrency">{{ $balanceCurrency ?? 'USD' }}</span>
                                </div>
                                <small class="text-muted">
                                    Available balance: <strong>{{ number_format($accountBalance ?? 0, 2) }} {{ $balanceCurrency ?? 'USD' }}</strong>
                                </small>
                            </div>
                            <input type="hidden" name="currency" id="cashOutCurrencyHidden" value="{{ $balanceCurrency ?? 'USD' }}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Cash Out</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            // Update currency when bank account is selected (Cash In)
            document.getElementById('cashInBankAccount')?.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                const currency = selected.dataset.currency || 'USD';
                document.getElementById('cashInCurrency').textContent = currency;
                document.getElementById('cashInCurrencyHidden').value = currency;
            });
            
            // Update currency when bank account is selected (Cash Out)
            document.getElementById('cashOutBankAccount')?.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                const currency = selected.dataset.currency || '{{ $balanceCurrency ?? 'USD' }}';
                document.getElementById('cashOutCurrency').textContent = currency;
                document.getElementById('cashOutCurrencyHidden').value = currency;
            });
        </script>
    </div>
</div>
@endsection
