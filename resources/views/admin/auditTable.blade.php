<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
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

        .main-content {
            flex: 1;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
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
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Audit Logs</h1>
            <div class="user-info">
                Welcome
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Metadata</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $logged)
                <tr>
                    <td>{{ $logged->id }}</td>
                    <td>{{ $logged->user}}</td>
                    <td>{{ $logged->action }}</td>
                    <td>{{ $logged->table_name }}</td>
                    <td>{{ $logged->record_id }}</td>
                    <td>{{ $logged->metadata }}</td>
                    <td>{{ $logged->created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</body>
</html>
