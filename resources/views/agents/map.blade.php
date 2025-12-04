<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Agents - Money Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .agent-card {
            transition: transform 0.2s;
        }
        .agent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Money Transfer</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link active" href="{{ route('agents.map') }}">Find Agents</a>
                <a class="nav-link" href="{{ route('agents.register') }}">Become an Agent</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5">Find Agents Near You</h1>
                <p class="lead text-muted">Browse approved agent locations for cash pickup and money transfers</p>
            </div>
        </div>

        @if($agents->count() > 0)
            <div class="row">
                @foreach($agents as $agent)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card agent-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">{{ $agent->store_name ?? 'Agent Store' }}</h5>
                                <p class="card-text text-muted">
                                    <strong>Owner:</strong> {{ $agent->user->name ?? 'N/A' }}<br>
                                    <strong>Address:</strong> {{ $agent->address ?? 'No address provided' }}
                                </p>
                                
                                @if($agent->latitude && $agent->longitude)
                                    <a href="https://www.google.com/maps?q={{ $agent->latitude }},{{ $agent->longitude }}" 
                                       target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                                        View on Map
                                    </a>
                                @endif
                                
                                <a href="{{ route('agents.public_profile', $agent->id) }}" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </div>
                            <div class="card-footer bg-white">
                                <small class="text-muted">
                                    @if($agent->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-warning">{{ ucfirst($agent->status) }}</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info">
                <h4>No Agents Available</h4>
                <p>There are currently no approved agents in the system. Check back later or <a href="{{ route('agents.register') }}">become an agent</a>.</p>
            </div>
        @endif
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; {{ date('Y') }} Money Transfer Application</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

