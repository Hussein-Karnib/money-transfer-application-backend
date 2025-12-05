@extends('layouts.app')

@section('title', 'Register Agent')

@section('content')
<h1 class="h4 mb-3">Register as Agent / Partner Store</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('agents.store') }}">
            @csrf

            <h5 class="mb-3">Account Information</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Confirm</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <h5 class="mb-3">Store Details</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Store Name</label>
                    <input type="text" name="store_name" class="form-control"
                           value="{{ old('store_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control"
                           value="{{ old('address') }}" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Latitude (optional)</label>
                    <input type="text" name="latitude" class="form-control"
                           value="{{ old('latitude') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Longitude (optional)</label>
                    <input type="text" name="longitude" class="form-control"
                           value="{{ old('longitude') }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit for approval</button>
        </form>
    </div>
</div>
@endsection
