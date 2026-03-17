@extends('layouts.app')

@section('title', 'Buy Offers & Boosters')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-stars me-2"></i>Buy Offers & Boosters</h1>
        <p>Enhance your transfers with premium features and save money</p>
    </div>
</div>

@if(session('success'))
    <div class="container-fluid px-4 mb-4">
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="container-fluid px-4 mb-4">
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-9">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-gift me-2"></i>Available Offers
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @foreach($availableOffers as $offer)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-{{ $offer['color'] ?? 'primary' }}" style="border-width: 2px;">
                                <div class="card-body d-flex flex-column">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-{{ $offer['icon'] ?? 'star' }}" style="font-size: 3rem; color: var(--bs-{{ $offer['color'] ?? 'primary' }});"></i>
                                    </div>
                                    <h5 class="card-title text-center mb-2">{{ $offer['name'] }}</h5>
                                    <p class="card-text text-muted small text-center mb-3">{{ $offer['description'] }}</p>
                                    <div class="text-center mb-3">
                                        <span class="badge bg-{{ $offer['color'] ?? 'primary' }} badge-modern" style="font-size: 1rem;">
                                            <i class="bi bi-check-circle me-1"></i>{{ $offer['savings'] }}
                                        </span>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="text-center mb-3">
                                            <span class="h4 mb-0">${{ number_format($offer['price'], 2) }}</span>
                                        </div>
                                        <form action="{{ route('offers.purchase') }}" method="POST" onsubmit="return confirm('Purchase {{ $offer['name'] }} for ${{ number_format($offer['price'], 2) }}?')">
                                            @csrf
                                            <input type="hidden" name="offer_name" value="{{ $offer['name'] }}">
                                            <input type="hidden" name="price" value="{{ $offer['price'] }}">
                                            <button type="submit" class="btn btn-{{ $offer['color'] ?? 'primary' }}-modern btn-modern w-100" 
                                                    {{ ($user->balance ?? 0) < $offer['price'] ? 'disabled' : '' }}>
                                                <i class="bi bi-cart-plus me-2"></i>
                                                {{ ($user->balance ?? 0) >= $offer['price'] ? 'Purchase Now' : 'Insufficient Balance' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 layout-stack">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-wallet2 me-2"></i>Your Wallet
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="h3 mb-0">${{ number_format($user->balance ?? 0, 2) }}</div>
                    <small class="text-muted">{{ $user->balance_currency ?? 'USD' }}</small>
                </div>
                <hr>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-modern btn-modern w-100">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        @if($purchasedOffers->count() > 0)
            <div class="card-modern">
                <div class="card-header">
                    <i class="bi bi-check-circle me-2"></i>Your Active Offers
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($purchasedOffers as $purchased)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $purchased->offer_name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Purchased: {{ \Carbon\Carbon::parse($purchased->purchased_at)->format('M d, Y') }}
                                        </small>
                                        @if($purchased->expires_at)
                                            <br>
                                            <small class="text-warning">
                                                Expires: {{ \Carbon\Carbon::parse($purchased->expires_at)->format('M d, Y') }}
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
