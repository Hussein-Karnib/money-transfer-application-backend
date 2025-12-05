<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Money Transfer App')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --dark-bg: #1a1d29;
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            background-attachment: fixed;
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Modern Navbar */
        .navbar-modern {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar-modern .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .navbar-modern .nav-link {
            color: var(--text-primary) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .navbar-modern .nav-link:hover {
            background: var(--primary-gradient);
            color: white !important;
            transform: translateY(-2px);
        }

        .navbar-modern .nav-link.active {
            background: var(--primary-gradient);
            color: white !important;
        }

        /* Modern Cards */
        .card-modern {
            background: var(--card-bg);
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .card-modern .card-header {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-modern .card-body {
            padding: 1.5rem;
        }

        /* Buttons */
        .btn-modern {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-modern:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary-modern {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-modern {
            border: 2px solid;
            background: transparent;
            border-image: var(--primary-gradient) 1;
        }

        .btn-outline-modern:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Page Header */
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: var(--shadow-lg);
        }

        .page-header h1 {
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #48bb78; }
        .stat-card.warning { border-left-color: #ed8936; }
        .stat-card.danger { border-left-color: #f56565; }

        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tables */
        .table-modern {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .table-modern thead {
            background: var(--primary-gradient);
            color: white;
        }

        .table-modern thead th {
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background: #f7fafc;
            transform: scale(1.01);
        }

        .table-modern tbody td {
            padding: 1rem;
            border-color: var(--border-color);
        }

        /* Badges */
        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Forms */
        .form-control-modern {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-label-modern {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        /* Alerts */
        .alert-modern {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stat-card .stat-value {
                font-size: 2rem;
            }
        }

        /* Loading Spinner */
        .spinner-modern {
            border: 3px solid rgba(102, 126, 234, 0.1);
            border-top-color: #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ auth()->check() && auth()->user()->role && strtolower(auth()->user()->role->name) === 'admin' ? route('admin.dashboard') : route('dashboard') }}">
                <i class="bi bi-send-fill"></i> MoneyTransfer
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarMain" aria-controls="navbarMain"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @auth
                        @if(Auth::user()->role && strtolower(Auth::user()->role->name) === 'admin')
                            {{-- Admin Navigation --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                                   href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.approvals') ? 'active' : '' }}" 
                                   href="{{ route('admin.approvals') }}">
                                    <i class="bi bi-check-circle me-1"></i> Approvals
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}" 
                                   href="{{ route('admin.agents.index') }}">
                                    <i class="bi bi-people me-1"></i> Agents
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.statistics') ? 'active' : '' }}" 
                                   href="{{ route('admin.statistics') }}">
                                    <i class="bi bi-graph-up me-1"></i> Statistics
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" 
                                   href="{{ route('admin.reports.index') }}">
                                    <i class="bi bi-file-earmark-text me-1"></i> Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.auditTable.*') ? 'active' : '' }}" 
                                   href="{{ route('admin.auditTable') }}">
                                    <i class="bi bi-clock-history me-1"></i> Audit Logs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reviews.index') ? 'active' : '' }}" 
                                   href="{{ route('admin.reviews.index') }}">
                                    <i class="bi bi-chat-text me-1"></i> Feedback
                                </a>
                            </li>
                        @elseif(Auth::user()->role && strtolower(Auth::user()->role->name) === 'agent')
                            {{-- Agent Navigation --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" 
                                   href="{{ route('portal.dashboard') }}">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('portal.transfers.*') ? 'active' : '' }}" 
                                   href="{{ route('portal.transfers.pending', ['type' => 'cash_in']) }}">
                                    <i class="bi bi-arrow-down-circle me-1"></i> Cash-In
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('portal.transfers.*') ? 'active' : '' }}" 
                                   href="{{ route('portal.transfers.pending', ['type' => 'cash_out']) }}">
                                    <i class="bi bi-arrow-up-circle me-1"></i> Cash-Out
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('portal.transactions.*') ? 'active' : '' }}" 
                                   href="{{ route('portal.transactions.index') }}">
                                    <i class="bi bi-list-ul me-1"></i> Transactions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('portal.commissions') ? 'active' : '' }}" 
                                   href="{{ route('portal.commissions') }}">
                                    <i class="bi bi-graph-up me-1"></i> Commissions
                                </a>
                            </li>
                        @else
                            {{-- Regular User Navigation --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                                   href="{{ route('dashboard') }}">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('app.transfers.*') ? 'active' : '' }}" 
                                   href="{{ route('app.transfers.index') }}">
                                    <i class="bi bi-arrow-left-right me-1"></i> Transfers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('app.beneficiaries.*') ? 'active' : '' }}" 
                                   href="{{ route('app.beneficiaries.index') }}">
                                    <i class="bi bi-people me-1"></i> Beneficiaries
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('app.bank-accounts.*') ? 'active' : '' }}" 
                                   href="{{ route('app.bank-accounts.index') }}">
                                    <i class="bi bi-bank me-1"></i> Bank Accounts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('app.notifications.*') ? 'active' : '' }}" 
                                   href="{{ route('app.notifications.index') }}">
                                    <i class="bi bi-bell me-1"></i> Notifications
                                    @php
                                        $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                                    @endphp
                                    @if($unreadCount > 0)
                                        <span class="badge bg-danger badge-modern ms-1">{{ $unreadCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reviews.create') ? 'active' : '' }}" 
                                   href="{{ route('reviews.create') }}">
                                    <i class="bi bi-chat-dots me-1"></i> Feedback
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <div class="user-menu">
                    <a href="{{ route('profile.show') }}" class="d-flex align-items-center text-decoration-none">
                        @php $avatar = auth()->user()->avatar_url ?? null; @endphp
                        @if($avatar)
                            <img src="{{ $avatar }}" alt="Avatar" class="user-avatar" style="object-fit: cover;">
                        @else
                            <div class="user-avatar">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <span class="text-dark fw-semibold ms-2">{{ auth()->user()->name ?? 'User' }}</span>
                    </a>
                    <form method="POST" action="{{ route('auth.logout') }}" class="d-inline ms-3">
                        @csrf
                        <button class="btn btn-sm btn-outline-modern btn-outline-danger">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid px-4">
            @if(session('success'))
                <div class="alert alert-success alert-modern alert-dismissible fade show fade-in-up" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-modern alert-dismissible fade show fade-in-up" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-modern alert-dismissible fade show fade-in-up" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global CSRF token
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        // Add fade-in animation to cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-modern, .stat-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in-up');
            });
        });
    </script>

    @yield('scripts')
</body>
</html>
