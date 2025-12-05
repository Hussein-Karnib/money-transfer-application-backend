<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Agents Map - Money Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        body {
            background-color: #f8fafc;
        }
        #agents-map {
            height: 70vh;
            min-height: 480px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Money Transfer</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link active" href="{{ route('agents.map_all') }}">Find Agents</a>
                <a class="nav-link" href="{{ route('agents.register') }}">Become an Agent</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-3">
            <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="h3 mb-2">All Agents Map</h1>
                    <p class="text-muted mb-0">Search approved agents and view all locations on the map.</p>
                </div>
                <a href="{{ route('agents.map') }}" class="btn btn-outline-secondary btn-sm">
                    Back to list
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('agents.map_all') }}" class="mb-3">
            <div class="input-group">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Search by ID, name, or address"
                    value="{{ $keyword }}"
                >
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <div id="agents-map" class="mb-4"></div>

        @if($list->isEmpty())
            <div class="alert alert-info">
                No agents found for this search.
            </div>
        @else
            <div class="card">
                <div class="card-header bg-white">
                    <strong>Agents ({{ $list->count() }})</strong>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($list as $agent)
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">{{ $agent->store_name }}</div>
                                <div class="text-muted small">ID: {{ $agent->id }}</div>
                                <div class="text-muted">{{ $agent->address }}</div>
                            </div>
                            <div class="ms-3">
                                <a href="{{ route('agents.public_profile', $agent->id) }}" class="btn btn-sm btn-outline-primary">
                                    View Profile
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <footer class="bg-light py-4 mt-4">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; {{ date('Y') }} Money Transfer Application</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        const agents = @json($mapData);

        const map = L.map('agents-map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const bounds = L.latLngBounds();

        agents.forEach(agent => {
            if (!agent.lat || !agent.lng) {
                return;
            }

            const marker = L.marker([agent.lat, agent.lng]).addTo(map);
            const popup = `
                <div>
                    <strong>${agent.store_name ?? 'Agent'}</strong><br>
                    <span class="text-muted">ID: ${agent.id}</span><br>
                    <span class="text-muted">${agent.address ?? ''}</span><br>
                    <a href="${agent.profile_url}" class="mt-2 d-inline-block">View profile</a>
                </div>
            `;
            marker.bindPopup(popup);
            bounds.extend([agent.lat, agent.lng]);
        });

        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.2));
        } else {
            map.setView([0, 0], 2);
        }
    </script>
</body>
</html>
