<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Statistics</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid black;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid gray;
        }

        th {
            background-color: silver;
            font-weight: bold;
            color: black;
        }

        tr:hover {
            background-color: silver;
        }

        .search-container {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border: 1px solid gray;
        }

        .search-container input {
            padding: 5px;
            margin-right: 10px;
        }

        .search-container button {
            padding: 5px 10px;
            background-color: silver;
            border: 1px solid gray;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: gray;
            color: white;
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
            <h1>Statistics</h1>
            <div class="user-info">
                Welcome
            </div>
        </div>

        <div class="search-container">
            <form action="{{ route('searchDate') }}">
                <label>Start Date:</label>
                <input type="date" name="from_date" required>
                <label>End Date:</label>
                <input type="date" name="to_date" required>
                <button type="submit">Filter</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Users</td>
                    <td>{{ $data['users_count'] }}</td>
                </tr>
                <tr>
                    <td>Verified Users</td>
                    <td>{{ $data['verified_users_count'] }}</td>
                </tr>
                <tr>
                    <td>Total Agents</td>
                    <td>{{ $data['agents_count'] }}</td>
                </tr>
                <tr>
                    <td>Pending Agents</td>
                    <td>{{ $data['pending_agents_count'] }}</td>
                </tr>
                <tr>
                    <td>Total Transfers</td>
                    <td>{{ $data['transfers_count'] }}</td>
                </tr>
                <tr>
                    <td>Total Transfer Volume</td>
                    <td>{{ $data['total_transfer_volume'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>
