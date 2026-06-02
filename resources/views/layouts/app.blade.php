<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Earthquake Detection System') | SeismoGuard</title>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    {{-- Leaflet Map --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'seismo-dark': '#0f172a',
                        'seismo-card': '#1e293b',
                        'seismo-accent': '#06b6d4',
                        'danger': '#ef4444',
                        'warning': '#f59e0b',
                        'watch': '#f97316',
                        'safe': '#10b981'
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'shake': 'shake 0.5s ease-in-out',
                        'wave': 'wave 2s ease-in-out infinite'
                    },
                    keyframes: {
                        shake: {
                            '0%, 100%': { transform: 'translateX(0)' },
                            '25%': { transform: 'translateX(-5px)' },
                            '75%': { transform: 'translateX(5px)' }
                        },
                        wave: {
                            '0%': { transform: 'scaleY(1)' },
                            '50%': { transform: 'scaleY(1.5)' },
                            '100%': { transform: 'scaleY(1)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        
        .mono { font-family: 'JetBrains Mono', monospace; }
        
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .seismograph-grid {
            background-image: 
                linear-gradient(rgba(6, 182, 212, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6, 182, 212, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        .alert-banner {
            background: linear-gradient(90deg, #dc2626 0%, #991b1b 100%);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
        }
        
        .node-status-dot {
            box-shadow: 0 0 8px currentColor;
        }
        
        .wave-bar {
            display: inline-block;
            width: 3px;
            background: #06b6d4;
            margin: 0 1px;
            border-radius: 2px;
            animation: wave 1.2s ease-in-out infinite;
        }
        
        .wave-bar:nth-child(2) { animation-delay: 0.1s; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; }
        .wave-bar:nth-child(5) { animation-delay: 0.4s; }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
    
    @stack('styles')
</head>
<body class="min-h-screen bg-seismo-dark">
    {{-- Alert Banner --}}
    <div id="emergency-alert" class="hidden alert-banner text-white px-4 py-3 text-center font-bold animate-shake">
        <i class="fa-solid fa-triangle-exclamation mr-2"></i>
        <span id="alert-message">DETEKSI GEMPA BAHAYA! Segera evakuasi ke tempat aman!</span>
    </div>

    {{-- Navigation --}}
    <nav class="glass-panel sticky top-0 z-50 border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <div class="w-10 h-10 bg-seismo-accent rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-house-crack text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white tracking-tight">SeismoGuard</h1>
                            <p class="text-xs text-slate-400">Earthquake Early Warning System</p>
                        </div>
                    </div>
                    
                    <div class="hidden md:ml-8 md:flex md:space-x-1">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-seismo-accent/20 text-seismo-accent' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} transition-all">
                            <i class="fa-solid fa-gauge-high mr-1"></i> Dashboard
                        </a>
                        <a href="{{ route('seismograph') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('seismograph') ? 'bg-seismo-accent/20 text-seismo-accent' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} transition-all">
                            <i class="fa-solid fa-wave-square mr-1"></i> Seismograf
                        </a>
                        <a href="{{ route('nodes.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('nodes.*') ? 'bg-seismo-accent/20 text-seismo-accent' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} transition-all">
                            <i class="fa-solid fa-microchip mr-1"></i> Node Sensor
                        </a>
                        <a href="{{ route('logs.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('logs.*') ? 'bg-seismo-accent/20 text-seismo-accent' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} transition-all">
                            <i class="fa-solid fa-clock-rotate-left mr-1"></i> Log Data
                        </a>
                        <a href="{{ route('alerts') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('alerts') ? 'bg-seismo-accent/20 text-seismo-accent' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} transition-all relative">
                            <i class="fa-solid fa-bell mr-1"></i> Alert
                            <span id="alert-badge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-danger rounded-full text-[10px] flex items-center justify-center">!</span>
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-400">
                        <span class="w-2 h-2 bg-safe rounded-full node-status-dot animate-pulse"></span>
                        <span class="hidden sm:inline">System Online</span>
                    </div>
                    <div class="text-right hidden sm:block">
                        <div class="text-sm font-mono text-seismo-accent" id="live-clock">00:00:00</div>
                        <div class="text-xs text-slate-500" id="live-date">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-800 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-slate-500 text-sm">
            <p>SeismoGuard &copy; 2024 - Sistem Pendeteksi Gempa Berbasis IoT | ESP32 + MPU6050 + HC-SR04</p>
            <p class="mt-1 text-xs">Proyek Akhir Semester - Jalur: MQTT → Node-RED → InfluxDB → Laravel</p>
        </div>
    </footer>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('live-clock').textContent = now.toLocaleTimeString('id-ID', {hour12: false});
            document.getElementById('live-date').textContent = now.toLocaleDateString('id-ID', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Real-time data fetcher
        async function fetchLatestTelemetry() {
            try {
                const response = await fetch('/api/telemetry/latest');
                const data = await response.json();
                
                if (Array.isArray(data) && data.length > 0) {
                    const latest = data[0];
                    if (latest.alert_level === 'danger') {
                        showEmergencyAlert(latest);
                    }
                }
            } catch (e) {
                console.error('Fetch error:', e);
            }
        }

        function showEmergencyAlert(data) {
            const banner = document.getElementById('emergency-alert');
            const msg = document.getElementById('alert-message');
            msg.textContent = `GEMPA TERDETEKSI! Magnitudo: ${data.magnitude} | Node: ${data.node_id} | ${new Date(data.recorded_at).toLocaleTimeString('id-ID')}`;
            banner.classList.remove('hidden');
            
            // Play alert sound if browser allows
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZSA0PVanu8LdnGgU1k9n1unEiBC13yO/eizEIHWq+8+OZ');
            audio.play().catch(() => {});
        }

        // Poll every 3 seconds
        setInterval(fetchLatestTelemetry, 3000);

        // Auto-hide emergency alert after 30 seconds
        setInterval(() => {
            const banner = document.getElementById('emergency-alert');
            if (!banner.classList.contains('hidden')) {
                banner.classList.add('hidden');
            }
        }, 30000);
    </script>

    @stack('scripts')
</body>
</html>