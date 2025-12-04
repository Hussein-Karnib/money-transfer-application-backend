<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
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
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
        
        .form-container {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid black;
        }
        
        .form-container label {
            margin-right: 10px;
        }
        
        .form-container input, .form-container select {
            margin-right: 20px;
            padding: 5px;
        }
        
        button {
            padding: 5px 10px;
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
                <form method="POST" action="#" id="logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Reports</h1>
            <div class="user-info">
                Welcome
            </div>
        </div>
        
        <div class="form-container">
            <h3>Generate Report</h3>
            <form action="{{ route('admin.reports.store') }}" method="POST">
                @csrf
                <label>Type:</label>
                <select name="type">
                    <option value="platform_usage">Platform Usage</option>
                    <option value="transactions">Transactions</option>
                    <option value="feedback">Feedback</option>
                </select>
                
                <label>Start:</label>
                <input type="date" name="start_date" required>
                
                <label>End:</label>
                <input type="date" name="end_date" required>
                
                <button type="submit">Generate CSV</button>
            </form>
        </div>

        <h3>History</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                <tr>
                    <td>{{ $report->type }}</td>
                    <td>{{ $report->generated_at }}</td>
                    <td>
                        <a href="{{ route('admin.reports.download', $report) }}">Download</a>
                        <form action="{{ route('admin.reports.destroy', $report) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="border:none; background:none; color:blue; text-decoration:underline;">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
