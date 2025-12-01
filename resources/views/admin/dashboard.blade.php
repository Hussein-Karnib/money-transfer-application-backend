<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            background-color: #f4f7f6;
            color: #333;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            border-radius: 5px;
        }

        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
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
             background-color: rgba(255,255,255,0.1);
             color: blue;
        }


        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;

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
            border-radius: 8px;
                        text-align: center;
        }

        .card h3 {
            margin-top: 0;
            color: #2c3e50;
        }

        .card .number {
            font-weight: bold;
            color: #3498db;
            margin: 10px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="nav-links">
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Manage Admins</a></li>
            <li><a href="#">Manage Agents</a></li>
            <li><a href="#">Reports</a></li>
            <li><a href="#">Audit Logs</a></li>
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
                <div class="number">0</div>
                <p>Active agwnts</p>
            </div>
            <div class="card">
                <h3>Transactions</h3>
                <div class="number">0</div>
                <p></p>
            </div>
            <div class="card">
                <h3>Pending Approvals</h3>
                <div class="number">0</div>
                <p>Agents waiting</p>
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
