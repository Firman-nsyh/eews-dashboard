<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SeismoGuard — @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

    @vite(['resources/js/app.js'])

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0f1e;
            color: #e2e8f0;
            min-height: 100vh;
        }

        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            padding: 0 1.5rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .navbar-brand .logo {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .navbar-brand .brand-name {
            font-size: 1rem;
            font-weight: 700;
            color: white;
        }

        .navbar-brand .brand-sub {
            font-size: 0.65rem;
            color: #64748b;
            margin-top: -2px;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            list-style: none;
        }

        .navbar-nav a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            color: #94a3b8;
            transition: all 0.2s;
        }

        .navbar-nav a:hover {
            background: rgba(148, 163, 184, 0.1);
            color: #e2e8f0;
        }

        .navbar-nav a.active {
            background: rgba(6, 182, 212, 0.15);
            color: #06b6d4;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            color: #10b981;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #94a3b8;
            text-align: right;
        }

        /* Main content */
        .main-content {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Glass panel */
        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 12px;
            backdrop-filter: blur(8px);
        }

        /* Cards grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 12px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
        }

        .stat-card.cyan::before  { background: #06b6d4; }
        .stat-card.green::before { background: #10b981; }
        .stat-card.blue::before  { background: #3b82f6; }
        .stat-card.purple::before{ background: #8b5cf6; }

        .stat-label { font-size: 0.75rem; color: #64748b; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: white; }
        .stat-sub   { font-size: 0.7rem; color: #94a3b8; margin-top: 0.25rem; }
        .stat-icon  {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.08;
        }

        /* Status colors */
        .text-cyan   { color: #06b6d4; }
        .text-green  { color: #10b981; }
        .text-yellow { color: #f59e0b; }
        .text-red    { color: #ef4444; }
        .text-blue   { color: #3b82f6; }
        .text-slate  { color: #94a3b8; }

        /* Seismograph */
        .seismograph-container {
            position: relative;
            height: 350px;
            background: #060b18;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.08);
            overflow: hidden;
        }

        .seismograph-container canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* STA/LTA bar */
        .stalta-bar-container {
            margin-top: 1rem;
        }

        .stalta-bar-track {
            height: 14px;
            background: #1e293b;
            border-radius: 99px;
            overflow: hidden;
            position: relative;
        }

        .stalta-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #f59e0b, #ef4444);
            border-radius: 99px;
            transition: width 0.3s ease;
        }

        .stalta-threshold-line {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(255,255,255,0.5);
            left: 66.6%;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .data-table th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.06);
            color: #cbd5e1;
        }

        .data-table tr:hover td {
            background: rgba(148, 163, 184, 0.04);
        }

        /* Status badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
        }

        .badge-gempa   { background: rgba(239,68,68,0.15);  color: #ef4444; }
        .badge-siaga   { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .badge-waspada { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .badge-aman    { background: rgba(16,185,129,0.15); color: #10b981; }

        /* Alert cards */
        .alert-level-card {
            border-radius: 10px;
            padding: 1.25rem;
            border: 1px solid;
        }

        .alert-level-card.danger  { background: rgba(239,68,68,0.08);  border-color: rgba(239,68,68,0.3); }
        .alert-level-card.warning { background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.3); }
        .alert-level-card.watch   { background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.3); }
        .alert-level-card.safe    { background: rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.3); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 99px; }

        /* Utility */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .rounded { border-radius: 8px; }
        .rounded-lg { border-radius: 12px; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .text-lg { font-size: 1.125rem; }
        .text-xl { font-size: 1.25rem; }
        .text-2xl { font-size: 1.5rem; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .text-white { color: white; }
        .w-full { width: 100%; }
        .overflow-auto { overflow: auto; }
        .space-y > * + * { margin-top: 1rem; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a href="{{ route('dashboard') }}" class="navbar-brand">
        <div class="logo"><i class="fa-solid fa-house-crack"></i></div>
        <div>
            <div class="brand-name">SeismoGuard</div>
            <div class="brand-sub">Earthquake Early Warning System</div>
        </div>
    </a>

    <ul class="navbar-nav">
        <li>
            <a href="{{ route('dashboard') }}"
               class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="{{ route('seismograph') }}"
               class="{{ request()->routeIs('seismograph') ? 'active' : '' }}">
                <i class="fa-solid fa-wave-square"></i> Seismograf
            </a>
        </li>
        <li>
            <a href="{{ route('logdata') }}"
               class="{{ request()->routeIs('logdata') ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i> Log Data
            </a>
        </li>
        <li>
            <a href="{{ route('alerts') }}"
               class="{{ request()->routeIs('alerts') ? 'active' : '' }}">
                <i class="fa-solid fa-bell"></i> Alert
            </a>
        </li>
        <li>
            <a href="{{ url('/control') }}"
               class="{{ request()->is('control') ? 'active' : '' }}">
                <i class="fa-solid fa-sliders"></i> Panel Kontrol
            </a>
        </li>
        </ul>

    <div class="navbar-right">
        <div class="status-badge">
            <div class="status-dot"></div>
            <span>System Online</span>
        </div>
        <div class="clock">
            <div id="clock-time" class="font-mono" style="font-size:1rem;color:white;font-weight:600;"></div>
            <div id="clock-date" style="font-size:0.65rem;color:#64748b;text-align:right;"></div>
        </div>
    </div>
</nav>

<!-- Content -->
<main class="main-content">
    @yield('content')
</main>

<script>
    // Clock
    function updateClock() {
        const now  = new Date();
        const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        document.getElementById('clock-time').textContent =
            now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
        document.getElementById('clock-date').textContent =
            days[now.getDay()] + ', ' + now.toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'});
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>

@stack('scripts')
</body>
</html>