<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="nav-link active" href="{{ route('admin.agents.index') }}">Agents</a>
                <a class="nav-link" href="{{ route('admin.auditTable') }}">Audit Logs</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Agents</h2>
            <div>
                <a href="{{ route('agents.map') }}" class="btn btn-outline-primary">View Agent Map</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filter by Status -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.agents.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Agents Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Store Name</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="agents-table-body">
                            <tr>
                                <td colspan="7" class="text-center">Loading agents...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Page navigation" id="pagination-nav" class="mt-3 d-none">
                    <ul class="pagination justify-content-center" id="pagination-links">
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadAgents() {
            try {
                const status = new URLSearchParams(window.location.search).get('status') || '';
                const url = status ? `{{ route('admin.agents.index') }}?status=${status}` : '{{ route('admin.agents.index') }}';
                
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load agents');
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error('Invalid response format');
                }

                const agents = data.data.data || data.data;
                const tbody = document.getElementById('agents-table-body');
                tbody.innerHTML = '';

                if (agents.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">No agents found.</td></tr>';
                    return;
                }

                agents.forEach(agent => {
                    const tr = document.createElement('tr');
                    const statusClass = {
                        'pending': 'warning',
                        'approved': 'success',
                        'suspended': 'danger'
                    }[agent.status] || 'secondary';

                    tr.innerHTML = `
                        <td>${agent.id}</td>
                        <td><strong>${agent.store_name || 'N/A'}</strong></td>
                        <td>${agent.user?.name || 'N/A'}</td>
                        <td>${agent.user?.email || 'N/A'}</td>
                        <td>${agent.address || 'N/A'}</td>
                        <td><span class="badge bg-${statusClass}">${agent.status || 'pending'}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/agents/${agent.id}" class="btn btn-outline-info" target="_blank">View</a>
                                ${agent.status !== 'approved' ? `
                                    <form method="POST" action="/admin/agents/${agent.id}/status" class="d-inline" onsubmit="return confirm('Approve this agent?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-outline-success">Approve</button>
                                    </form>
                                ` : ''}
                                ${agent.status !== 'suspended' ? `
                                    <form method="POST" action="/admin/agents/${agent.id}/status" class="d-inline" onsubmit="return confirm('Suspend this agent?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="suspended">
                                        <button type="submit" class="btn btn-outline-warning">Suspend</button>
                                    </form>
                                ` : ''}
                                <form method="POST" action="/admin/agents/${agent.id}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this agent?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                // Handle pagination if present
                if (data.data.links) {
                    // You can implement pagination links here if needed
                }
            } catch (error) {
                console.error('Error loading agents:', error);
                document.getElementById('agents-table-body').innerHTML = 
                    '<tr><td colspan="7" class="text-center text-danger">Error loading agents. Please refresh the page.</td></tr>';
            }
        }

        // Load agents on page load
        loadAgents();
    </script>
</body>
</html>

