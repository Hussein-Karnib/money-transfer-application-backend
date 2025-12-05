@extends('layouts.app')

@section('title', 'Send Feedback')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-modern">
                <div class="card-header">
                    <i class="bi bi-chat-dots me-2"></i> Send Feedback to Admin
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        We value your feedback! Please let us know if you have any questions, suggestions, or issues.
                    </p>

                    <form action="{{ route('reviews.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="message" class="form-label form-label-modern">Your Message</label>
                            <textarea name="message" id="message" rows="5" 
                                class="form-control form-control-modern @error('message') is-invalid @enderror"
                                placeholder="Write your message here..." required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-modern btn-modern me-2">Cancel</a>
                            <button type="button" class="btn btn-primary-modern btn-modern" onclick="this.closest('form').submit();">
                                <i class="bi bi-send me-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
