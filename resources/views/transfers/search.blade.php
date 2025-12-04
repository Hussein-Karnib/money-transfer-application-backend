@extends('layouts.app')

@section('title', 'Search Transfer Services')

@section('content')
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-search me-2"></i>Search Transfer Services</h1>
        <p>Compare transfer options, fees, and rates to find the best deal</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-funnel me-2"></i>Search Filters
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('transfers.search.post') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-modern">Amount to Send</label>
                        <input type="number" step="0.01" class="form-control form-control-modern" 
                               name="amount" value="{{ old('amount', $amount ?? 1000) }}" required min="1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">From Country</label>
                        <select class="form-select form-control-modern" name="country_from_id" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" 
                                    {{ old('country_from_id', $countryFromId ?? $userCountryId) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">To Country</label>
                        <select class="form-select form-control-modern" name="country_to_id" required>
                            <option value="">Select Destination</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" 
                                    {{ old('country_to_id', $countryToId ?? '') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label-modern">Currency From</label>
                            <select class="form-select form-control-modern" name="currency_from" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" 
                                        {{ old('currency_from', $currencyFrom ?? 'USD') == $currency->code ? 'selected' : '' }}>
                                        {{ $currency->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label-modern">Currency To</label>
                            <select class="form-select form-control-modern" name="currency_to" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" 
                                        {{ old('currency_to', $currencyTo ?? 'LBP') == $currency->code ? 'selected' : '' }}>
                                        {{ $currency->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Transfer Speed</label>
                        <select class="form-select form-control-modern" name="speed">
                            <option value="standard" {{ old('speed', $speed ?? 'standard') == 'standard' ? 'selected' : '' }}>
                                Standard (24 hours)
                            </option>
                            <option value="express" {{ old('speed', $speed ?? '') == 'express' ? 'selected' : '' }}>
                                Express (1 hour)
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Payout Method (Optional)</label>
                        <select class="form-select form-control-modern" name="method_id">
                            <option value="">All Methods</option>
                            @foreach($methods as $method)
                                <option value="{{ $method->id }}" 
                                    {{ old('method_id', $methodId ?? '') == $method->id ? 'selected' : '' }}>
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                        <i class="bi bi-search me-2"></i>Search Transfer Options
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if(isset($exchangeRate))
            <div class="card-modern">
                <div class="card-header">
                    <i class="bi bi-list-check me-2"></i>Transfer Options
                </div>
                <div class="card-body">
                    <!-- Summary Card -->
                    <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <small class="opacity-75">You Send</small>
                                    <h4 class="mb-0">{{ number_format($amount, 2) }} {{ $currencyFrom }}</h4>
                                </div>
                                <div class="col-md-3">
                                    <small class="opacity-75">Fee</small>
                                    <h4 class="mb-0">{{ number_format($fee, 2) }} {{ $currencyFrom }}</h4>
                                </div>
                                <div class="col-md-3">
                                    <small class="opacity-75">Total Cost</small>
                                    <h4 class="mb-0">{{ number_format($totalAmount, 2) }} {{ $currencyFrom }}</h4>
                                </div>
                                <div class="col-md-3">
                                    <small class="opacity-75">They Receive</small>
                                    <h4 class="mb-0">{{ number_format($recipientAmount, 2) }} {{ $currencyTo }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Exchange Rate & Delivery Time -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="stat-card primary">
                                <div class="stat-label">Exchange Rate</div>
                                <div class="stat-value" style="font-size: 1.8rem;">
                                    1 {{ $currencyFrom }} = {{ number_format($exchangeRate, 4) }} {{ $currencyTo }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card success">
                                <div class="stat-label">Delivery Time</div>
                                <div class="stat-value" style="font-size: 1.8rem;">{{ $deliveryTime }}</div>
                                <small class="text-muted">Estimated: {{ $estimatedDelivery->format('M d, Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fee Details -->
                    @if($feeRule)
                        <div class="alert alert-info mb-4">
                            <h6><i class="bi bi-info-circle me-2"></i>Fee Breakdown</h6>
                            <p class="mb-1">Fixed Fee: {{ number_format($feeRule->fee_fixed ?? 0, 2) }} {{ $currencyFrom }}</p>
                            <p class="mb-0">Percentage: {{ number_format($feeRule->fee_percent ?? 0, 2) }}%</p>
                            <p class="mb-0 mt-2">
                                <small>
                                    <strong>Route:</strong> {{ $feeRule->countryFrom->name ?? 'N/A' }} → {{ $feeRule->countryTo->name ?? 'N/A' }}
                                </small>
                            </p>
                        </div>
                    @endif
                    
                    <!-- Promotions -->
                    @if($promotionsWithDiscount && $promotionsWithDiscount->count() > 0)
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-tag me-2"></i>Available Promotions</h5>
                            <div class="row g-3">
                                @foreach($promotionsWithDiscount as $promoData)
                                    @php
                                        $promo = $promoData['promotion'];
                                        $discount = $promoData['discount'];
                                    @endphp
                                    <div class="col-md-6">
                                        <div class="card" style="border-left: 4px solid #48bb78;">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <strong>{{ $promo->code }}</strong>
                                                        <p class="mb-0 text-muted small">{{ $promo->description }}</p>
                                                    </div>
                                                    <span class="badge bg-success">Save {{ number_format($discount, 2) }} {{ $currencyFrom }}</span>
                                                </div>
                                                <small class="text-muted">
                                                    @if($promo->discount_type === 'percentage')
                                                        {{ number_format($promo->discount_value, 2) }}% off
                                                    @else
                                                        Fixed discount: {{ number_format($promo->discount_value, 2) }} {{ $currencyFrom }}
                                                    @endif
                                                </small>
                                                @if($promo->max_discount)
                                                    <br><small class="text-muted">Max discount: {{ number_format($promo->max_discount, 2) }} {{ $currencyFrom }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Available Methods -->
                    @if($availableMethods && $availableMethods->count() > 0)
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-wallet2 me-2"></i>Available Payout Methods</h5>
                            <div class="row g-2">
                                @foreach($availableMethods as $method)
                                    <div class="col-md-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <i class="bi bi-bank" style="font-size: 2rem; color: #667eea;"></i>
                                                <p class="mb-0 mt-2"><strong>{{ $method->name }}</strong></p>
                                                @if($method->description)
                                                    <small class="text-muted">{{ $method->description }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 mt-4">
                        <a href="{{ route('app.transfers.create') }}" class="btn btn-primary-modern btn-modern">
                            <i class="bi bi-send me-2"></i>Create Transfer with These Options
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="card-modern">
                <div class="card-body text-center py-5">
                    <i class="bi bi-search" style="font-size: 4rem; color: #cbd5e0;"></i>
                    <h4 class="mt-3 mb-2">Search Transfer Services</h4>
                    <p class="text-muted">Fill in the filters and click "Search Transfer Options" to see available transfer services, fees, and rates.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
