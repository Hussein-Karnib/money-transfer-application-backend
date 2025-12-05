@extends('layouts.app')

@section('title', 'Edit Agent')

@section('content')
<h1 class="h4 mb-3">Edit Store: {{ $agent->store_name }}</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('agents.update', $agent) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label">Store Name</label>
                <input type="text" name="store_name" class="form-control"
                       value="{{ old('store_name', $agent->store_name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control"
                       value="{{ old('address', $agent->address) }}" required>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="latitude" class="form-control"
                           value="{{ old('latitude', $agent->latitude) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="longitude" class="form-control"
                           value="{{ old('longitude', $agent->longitude) }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('agents.show', $agent) }}" class="btn btn-outline-secondary">Back</a>
        </form>
    </div>
</div>
@endsection
