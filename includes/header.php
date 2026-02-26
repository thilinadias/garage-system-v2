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
        :root {
            /* Light Theme Variables */
            --bg-color: #f4f7fa;
            --text-color: #2c3e50;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-hover: #334155;
            --card-bg: #ffffff;
            --card-border: rgba(0,0,0,0.05);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --primary-color: #2563eb;
            --nav-bg: #ffffff;
        }

        body.bg-dark {
            /* Dark Theme Variables */
            --bg-color: #0f172a;
            --text-color: #f8fafc;
            --sidebar-bg: #020617;
            --sidebar-text: #94a3b8;
            --sidebar-hover: #1e293b;
            --card-bg: #1e293b;
            --card-border: rgba(255,255,255,0.05);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --primary-color: #3b82f6;
            --nav-bg: #1e293b;
            
            background-color: var(--bg-color) !important;
            color: var(--text-color) !important;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar Styling */
        .sidebar {
            background-color: var(--sidebar-bg) !important;
            color: white;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .sidebar a {
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            border-radius: 8px;
            margin: 4px 10px;
            transition: all 0.2s ease;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: var(--sidebar-hover);
            color: white;
            transform: translateX(4px);
        }
        .sidebar .brand {
            font-size: 1.5rem;
            font-weight: 700;
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            letter-spacing: 0.5px;
            margin: 0;
            border-radius: 0;
        }
        .sidebar .brand:hover {
            transform: none;
            background-color: transparent;
        }

        /* Layout & Components */
        .main-content {
            padding: 24px;
        }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.3s, color 0.3s;
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--card-border);
            padding: 16px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        /* Navbar */
        .navbar-custom {
            background-color: var(--nav-bg) !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-bottom: 1px solid var(--card-border);
        }
        
        /* Buttons & Inputs */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #ced4da;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }

        /* Dark Mode Overrides for Components */
        body.bg-dark .form-control, 
        body.bg-dark .form-select {
            background-color: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        body.bg-dark .form-control:focus, 
        body.bg-dark .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        body.bg-dark .table {
            color: var(--text-color);
        }
        body.bg-dark .text-muted {
            color: #94a3b8 !important;
        }
        body.bg-dark .card-header.bg-light,
        body.bg-dark .bg-light {
            background-color: #0f172a !important;
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
    <div class="d-flex min-vh-100">
        <!-- Sidebar will be included here -->
