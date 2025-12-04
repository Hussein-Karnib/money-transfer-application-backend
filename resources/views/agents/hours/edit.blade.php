@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Edit Working Hours - {{ $agent->store_name }}</h4>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.hours.update', $agent) }}">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Day</th>
                                    <th>Open</th>
                                    <th>Close Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($days as $dayIndex => $dayName)
                                    @php
                                        $hour = $hours->get($dayIndex);
                                        $isClosed = $hour ? $hour->is_closed : true;
                                        $openTime = $hour && !$isClosed ? \Carbon\Carbon::parse($hour->open_time)->format('H:i') : '';
                                        $closeTime = $hour && !$isClosed ? \Carbon\Carbon::parse($hour->close_time)->format('H:i') : '';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $dayName }}</strong></td>
                                        <td>
                                            <input type="time" 
                                                   class="form-control hours-open-time" 
                                                   name="hours[{{ $dayIndex }}][open_time]" 
                                                   value="{{ old("hours.$dayIndex.open_time", $openTime) }}"
                                                   data-day="{{ $dayIndex }}">
                                        </td>
                                        <td>
                                            <input type="time" 
                                                   class="form-control hours-close-time" 
                                                   name="hours[{{ $dayIndex }}][close_time]" 
                                                   value="{{ old("hours.$dayIndex.close_time", $closeTime) }}"
                                                   data-day="{{ $dayIndex }}">
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input hours-enabled" 
                                                       type="checkbox" 
                                                       name="hours[{{ $dayIndex }}][enabled]" 
                                                       value="1" 
                                                       data-day="{{ $dayIndex }}"
                                                       {{ !$isClosed ? 'checked' : '' }}
                                                       id="enabled_{{ $dayIndex }}">
                                                <label class="form-check-label" for="enabled_{{ $dayIndex }}">
                                                    Open
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-3">
                        <strong>Instructions:</strong>
                        <ul class="mb-0">
                            <li>Check "Open" to enable a day</li>
                            <li>Enter open and close times in 24-hour format (e.g., 09:00, 17:00)</li>
                            <li>Close time must be later than open time</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="{{ route('portal.hours.index', $agent) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Save Working Hours
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    // Disable time inputs when day is closed
    document.querySelectorAll('.hours-enabled').forEach(checkbox => {
        const day = checkbox.dataset.day;
        const openInput = document.querySelector(`.hours-open-time[data-day="${day}"]`);
        const closeInput = document.querySelector(`.hours-close-time[data-day="${day}"]`);

        function toggleInputs() {
            const isEnabled = checkbox.checked;
            openInput.disabled = !isEnabled;
            closeInput.disabled = !isEnabled;
            
            if (!isEnabled) {
                openInput.value = '';
                closeInput.value = '';
            }
        }

        checkbox.addEventListener('change', toggleInputs);
        toggleInputs(); // Initialize on page load
    });
</script>
@endsection

