<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Approvals</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            background-color: white;
            color: black;
        }
        .sidebar {
            width: 250px;
            background-color: gray;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 1px solid white;
            padding-bottom: 10px;
        }

        .nav-links {
            list-style: none;
            padding: 0;
        }

        .nav-links li {
            margin-bottom: 15px;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 10px;
        }

        .nav-links a:hover {
            background-color: silver;
            color: blue;
        }
        
        .logout-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 10px;
            text-align: left;
            width: 100%;
        }
        
        .logout-btn:hover {
             background-color: silver;
             color: blue;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border: 1px solid gray;
        }

        .user-info {
            font-weight: bold;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
        
        h3 {
            margin-top: 0;
        }

        .action-form {
            display: inline;
        }

        .action-btn {
            border: none; 
            background: none; 
            color: blue; 
            text-decoration: underline; 
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="nav-links">
            <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li><a href="{{ route('admin.reports') }}">Reports</a></li>
            <li><a href="{{ route('admin.auditTable') }}">Audit Logs</a></li>
            <li><a href="{{ route('admin.statistics') }}">Statistics</a></li>
            <li><a href="{{ route('admin.approvals') }}">Approvals</a></li>
            <li>
                <form method="POST" action="{{ route('auth.logout') }}" id="logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Approvals & New Users</h1>
            <div class="user-info">
                Welcome
            </div>
        </div>

        <h3>Pending Agents</h3>
        @if($pendingAgents->isEmpty())
            <p>No pending agents.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Store Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingAgents as $agent)
                    <tr>
                        <td>{{ $agent->user->name ?? 'N/A' }}</td>
                        <td>{{ $agent->store_name }}</td>
                        <td>{{ $agent->user->email ?? 'N/A' }}</td>
                        <td>
                            <form action="{{ route('admin.agents.update_status', $agent->id) }}" method="POST" class="action-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="action-btn">Approve</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>New Users</h3>
        @if($newUsers->isEmpty())
            <p>No new users.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($newUsers as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <form action="{{ route('admin.users.approve', $user->id) }}" method="POST" class="action-form">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="action-btn">Approve</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</body>
</html>
