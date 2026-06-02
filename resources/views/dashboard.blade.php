@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-seismo-accent/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-150"></div>
            <div class="relative">
                <p class="text-slate-400 text-sm font-medium">Total Node Sensor</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-white">{{ $stats['total_nodes'] }}</span>
                    <span class="text-xs text-seismo-accent">ESP32 + MPU6050</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-sm">
                    <span class="w-2 h-2 bg-safe rounded-full"></span>
                    <span class="text-slate-400">{{ $stats['online_nodes'] }} Online</span>
                </div>
            </div>
            <i class="fa-solid fa-network-wired absolute bottom-4 right-4 text-4xl text-slate-700"></i>
        </div>

        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-warning/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-150"></div>
            <div class="relative">
                <p class="text-slate-400 text-sm font-medium">Event Gempa Hari Ini</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-white">{{ $stats['today_events'] }}</span>
                    <span class="text-xs text-warning">deteksi</span>
                </div>
                <div class="mt-4 text-sm text-slate-400">
                    @if($stats['last_earthquake'])
                        Terakhir: {{ $stats['last_earthquake']->recorded_at->diffForHumans() }}
                    @else
                        Belum ada event
                    @endif
                </div>
            </div>
            <i class="fa-solid fa-house-crack absolute bottom-4 right-4 text-4xl text-slate-700"></i>
        </div>

        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-danger/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-150"></div>
            <div class="relative">
                <p class="text-slate-400 text-sm font-medium">Status Sistem</p>
                <div class="mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-safe/20 text-safe">
                        <span class="w-2 h-2 bg-safe rounded-full mr-2 animate-pulse"></span>
                        Monitoring Aktif
                    </span>
                </div>
                <div class="mt-4 text-sm text-slate-400">
                    STA/LTA & TinyML Ready
                </div>
            </div>
            <i class="fa-solid fa-shield-halved absolute bottom-4 right-4 text-4xl text-slate-700"></i>
        </div>

        <div class="glass-panel rounded-xl p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-150"></div>
            <div class="relative">
                <p class="text-slate-400 text-sm font-medium">Konektivitas</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-white">MQTT</span>
                </div>
                <div class="mt-4 text-sm text-slate-400">
                    Mosquitto Broker • Node-RED • WebSocket
                </div>
            </div>
            <i class="fa-solid fa-cloud absolute bottom-4 right-4 text-4xl text-slate-700"></i>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Real-time Seismograph Preview --}}
        <div class="lg:col-span-2 glass-panel rounded-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fa-solid fa-wave-square text-seismo-accent"></i>
                    Real-Time Seismograf
                </h2>
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-danger opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-danger"></span>
                    </span>
                    <span class="text-xs text-danger font-mono">LIVE</span>
                </div>
            </div>
            
            <div class="relative h-64 seismograph-grid rounded-lg border border-slate-700 overflow-hidden">
                <canvas id="seismo-preview" class="w-full h-full"></canvas>
                <div class="absolute top-2 right-2 flex gap-1">
                    <div class="wave-bar h-4"></div>
                    <div class="wave-bar h-6"></div>
                    <div class="wave-bar h-3"></div>
                    <div class="wave-bar h-8"></div>
                    <div class="wave-bar h-5"></div>
                </div>
            </div>
            
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div class="bg-slate-800/50 rounded-lg p-3">
                    <p class="text-xs text-slate-400">Accel X</p>
                    <p class="text-lg font-mono font-bold text-seismo-accent" id="val-accel-x">0.00</p>
                </div>
                <div class="bg-slate-800/50 rounded-lg p-3">
                    <p class="text-xs text-slate-400">Accel Y</p>
                    <p class="text-lg font-mono font-bold text-seismo-accent" id="val-accel-y">0.00</p>
                </div>
                <div class="bg-slate-800/50 rounded-lg p-3">
                    <p class="text-xs text-slate-400">Accel Z</p>
                    <p class="text-lg font-mono font-bold text-seismo-accent" id="val-accel-z">0.00</p>
                </div>
            </div>
        </div>

        {{-- Active Alerts Feed --}}
        <div class="glass-panel rounded-xl p-6">
            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-bell text-warning"></i>
                Alert Aktif
            </h2>
            
            <div class="space-y-3 max-h-80 overflow-y-auto pr-2" id="alerts-feed">
                @forelse($recentAlerts as $alert)
                <div class="bg-slate-800/50 rounded-lg p-4 border-l-4 border-{{ $alert->alert_color }}-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $alert->alert_color }}-500/20 text-{{ $alert->alert_color }}-400">
                                {{ strtoupper($alert->alert_level) }}
                            </span>
                            <p class="mt-1 text-sm font-medium text-white">{{ $alert->sensorNode->name ?? $alert->node_id }}</p>
                            <p class="text-xs text-slate-400 mt-1">
                                Mag: {{ $alert->magnitude }} | STA/LTA: {{ number_format($alert->sta_lta_ratio, 2) }}
                            </p>
                        </div>
                        <span class="text-xs text-slate-500">{{ $alert->recorded_at->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-slate-500">
                    <i class="fa-solid fa-check-circle text-4xl mb-2 text-safe"></i>
                    <p>Tidak ada alert aktif</p>
                    <p class="text-xs mt-1">Semua sensor dalam kondisi aman</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Node Status Grid --}}
    <div class="glass-panel rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-microchip text-seismo-accent"></i>
            Status Node Sensor
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($nodes as $node)
            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700 hover:border-seismo-accent/50 transition-colors">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center">
                            <i class="fa-solid fa-wifi {{ $node->isOnline() ? 'text-safe' : 'text-slate-500' }}"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-white">{{ $node->name }}</h3>
                            <p class="text-xs text-slate-400">{{ $node->location }}</p>
                        </div>
                    </div>
                    <span class="w-2.5 h-2.5 rounded-full {{ $node->isOnline() ? 'bg-safe node-status-dot' : 'bg-slate-600' }}"></span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-slate-900/50 rounded p-2">
                        <p class="text-slate-500">MPU6050</p>
                        <p class="text-safe font-mono">{{ $node->mpu6050_status ? 'OK' : 'ERR' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded p-2">
                        <p class="text-slate-500">HC-SR04</p>
                        <p class="text-safe font-mono">{{ $node->hc_sr04_status ? 'OK' : 'ERR' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded p-2">
                        <p class="text-slate-500">ESP-NOW</p>
                        <p class="text-seismo-accent font-mono">{{ $node->esp_now_signal ?? 'N/A' }} dBm</p>
                    </div>
                    <div class="bg-slate-900/50 rounded p-2">
                        <p class="text-slate-500">Baterai</p>
                        <p class="text-warning font-mono">{{ $node->battery_level ?? 'N/A' }}%</p>
                    </div>
                </div>
                
                @if($node->telemetries->first())
                @php $last = $node->telemetries->first(); @endphp
                <div class="mt-3 pt-3 border-t border-slate-700">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Last reading:</span>
                        <span class="text-slate-300 font-mono">{{ $last->recorded_at->diffForHumans() }}</span>
                    </div>
                    <div class="mt-1 flex gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-700 text-slate-300">
                            X: {{ number_format($last->accel_x, 2) }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-700 text-slate-300">
                            Y: {{ number_format($last->accel_y, 2) }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-700 text-slate-300">
                            Z: {{ number_format($last->accel_z, 2) }}
                        </span>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- System Architecture Diagram --}}
    <div class="glass-panel rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-diagram-project text-seismo-accent"></i>
            Arsitektur Sistem
        </h2>
        
        <div class="flex flex-wrap justify-center items-center gap-4 text-sm">
            <div class="bg-slate-800 rounded-lg p-4 text-center border border-slate-600 w-40">
                <i class="fa-solid fa-microchip text-2xl text-seismo-accent mb-2"></i>
                <p class="font-medium text-white">Node Sensor</p>
                <p class="text-xs text-slate-400 mt-1">ESP32 + MPU6050 + HC-SR04</p>
                <p class="text-[10px] text-seismo-accent mt-1">STA/LTA + TinyML</p>
            </div>
            
            <i class="fa-solid fa-arrow-right text-slate-600"></i>
            
            <div class="bg-slate-800 rounded-lg p-4 text-center border border-slate-600 w-40">
                <i class="fa-solid fa-tower-broadcast text-2xl text-warning mb-2"></i>
                <p class="font-medium text-white">Node Gateway</p>
                <p class="text-xs text-slate-400 mt-1">ESP-NOW + WiFi</p>
                <p class="text-[10px] text-warning mt-1">JSON Payload</p>
            </div>
            
            <i class="fa-solid fa-arrow-right text-slate-600"></i>
            
            <div class="bg-slate-800 rounded-lg p-4 text-center border border-slate-600 w-40">
                <i class="fa-solid fa-cloud text-2xl text-purple-400 mb-2"></i>
                <p class="font-medium text-white">MQTT Broker</p>
                <p class="text-xs text-slate-400 mt-1">Mosquitto</p>
                <p class="text-[10px] text-purple-400 mt-1">sensor/telemetri</p>
            </div>
            
            <i class="fa-solid fa-arrow-right text-slate-600"></i>
            
            <div class="bg-slate-800 rounded-lg p-4 text-center border border-slate-600 w-40">
                <i class="fa-solid fa-server text-2xl text-blue-400 mb-2"></i>
                <p class="font-medium text-white">Node-RED</p>
                <p class="text-xs text-slate-400 mt-1">Middleware + WebSocket</p>
                <p class="text-[10px] text-blue-400 mt-1">ws-gempa</p>
            </div>
            
            <i class="fa-solid fa-arrow-right text-slate-600"></i>
            
            <div class="bg-slate-800 rounded-lg p-4 text-center border border-seismo-accent w-40 ring-2 ring-seismo-accent/20">
                <i class="fa-brands fa-laravel text-2xl text-danger mb-2"></i>
                <p class="font-medium text-white">Laravel App</p>
                <p class="text-xs text-slate-400 mt-1">Web UI + API</p>
                <p class="text-[10px] text-danger mt-1">Real-time Monitor</p>
            </div>
            
            <i class="fa-solid fa-arrow-right text-slate-600"></i>
            
            <div class="bg-slate-800 rounded-lg p-4 text-center border border-slate-600 w-40">
                <i class="fa-solid fa-database text-2xl text-green-400 mb-2"></i>
                <p class="font-medium text-white">InfluxDB</p>
                <p class="text-xs text-slate-400 mt-1">Time-Series DB</p>
                <p class="text-[10px] text-green-400 mt-1">History Log</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Real-time seismograph chart
    const ctx = document.getElementById('seismo-preview').getContext('2d');
    const maxDataPoints = 100;
    const dataPoints = {
        x: Array(maxDataPoints).fill(0),
        y: Array(maxDataPoints).fill(0),
        z: Array(maxDataPoints).fill(0)
    };

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array(maxDataPoints).fill(''),
            datasets: [
                {
                    label: 'Accel X',
                    data: dataPoints.x,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: true
                },
                {
                    label: 'Accel Y',
                    data: dataPoints.y,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: true
                },
                {
                    label: 'Accel Z',
                    data: dataPoints.z,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    labels: { color: '#94a3b8', font: { size: 10 } }
                }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)'
                    },
                    ticks: {
                        color: '#64748b',
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // Simulate real-time data (replace with actual WebSocket/MQTT connection)
    async function updateSeismograph() {
        try {
            const response = await fetch('/api/telemetry/latest?limit=1');
            const data = await response.json();
            
            if (Array.isArray(data) && data.length > 0) {
                const latest = data[0];
                
                // Update values
                document.getElementById('val-accel-x').textContent = latest.accel_x?.toFixed(2) || '0.00';
                document.getElementById('val-accel-y').textContent = latest.accel_y?.toFixed(2) || '0.00';
                document.getElementById('val-accel-z').textContent = latest.accel_z?.toFixed(2) || '0.00';

                // Shift and push new data
                dataPoints.x.shift();
                dataPoints.x.push(latest.accel_x || 0);
                dataPoints.y.shift();
                dataPoints.y.push(latest.accel_y || 0);
                dataPoints.z.shift();
                dataPoints.z.push(latest.accel_z || 0);

                chart.update('none');
            } else {
                // Demo mode - generate synthetic wave
                const t = Date.now() / 1000;
                const noise = () => (Math.random() - 0.5) * 0.1;
                
                dataPoints.x.shift();
                dataPoints.x.push(Math.sin(t * 2) * 0.5 + noise());
                dataPoints.y.shift();
                dataPoints.y.push(Math.cos(t * 1.5) * 0.3 + noise());
                dataPoints.z.shift();
                dataPoints.z.push(9.8 + Math.sin(t * 0.5) * 0.2 + noise());

                chart.update('none');
            }
        } catch (e) {
            // Demo mode fallback
            const t = Date.now() / 1000;
            const noise = () => (Math.random() - 0.5) * 0.1;
            
            dataPoints.x.shift();
            dataPoints.x.push(Math.sin(t * 2) * 0.5 + noise());
            dataPoints.y.shift();
            dataPoints.y.push(Math.cos(t * 1.5) * 0.3 + noise());
            dataPoints.z.shift();
            dataPoints.z.push(9.8 + Math.sin(t * 0.5) * 0.2 + noise());

            document.getElementById('val-accel-x').textContent = dataPoints.x[maxDataPoints-1].toFixed(2);
            document.getElementById('val-accel-y').textContent = dataPoints.y[maxDataPoints-1].toFixed(2);
            document.getElementById('val-accel-z').textContent = dataPoints.z[maxDataPoints-1].toFixed(2);

            chart.update('none');
        }
    }

    setInterval(updateSeismograph, 100);
</script>
@endpush
@endsection