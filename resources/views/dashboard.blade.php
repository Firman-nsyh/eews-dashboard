@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card cyan">
        <i class="fa-solid fa-network-wired stat-icon"></i>
        <div class="stat-label">Total Node Sensor</div>
        <div class="stat-value text-cyan">1</div>
        <div class="stat-sub"><i class="fa-solid fa-microchip"></i> ESP32-CAM + MPU6050</div>
    </div>

    <div class="stat-card red">
        <i class="fa-solid fa-house-crack stat-icon"></i>
        <div class="stat-label">Event Gempa Hari Ini</div>
        <div class="stat-value text-red" id="today-events">0</div>
        <div class="stat-sub">Deteksi terkonfirmasi</div>
    </div>

    <div class="stat-card green">
        <i class="fa-solid fa-shield-halved stat-icon"></i>
        <div class="stat-label">Status Sistem</div>
        <div class="stat-value text-green" style="font-size: 1.2rem; margin-top:0.5rem;">
            <div class="badge badge-aman" id="system-status">
                <i class="fa-solid fa-check-circle"></i> MONITORING AKTIF
            </div>
        </div>
        <div class="stat-sub mt-2">Algoritma STA/LTA Ready</div>
    </div>

    <div class="stat-card blue">
        <i class="fa-solid fa-cloud stat-icon"></i>
        <div class="stat-label">Konektivitas</div>
        <div class="stat-value text-blue">MQTT</div>
        <div class="stat-sub">ESP-NOW &rarr; WiFi &rarr; Mosquitto</div>
    </div>
</div>

<div class="grid-2">
    <div class="glass-panel p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg">
                <i class="fa-solid fa-wave-square text-cyan"></i> Sinyal Terakhir
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate font-mono" id="last-update-time">-</span>
                <span class="badge badge-aman" id="live-indicator">
                    <span id="ws-dot" style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;margin-right:4px;"></span>
                    LIVE
                </span>
            </div>
        </div>

        <div class="grid-2 mt-4">
            <div>
                <div class="text-xs text-slate mb-1">Rasio STA/LTA (Ambang: 2.0)</div>
                <div class="text-2xl font-mono font-bold text-white" id="dash-stalta">0.000</div>
                <div class="stalta-bar-container mt-2">
                    <div class="stalta-bar-track">
                        <div class="stalta-bar-fill" id="dash-stalta-bar" style="width:0%"></div>
                        <div class="stalta-threshold-line"></div>
                    </div>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate mb-1">Deviasi Getaran</div>
                <div class="text-2xl font-mono font-bold text-white">
                    <span id="dash-dev-val">0.000</span>
                    <span class="text-sm text-slate">g</span>
                </div>
                <div class="text-xs text-slate mt-2">
                    STA: <span class="font-mono text-cyan" id="dash-sta">0.000000</span> |
                    LTA: <span class="font-mono text-green" id="dash-lta">0.000000</span>
                </div>
            </div>
        </div>

        <div class="mt-4" style="padding-top:1rem;border-top:1px solid rgba(148,163,184,0.1)">
            <div class="text-xs text-slate mb-1">Sensor ID</div>
            <div class="font-mono text-sm text-white" id="dash-sensor-id">-</div>
        </div>
    </div>

    <div class="glass-panel p-6" style="position:relative; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; min-height:200px;">
        <h3 class="font-semibold text-lg w-full text-left" style="position:absolute;top:1.5rem;left:1.5rem;">
            <i class="fa-solid fa-bell text-yellow"></i> Alert Aktif
        </h3>
        <div id="alert-display">
            <i class="fa-solid fa-circle-check text-green mb-2" style="font-size:3rem;"></i>
            <div class="text-lg font-semibold text-white">Tidak ada alert aktif</div>
            <div class="text-sm text-slate">Semua sensor dalam kondisi aman</div>
        </div>
    </div>
</div>

