@extends('layouts.app')

@section('title', 'New Transfer')

@section('content')
@php
    $prefillAmount = $prefill['amount'] ?? request('amount', 1000);
    $prefillCurrencyFrom = $prefill['currency_from'] ?? request('currency_from', 'USD');
    $prefillCurrencyTo = $prefill['currency_to'] ?? request('currency_to', 'LBP');
    $prefillSpeed = $prefill['speed'] ?? request('speed', 'standard');
    $prefillMethodId = $prefill['transfer_method_id'] ?? request('transfer_method_id');
    $prefillOffers = $prefill['selected_offers'] ?? request()->input('selected_offers', []);
@endphp
<div class="page-header">
    <div class="container-fluid px-4">
        <h1><i class="bi bi-send me-2"></i>New Transfer</h1>
        <p>Send money to your beneficiaries quickly and securely</p>
    </div>
</div>

@if(session('error'))
    <div class="container-fluid px-4 mb-4">
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>Transfer Details
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('transfers.store') }}">
                    @csrf
                    @if(!empty($prefillOffers))
                        @foreach($prefillOffers as $offerName)
                            <input type="hidden" name="selected_offers[]" value="{{ $offerName }}">
                        @endforeach
                    @endif

                    <div class="mb-3">
                        <label for="beneficiary_id" class="form-label-modern">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" id="beneficiary_id" class="form-select form-control-modern @error('beneficiary_id') is-invalid @enderror" required>
                            <option value="">Select beneficiary...</option>
                            @foreach($beneficiaries as $beneficiary)
                                <option value="{{ $beneficiary->id }}" {{ old('beneficiary_id') == $beneficiary->id ? 'selected' : '' }}>
                                    {{ $beneficiary->full_name }} ({{ $beneficiary->country->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('beneficiary_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label-modern">Amount to Send <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" class="form-control form-control-modern @error('amount') is-invalid @enderror" 
                               id="amount" name="amount" value="{{ old('amount', $prefillAmount ?? 1000) }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="currency_from" class="form-label-modern">Currency From <span class="text-danger">*</span></label>
                            <select name="currency_from" id="currency_from" class="form-select form-control-modern @error('currency_from') is-invalid @enderror" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" {{ old('currency_from', $prefillCurrencyFrom ?? 'USD') == $currency->code ? 'selected' : '' }}>
                                        {{ $currency->code }} - {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('currency_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency_to" class="form-label-modern">Currency To <span class="text-danger">*</span></label>
                            <select name="currency_to" id="currency_to" class="form-select form-control-modern @error('currency_to') is-invalid @enderror" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" {{ old('currency_to', $prefillCurrencyTo ?? 'LBP') == $currency->code ? 'selected' : '' }}>
                                        {{ $currency->code }} - {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('currency_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="promo_code" class="form-label-modern">Promo Code (optional)</label>
                        <input type="text" class="form-control form-control-modern @error('promo_code') is-invalid @enderror" 
                               id="promo_code" name="promo_code" value="{{ old('promo_code') }}" placeholder="WELCOME10">
                        @error('promo_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="transfer_method_id" class="form-label-modern">Transfer Method</label>
                        <select id="transfer_method_id" name="transfer_method_id" class="form-select form-control-modern">
                            <option value="">Use beneficiary default</option>
                            @foreach($methods as $method)
                                <option value="{{ $method->id }}" {{ old('transfer_method_id', $prefillMethodId ?? '') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="speed" class="form-label-modern">Transfer Speed</label>
                        <select name="speed" id="speed" class="form-select form-control-modern">
                            <option value="instant" {{ old('speed', $prefillSpeed ?? '') == 'instant' ? 'selected' : '' }}>Instant (minutes, higher fee)</option>
                            <option value="express" {{ old('speed', $prefillSpeed ?? '') == 'express' ? 'selected' : '' }}>Express (2 hours)</option>
                            <option value="same_day" {{ old('speed', $prefillSpeed ?? '') == 'same_day' ? 'selected' : '' }}>Same Day</option>
                            <option value="standard" {{ old('speed', $prefillSpeed ?? 'standard') == 'standard' ? 'selected' : '' }}>Standard (next day)</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-modern btn-modern">
                            <i class="bi bi-check-circle me-2"></i>Create Transfer
                        </button>
                        <a href="{{ route('app.transfers.index') }}" class="btn btn-outline-modern btn-modern">
                            <i class="bi bi-arrow-left me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Transfer Information
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <i class="bi bi-shield-check me-1"></i>
                    All transfers are secure and encrypted.
                </p>
                <p class="text-muted small">
                    <i class="bi bi-clock me-1"></i>
                    Standard transfers take 24 hours, express transfers take 1 hour.
                </p>
                <p class="text-muted small">
                    <i class="bi bi-tag me-1"></i>
                    Enter a promo code to get discounts on transfer fees.
                </p>
                @if(!empty($prefillOffers))
                <p class="text-muted small">
                    <i class="bi bi-stars me-1"></i>
                    Selected offers: {{ implode(', ', $prefillOffers) }}
                </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
