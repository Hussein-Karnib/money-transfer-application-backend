<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $agent->store_name ?? 'Agent Profile' }} - Money Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .store-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .info-card {
            border-left: 4px solid #667eea;
        }
        .hours-table {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Money Transfer</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link" href="{{ route('agents.map') }}">Find Agents</a>
            </div>
        </div>
    </nav>

    <div class="store-header">
        <div class="container">
            <h1 class="display-4">{{ $agent->store_name ?? 'Agent Store' }}</h1>
            <p class="lead">{{ $agent->address ?? 'No address provided' }}</p>
            @if($agent->status === 'approved')
                <span class="badge bg-success fs-6">Approved Agent</span>
            @elseif($agent->status === 'pending')
                <span class="badge bg-warning fs-6">Pending Approval</span>
            @else
                <span class="badge bg-danger fs-6">Suspended</span>
            @endif
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Store Information</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Store Name:</dt>
                            <dd class="col-sm-9">{{ $agent->store_name ?? 'N/A' }}</dd>

                            <dt class="col-sm-3">Owner:</dt>
                            <dd class="col-sm-9">{{ $agent->user->name ?? 'N/A' }}</dd>

                            <dt class="col-sm-3">Address:</dt>
                            <dd class="col-sm-9">{{ $agent->address ?? 'No address provided' }}</dd>

                            @if($agent->latitude && $agent->longitude)
                            <dt class="col-sm-3">Location:</dt>
                            <dd class="col-sm-9">
                                <a href="https://www.google.com/maps?q={{ $agent->latitude }},{{ $agent->longitude }}" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    View on Map
                                </a>
                            </dd>
                            @endif
                        </dl>
                    </div>
                </div>

                @if($agent->hours && $agent->hours->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Working Hours</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm hours-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                @endphp
                                @foreach($days as $dayIndex => $dayName)
                                    @php
                                        $hour = $agent->hours->firstWhere('day_of_week', $dayIndex);
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $dayName }}</strong></td>
                                        <td>
                                            @if($hour && !$hour->is_closed)
                                                {{ \Carbon\Carbon::parse($hour->open_time)->format('g:i A') }} - 
                                                {{ \Carbon\Carbon::parse($hour->close_time)->format('g:i A') }}
                                            @else
                                                <span class="text-muted">Closed</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($hour && !$hour->is_closed)
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
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('agents.map') }}" class="btn btn-primary">
                                Find More Agents
                            </a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                    Login to Transfer Money
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; {{ date('Y') }} Money Transfer Application</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