<div class="glass-panel p-6 mt-4">
    <h3 class="font-semibold text-lg mb-4">
        <i class="fa-solid fa-diagram-project text-cyan"></i> Arsitektur Sistem
    </h3>
    <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;flex-wrap:wrap;">
        @php
        $nodes = [
            ['icon'=>'fa-microchip',      'label'=>'Node Sensor',   'sub'=>'ESP32-CAM + MPU6050',    'color'=>'#06b6d4'],
            ['icon'=>'fa-tower-broadcast', 'label'=>'Node Gateway',  'sub'=>'ESP-NOW + WiFi',         'color'=>'#8b5cf6'],
            ['icon'=>'fa-cloud',           'label'=>'MQTT Broker',   'sub'=>'Mosquitto VM1',          'color'=>'#ec4899'],
            ['icon'=>'fa-circle-nodes',    'label'=>'Node-RED',      'sub'=>'Middleware VM2',         'color'=>'#f59e0b'],
            ['icon'=>'fa-brands fa-laravel','label'=>'Laravel App',  'sub'=>'Web UI + API',           'color'=>'#ef4444'],
            ['icon'=>'fa-database',        'label'=>'InfluxDB',      'sub'=>'Time-Series DB VM3',     'color'=>'#10b981'],
        ];
        @endphp

        @foreach($nodes as $i => $node)
            <div style="text-align:center;padding:1rem;background:rgba(15,23,42,0.8);border:1px solid rgba(148,163,184,0.12);border-radius:10px;min-width:110px;">
                <i class="fa-solid {{ $node['icon'] }}" style="font-size:1.5rem;color:{{ $node['color'] }};margin-bottom:0.5rem;display:block;"></i>
                <div style="font-size:0.75rem;font-weight:600;color:white;">{{ $node['label'] }}</div>
                <div style="font-size:0.65rem;color:#64748b;margin-top:0.2rem;">{{ $node['sub'] }}</div>
            </div>
            @if(!$loop->last)
                <i class="fa-solid fa-arrow-right" style="color:#334155;font-size:0.875rem;"></i>
            @endif
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateDashboard(e) {
    const ratio = parseFloat(e.stalta_ratio) || 0;
    const dev   = parseFloat(e.deviation)    || 0;
    const sta   = parseFloat(e.sta_value)    || 0;
    const lta   = parseFloat(e.lta_value)    || 0;

    document.getElementById('dash-stalta').innerText  = ratio.toFixed(3);
    document.getElementById('dash-dev-val').innerText = dev.toFixed(4);
    document.getElementById('dash-sta').innerText     = sta.toFixed(6);
    document.getElementById('dash-lta').innerText     = lta.toFixed(6);
    document.getElementById('dash-sensor-id').innerText = e.sensor_id || '-';
    
    let dateObj = e.time ? new Date(e.time) : (e.timestamp ? new Date(e.timestamp) : new Date());
    document.getElementById('last-update-time').innerText = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    const pct = Math.min((ratio / 3.0) * 100, 100);
    document.getElementById('dash-stalta-bar').style.width = pct + '%';
    document.getElementById('dash-stalta-bar').style.background = ratio >= 2.0 ? '#ef4444' : (ratio >= 1.5 ? '#f59e0b' : 'linear-gradient(90deg, #10b981, #f59e0b)');

    const alertDisplay = document.getElementById('alert-display');
    const sysStatus    = document.getElementById('system-status');
    const status       = String(e.status || '').toUpperCase();

    if (status === 'GEMPA' || status === 'EARTHQUAKE' || status === 'P-WAVE' || status === 'S-WAVE' || ratio >= 2.0) {
        alertDisplay.innerHTML = `
            <i class="fa-solid fa-triangle-exclamation text-red mb-2" style="font-size:3rem;animation:pulse 1s infinite;"></i>
            <div class="text-lg font-semibold text-red">BAHAYA: GEMPA TERDETEKSI!</div>
            <div class="text-sm text-slate">STATUS: ${status} | STA/LTA: ${ratio.toFixed(3)}</div>
        `;
        sysStatus.className   = 'badge badge-gempa';
        sysStatus.innerHTML   = '<i class="fa-solid fa-xmark-circle"></i> SISTEM TERPICU';
    } else if (status === 'SIAGA' || status === 'EVALUASI' || ratio >= 1.5) {
        alertDisplay.innerHTML = `
            <i class="fa-solid fa-triangle-exclamation text-yellow mb-2" style="font-size:3rem;"></i>
            <div class="text-lg font-semibold text-yellow">SIAGA: Menganalisis Getaran</div>
            <div class="text-sm text-slate">STA/LTA: ${ratio.toFixed(3)}</div>
        `;
        sysStatus.className = 'badge badge-siaga';
        sysStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> SIAGA';
    } else {
        alertDisplay.innerHTML = `
            <i class="fa-solid fa-circle-check text-green mb-2" style="font-size:3rem;"></i>
            <div class="text-lg font-semibold text-white">Tidak ada alert aktif</div>
            <div class="text-sm text-slate">Semua sensor dalam kondisi aman</div>
        `;
        sysStatus.className = 'badge badge-aman';
        sysStatus.innerHTML = '<i class="fa-solid fa-check-circle"></i> MONITORING AKTIF';
    }
}

