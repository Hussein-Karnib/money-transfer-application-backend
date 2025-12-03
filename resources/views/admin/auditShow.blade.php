<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Details</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid black;
            padding: 20px;
        }
        h1 {
            text-align: center;
            border-bottom: 1px solid gray;
            padding-bottom: 10px;
        }
        .detail-row {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background-color: gray;
            color: white;
            padding: 10px 20px;
        }
        .back-btn:hover {
            background-color: silver;
            color: blue;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Audit Log {{ $data->id }}</h1>

        <div class="detail-row">
            <span class="label">User:</span>
            {{ $data->user }}
        </div>

        <div class="detail-row">
            <span class="label">Action:</span>
            {{ $data->action }}
        </div>

        <div class="detail-row">
            <span class="label">Table:</span>
            {{ $data->table_name }}
        </div>

        <div class="detail-row">
            <span class="label">Record ID:</span>
            {{ $data->record_id }}
        </div>

        <div class="detail-row">
            <span class="label">Date:</span>
            {{ $data->created_at }}
        </div>

        <div class="detail-row">
            <span class="label">Metadata:</span>
            <pre>{{ $data->metadata }}</pre>
        </div>

        <a href="{{ route('admin.auditTable') }}" class="back-btn">Back to List</a>
    </div>

</body>
</html>
