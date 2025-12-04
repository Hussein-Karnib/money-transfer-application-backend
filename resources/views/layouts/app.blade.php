<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Money Transfer App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF token for AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap via CDN (keep it simple) --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .badge-pill {
            border-radius: 9999px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">MoneyTransfer</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('app.transfers.index') }}">Transfers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('app.beneficiaries.index') }}">Beneficiaries</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('app.bank-accounts.index') }}">Bank Accounts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('app.kyc.show') }}">KYC</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('app.notifications.index') }}">
                        Notifications
                        @php
                            $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                        @endphp
                        @if($unreadCount > 0)
                            <span class="badge bg-danger badge-pill">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center text-white">
                <span class="me-3">{{ auth()->user()->name ?? 'User' }}</span>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-light">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<div class="container mb-5">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Global CSRF token (if needed for forms)
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
</script>

@yield('scripts')
</body>
</html>
