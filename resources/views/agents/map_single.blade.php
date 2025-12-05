<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Map - Money Transfer</title>
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
        #agent-map {
            height: calc(100vh - 170px);
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

    <div class="container-fluid py-4">
        <div class="row mb-3">
            <div class="col d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="h3 mb-1">Agent Location</h1>
                    <p class="text-muted mb-0">Internal Leaflet map view for agent markers.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('agents.map') }}" class="btn btn-outline-secondary btn-sm">
                        Back to list
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div id="agent-map"></div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <a href="{{ route('agents.map') }}" class="btn btn-outline-secondary btn-sm">
                    Back to list
                </a>
            </div>
        </div>
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
        // Current payload is a single agent.
        const agent = @json($agent);

        const map = L.map('agent-map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const hasCoords = agent?.latitude && agent?.longitude;

        if (hasCoords) {
            const latLng = [agent.latitude, agent.longitude];
            const marker = L.marker(latLng).addTo(map);

            const popupContent = `
                <div>
                    <strong>${agent.store_name ?? 'Agent'}</strong><br>
                    <span class="text-muted">${agent.address ?? ''}</span>
                </div>
            `;

            marker.bindPopup(popupContent);
            map.setView(latLng, 15);
        } else {
            map.setView([0, 0], 2);
        }

        // Future extension points:
        // - Swap payload to an array of agents; loop through and set bounds as in map_all.
        // - Add filters (open now, distance, status) to adjust the payload and markers.
        // - Add a side list synced with markers to highlight and pan to an agent on hover/click.
    </script>
</body>
</html>
