@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Working Hours - {{ $agent->store_name }}</h4>
                <a href="{{ route('portal.hours.edit') }}" class="btn btn-light btn-sm">
                    Edit Hours
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Open Time</th>
                                <th>Close Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $dayIndex => $dayName)
                                @php
                                    $hour = $hours->firstWhere('day_of_week', $dayIndex);
                                    $isClosed = $hour ? $hour->is_closed : true;
                                @endphp
                                <tr>
                                    <td><strong>{{ $dayName }}</strong></td>
                                    <td>
                                        @if($hour && !$isClosed)
                                            {{ \Carbon\Carbon::parse($hour->open_time)->format('g:i A') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hour && !$isClosed)
                                            {{ \Carbon\Carbon::parse($hour->close_time)->format('g:i A') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hour && !$isClosed)
                                            <span class="badge bg-success">Open</span>
                                        @else
                                            <span class="badge bg-secondary">Closed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <a href="{{ route('portal.hours.edit') }}" class="btn btn-primary">
                        Edit Working Hours
                    </a>
                    <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

