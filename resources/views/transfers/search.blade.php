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
                            <option value="instant" {{ old('speed', $speed ?? '') == 'instant' ? 'selected' : '' }}>
                                Instant (minutes, highest fee)
                            </option>
                            <option value="express" {{ old('speed', $speed ?? '') == 'express' ? 'selected' : '' }}>
                                Express (2 hours)
                            </option>
                            <option value="same_day" {{ old('speed', $speed ?? '') == 'same_day' ? 'selected' : '' }}>
                                Same Day (delivered today)
                            </option>
                            <option value="standard" {{ old('speed', $speed ?? 'standard') == 'standard' ? 'selected' : '' }}>
                                Standard (next day, lowest fee)
                            </option>
                        </select>
                        <small class="text-muted">Faster speeds increase the transfer fee automatically.</small>
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
                                @isset($speedProfile)
                                    <div class="small text-muted mt-1">Speed profile: {{ $speedProfile['label'] }} ({{ $speedProfile['eta_text'] }})</div>
                                @endisset
                            </div>
                        </div>
                    </div>

                    @if(isset($transferOptions) && $transferOptions->count() > 0)
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-lightning-charge me-2"></i>Transfer services matching your filters</h5>
                            <div class="row g-3">
                                @foreach($transferOptions as $option)
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="badge bg-primary">{{ $option['speed_label'] }}</span>
                                                        <h6 class="mb-1">{{ $option['payout_label'] }}</h6>
                                                        <small class="text-muted">{{ $option['method']->description ?? 'Fast and secure' }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold">{{ number_format($option['total'], 2) }} {{ $currencyFrom }}</div>
                                                        <small class="text-muted">Total (incl. fees)</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <span class="badge bg-light text-dark">Fee: {{ number_format($option['fee'], 2) }} {{ $currencyFrom }}</span>
                                                    <span class="badge bg-success">Receives {{ number_format($option['recipient_amount'], 2) }} {{ $currencyTo }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
                                                    <span><i class="bi bi-clock me-1"></i>{{ $option['delivery_text'] }}</span>
                                                    <span><i class="bi bi-cash-stack me-1"></i>{{ $option['payout_label'] }}</span>
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    Rate: 1 {{ $currencyFrom }} = {{ number_format($option['exchange_rate'], 4) }} {{ $currencyTo }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Fee Details -->
                    @if($feeRule)
                        <div class="alert alert-info mb-4">
                            <h6><i class="bi bi-info-circle me-2"></i>Fee Breakdown</h6>
                            <p class="mb-1">Fixed Fee: {{ number_format($feeRule->fee_fixed ?? 0, 2) }} {{ $currencyFrom }}</p>
                            <p class="mb-0">Percentage: {{ number_format($feeRule->fee_percent ?? 0, 2) }}%</p>
                            <p class="mb-0 mt-2">
                                <small>
                                    <strong>Route:</strong> {{ $feeRule->countryFrom->name ?? 'N/A' }} -> {{ $feeRule->countryTo->name ?? 'N/A' }}
                                </small>
                            </p>
                            @isset($speedProfile)
                                <p class="mb-0">
                                    <small>Includes {{ number_format($speedProfile['fee_multiplier'], 2) }}x speed factor for {{ $speedProfile['label'] }} delivery.</small>
                                </p>
                            @endisset
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
                                                    @if(in_array($promo->discount_type, ['percentage', 'percent']))
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
                    
                    <!-- Buyable Offers -->
                    @php $selectedOffersList = $selectedOffers ?? []; @endphp
                    @if(isset($purchaseOffers) && $purchaseOffers->count() > 0)
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-stars me-2"></i>Buyable Offers & Boosters</h5>
                            <div class="row g-3">
                                @foreach($purchaseOffers as $offer)
                                    <div class="col-md-4">
                                        <label class="card h-100 border-primary" style="cursor: pointer;">
                                            <div class="card-body d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <strong>{{ $offer['name'] }}</strong>
                                                    <span class="badge bg-primary">{{ number_format($offer['price'], 2) }} {{ $currencyFrom }}</span>
                                                </div>
                                                <p class="text-muted small flex-grow-1 mb-2">{{ $offer['description'] }}</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-success">Save up to {{ number_format($offer['savings'], 2) }} {{ $currencyFrom }}</small>
                                                    <input type="checkbox" name="selected_offers[]" class="form-check-input mt-0 offer-checkbox"
                                                           value="{{ $offer['name'] }}" {{ in_array($offer['name'], $selectedOffersList) ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <strong>Selected offers:</strong>
                                <span id="selected-offers-summary">
                                    @if(count($selectedOffersList) > 0)
                                        {{ implode(', ', $selectedOffersList) }}
                                    @else
                                        None
                                    @endif
                                </span>
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
                        <form method="GET" action="{{ route('app.transfers.create') }}">
                            <input type="hidden" name="amount" value="{{ $amount }}">
                            <input type="hidden" name="currency_from" value="{{ $currencyFrom }}">
                            <input type="hidden" name="currency_to" value="{{ $currencyTo }}">
                            <input type="hidden" name="speed" value="{{ $speed }}">
                            @if($methodId)
                                <input type="hidden" name="transfer_method_id" value="{{ $methodId }}">
                            @endif
                            @if(isset($selectedOffers))
                                @foreach($selectedOffers as $offerName)
                                    <input type="hidden" name="selected_offers[]" value="{{ $offerName }}">
                                @endforeach
                            @endif
                            <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                                <i class="bi bi-send me-2"></i>Create Transfer with These Options
                            </button>
                        </form>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const summaryEl = document.getElementById('selected-offers-summary');
        function refreshSummary() {
            const checked = Array.from(document.querySelectorAll('.offer-checkbox:checked')).map(cb => cb.value);
            summaryEl.textContent = checked.length ? checked.join(', ') : 'None';
        }
        document.querySelectorAll('.offer-checkbox').forEach(cb => {
            cb.addEventListener('change', refreshSummary);
        });
        refreshSummary();
    });
</script>
@endpush
