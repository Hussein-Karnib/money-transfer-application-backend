<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            color: blue;        }
        
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

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border: 1px solid black;
         text-align: center;
        }

        .card h3 {
            margin-top: 0;
            color: black;
        }

        .card .number {
            font-weight: bold;
            color: blue;
            margin: 10px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="nav-links">
            <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li><a href="#">Reports</a></li>
            <li><a href="{{ route('admin.auditTable') }}">Audit Logs</a></li>
            <li><a href="{{ route('admin.statistics') }}">Statistics</a></li>
            <li>
                <form method="POST" action="#" id="logout-form">
                    @csrf
                    <a href="#" onclick="alert('Logout clicked'); return false;">Logout</a>
                </form>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                Welcome
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Agents</h3>
                <div class="number">{{ $data['active_agents_count'] }}</div>
                <p>Active agents</p>
            </div>
            <div class="card">
                <h3>Transactions</h3>
                <div class="number">{{ $data['total_transactions_count'] }}</div>
                <p>Completed transfers</p>
            </div>
            <div class="card">
                <h3>Pending Approvals</h3>
                <div class="number">{{ $data['pending_approvals_count'] }}</div>
                <p>Agents pending</p>
            </div>
            <div class="card">
                <h3>System Alerts</h3>
                <div class="number">0</div>
                <p>Requires attention</p>
            </div>
        </div>
    </div>

</body>
</html>
