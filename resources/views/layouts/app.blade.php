<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SOS Consultas')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3e 50%, #0d0d2b 100%);
            min-height: 100vh;
            color: #e0e0e0;
        }

        /* Navbar */
        .navbar {
            background: rgba(15, 15, 40, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .navbar-brand .logo {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.7rem;
            color: #fff;
        }

        .navbar-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .navbar-links a {
            color: #a0a0c0;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .navbar-links a:hover, .navbar-links a.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.85rem;
            color: #8888aa;
        }

        .btn-logout {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b7a;
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 0.4rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.4);
        }

        /* Main content */
        .main-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Glass card  */
        .glass-card {
            background: rgba(20, 20, 50, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .glass-card h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .glass-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            color: #ccc;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            color: #9999bb;
        }

        .form-control {
            width: 100%;
            padding: 0.6rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #00b4d8;
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15);
        }

        .form-control::placeholder { color: #555580; }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239999bb' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 2.5rem;
        }

        select.form-control option { background: #1a1a3e; color: #fff; }

        /* Buttons */
        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: #fff;
        }

        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .btn-success {
            background: linear-gradient(135deg, #00c853, #009624);
            color: #fff;
        }

        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0, 200, 83, 0.3); }

        .btn-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b7a;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover { background: rgba(220, 53, 69, 0.4); }

        .btn-sm { padding: 0.35rem 0.8rem; font-size: 0.8rem; }

        /* Tables */
        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 0.7rem 1rem;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #7777aa;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            white-space: nowrap;
        }

        td {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            white-space: nowrap;
        }

        tr:hover td { background: rgba(255, 255, 255, 0.03); }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: rgba(0, 200, 83, 0.15); color: #69f0ae; }
        .badge-warning { background: rgba(255, 193, 7, 0.15); color: #ffd54f; }
        .badge-danger  { background: rgba(220, 53, 69, 0.15); color: #ff6b7a; }
        .badge-info    { background: rgba(0, 180, 216, 0.15); color: #4dd0e1; }
        .badge-admin   { background: rgba(156, 39, 176, 0.15); color: #ce93d8; }

        /* Alerts */
        .alert {
            padding: 0.8rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background: rgba(0, 200, 83, 0.1);
            border: 1px solid rgba(0, 200, 83, 0.2);
            color: #69f0ae;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #ff6b7a;
        }

        .alert-info {
            background: rgba(0, 180, 216, 0.1);
            border: 1px solid rgba(0, 180, 216, 0.2);
            color: #4dd0e1;
        }

        /* Progress bar */
        .progress-container {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            height: 24px;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            border-radius: 10px;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fff;
            min-width: 2rem;
        }

        /* File upload */
        .file-upload {
            border: 2px dashed rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: #00b4d8;
            background: rgba(0, 180, 216, 0.05);
        }

        .file-upload input[type="file"] { display: none; }

        .file-upload .icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .file-upload p {
            color: #7777aa;
            font-size: 0.9rem;
        }

        .file-upload .filename {
            color: #00b4d8;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Grid helpers */
        .row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .col { flex: 1; min-width: 200px; }

        /* Inline form */
        .form-inline {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-inline .form-group { margin-bottom: 0; }

        /* Modal overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active { display: flex; }

        .modal-content {
            background: rgba(20, 20, 50, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-content h3 { margin-bottom: 1.5rem; color: #fff; }

        .modal-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn-ghost {
            background: transparent;
            color: #8888aa;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-ghost:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }

        /* Result cards */
        .result-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            transition: border-color 0.3s;
        }

        .result-item:hover { border-color: rgba(0, 180, 216, 0.3); }

        .result-item.error { border-color: rgba(220, 53, 69, 0.3); }

        .result-item .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .result-item .cedula-label {
            font-weight: 700;
            font-size: 1rem;
            color: #00b4d8;
        }

        .result-item .result-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.4rem;
        }

        .result-item .field {
            font-size: 0.8rem;
        }

        .result-item .field-label {
            color: #7777aa;
        }

        .result-item .field-value {
            color: #e0e0e0;
            font-weight: 500;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.25); }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: #00b4d8;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Pagination */
        .pagination { display: flex; gap: 0.3rem; justify-content: center; margin-top: 1rem; }
        .pagination a, .pagination span {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            color: #8888aa;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .pagination a:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
        .pagination .active span { background: rgba(0, 180, 216, 0.2); color: #00b4d8; border-color: rgba(0, 180, 216, 0.3); }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar { padding: 0 1rem; }
            .navbar-links { gap: 0; }
            .navbar-links a { padding: 0.5rem 0.6rem; font-size: 0.8rem; }
            .main-content { padding: 1rem; }
            .row { flex-direction: column; }
        }
    </style>
</head>
<body>
    @auth
    <nav class="navbar">
        <a href="{{ auth()->user()->isAdmin() ? route('consultas.index') : route('consultas.files') }}" class="navbar-brand">
            <span class="logo">SOS</span>
            SOS Consultas
        </a>
        <div class="navbar-links">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('consultas.index') }}" class="{{ request()->routeIs('consultas.index') ? 'active' : '' }}">Procesar</a>
            @endif
            <a href="{{ route('consultas.search') }}" class="{{ request()->routeIs('consultas.search') ? 'active' : '' }}">Buscar</a>
            <a href="{{ route('consultas.files') }}" class="{{ request()->routeIs('consultas.files') ? 'active' : '' }}">Consultas</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Usuarios</a>
            @endif
        </div>
        <div class="navbar-user">
            <span>{{ auth()->user()->name }} <span class="badge {{ auth()->user()->isAdmin() ? 'badge-admin' : 'badge-info' }}">{{ auth()->user()->role }}</span></span>
            <a href="{{ route('logout') }}" class="btn-logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Salir</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
        </div>
    </nav>
    @endauth

    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
