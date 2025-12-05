@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-person-circle me-2"></i>Your Profile</h1>
        <p>View and update your personal information.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}"
                             alt="Avatar" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white"
                             style="width: 120px; height: 120px; font-size: 2.5rem;">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                </div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->email }}</p>
                <span class="badge bg-{{ ($user->status ?? 'pending') === 'approved' ? 'success' : 'warning' }}">
                    {{ ucfirst($user->status ?? 'pending') }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>Edit Profile
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-modern">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-modern" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-modern" value="{{ old('phone', $user->phone) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Profile Picture</label>
                        <input type="file" name="avatar" class="form-control form-control-modern" accept="image/*">
                        <small class="text-muted">Upload an image from your device.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern">Card Number</label>
                        @if($primaryAccount && $primaryAccount->status === 'verified')
                            @php
                                $cardNumber = $primaryAccount->account_number ?? '';
                                $digits = preg_replace('/\D/', '', $cardNumber);
                                $masked = strlen($digits) >= 4 
                                    ? substr($digits, 0, 4) . ' **** **** ' . substr($digits, -4)
                                    : $cardNumber;
                            @endphp
                            <div class="input-group mb-2">
                                <input type="text" class="form-control form-control-modern" value="{{ $cardNumber }}" id="cardNumberInput" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="copyCardNumber('{{ $cardNumber }}')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Masked: <code>{{ $masked }}</code></small>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Verified card from {{ $primaryAccount->bank_name }} ({{ $primaryAccount->currency_code }})
                                @if($primaryAccount->verified_at)
                                    <br>Verified on: {{ $primaryAccount->verified_at->format('M d, Y H:i') }}
                                @endif
                            </small>
                        @else
                            <input type="text" class="form-control form-control-modern" value="No verified card yet" disabled>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                @if($primaryAccount && $primaryAccount->status === 'pending')
                                    Your card is pending admin approval. Once approved, it will appear here.
                                @else
                                    Add and verify a bank account to see your card number here.
                                    <a href="{{ route('app.bank-accounts.index') }}" class="text-decoration-none">Manage Bank Accounts</a>
                                @endif
                            </small>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary-modern btn-modern">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyCardNumber(cardNumber) {
    navigator.clipboard.writeText(cardNumber).then(function() {
        // Show a temporary success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
@endsection
