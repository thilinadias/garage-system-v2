<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
            color: white;
        }
        .sidebar .brand {
            font-size: 1.5rem;
            font-weight: bold;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #495057;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
            transition: background-color 0.3s, color 0.3s;
        }
        .navbar-custom {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        
        /* Dark Mode Overrides */
        body.bg-dark {
            background-color: #212529 !important;
            color: #f8f9fa !important;
        }
        body.bg-dark .card {
            background-color: #343a40;
            color: #f8f9fa;
        }
        body.bg-dark .navbar-custom {
            background-color: #343a40;
            border-bottom: 1px solid #495057;
        }
         body.bg-dark .table {
            color: #f8f9fa;
        }
         body.bg-dark .form-control, body.bg-dark .form-select {
            background-color: #495057;
            border-color: #6c757d;
            color: white;
        }
        /* Global Print Styles */
        @media print {
            .print-hide, .sidebar, .navbar-custom, .btn, .print-none { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            body { background-color: white !important; color: black !important; }
            .card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
            .container-fluid { padding: 0 !important; }
            .print-only { display: block !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar will be included here -->