document.addEventListener('DOMContentLoaded', async function () {

    // =======================================================
    // 🌟 FITUR BARU: AUTO-RESET MEMORI SAAT BERGANTI HARI
    // =======================================================
    const todayDate = new Date().toLocaleDateString('id-ID'); // Contoh: "10/6/2026"
    const savedDate = localStorage.getItem('eews_current_date');

    if (savedDate !== todayDate) {
        // Jika hari berganti, reset semua memori counter kembali ke 0
        localStorage.setItem('eews_today_event_count', '0');
        localStorage.setItem('eews_current_date', todayDate);
        
        // Reset juga memori statistik di tab Alert agar sinkron
        localStorage.setItem('eews_alert_stats', JSON.stringify({ 
            'P-WAVE':0, 'S-WAVE':0, 'EARTHQUAKE':0, 'GEMPA':0, 'EVALUASI':0 
        }));
    }

    // Load Telemetri Terakhir
    const savedTelemetry = localStorage.getItem('eews_last_telemetry');
    if (savedTelemetry) {
        try { updateDashboard(JSON.parse(savedTelemetry)); } catch(e) {}
    }

    // Load Counter Instan (Anti Reset 0)
    let savedEventCount = localStorage.getItem('eews_today_event_count') || 0;
    document.getElementById('today-events').innerText = savedEventCount;

    // Fetch API Data Live
    try {
        const res  = await fetch('/api/telemetry/latest');
        const json = await res.json();
        if (json.success && json.data && json.data.length > 0) {
            updateDashboard(json.data[0]); 
            localStorage.setItem('eews_last_telemetry', JSON.stringify(json.data[0]));
        }
    } catch (err) { console.warn(err); }

    // Fetch API Counter
    try {
        const resStats  = await fetch('/api/alert/stats');
        const jsonStats = await resStats.json();
        if (jsonStats.success && jsonStats.data) {
            const d = jsonStats.data;
            const serverTotal = (d.EARTHQUAKE || 0) + (d.GEMPA || 0) + (d['P-WAVE'] || 0) + (d['S-WAVE'] || 0);
            if (serverTotal > parseInt(savedEventCount)) {
                document.getElementById('today-events').innerText = serverTotal;
                localStorage.setItem('eews_today_event_count', serverTotal);
            }
        }
    } catch (err) { console.warn(err); }

    // WebSocket Listener
    if (window.Echo) {
        window.Echo.channel('seismic')
            .listen('.data.received', (e) => {
                localStorage.setItem('eews_last_telemetry', JSON.stringify(e));
                updateDashboard(e);

                // 🌟 PROTEKSI SINKRONISASI COUNTER 🌟
                const statusStr = String(e.status || '').toUpperCase();
                if (statusStr === 'EARTHQUAKE' || statusStr === 'GEMPA' || statusStr === 'P-WAVE' || statusStr === 'S-WAVE') {
                    let currentCount = parseInt(localStorage.getItem('eews_today_event_count')) || 0;
                    let newCount = currentCount + 1;
                    document.getElementById('today-events').innerText = newCount;
                    localStorage.setItem('eews_today_event_count', newCount);
                }

                // 🌟 PROTEKSI SINKRONISASI GRAFIK (Meskipun Tab Seismograf Sedang Ditutup) 🌟
                let localChartData = JSON.parse(localStorage.getItem('eews_chart_data')) || [];
                localChartData.push({
                    deviation: parseFloat(e.deviation) || 0,
                    stalta_ratio: parseFloat(e.stalta_ratio) || 0
                });
                if (localChartData.length > 50) localChartData.shift();
                localStorage.setItem('eews_chart_data', JSON.stringify(localChartData));
            });
    }
});
</script>
@endpush