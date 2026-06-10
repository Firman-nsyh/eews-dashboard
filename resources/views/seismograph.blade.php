@extends('layouts.app')
@section('title', 'Seismograf Real-Time')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-white"><i class="fa-solid fa-wave-square text-cyan"></i> Seismograf Real-Time</h2>
        <div class="text-sm text-slate mt-1">Visualisasi data akselerometer MPU6050 (Deviasi) & STA/LTA Ratio</div>
    </div>
    <div class="badge badge-aman"><i class="fa-solid fa-circle-dot" style="animation: pulse 1s infinite;"></i> LIVE STREAM</div>
</div>

<div class="glass-panel p-6 mb-6">
    <div class="seismograph-container">
        <canvas id="liveChart"></canvas>
    </div>

    <div class="stalta-bar-container mt-6">
        <div class="flex justify-between text-xs font-semibold mb-2">
            <span class="text-slate">STA/LTA Ratio (Ambang Gempa: 2.0)</span>
            <span class="text-cyan font-mono" id="stalta-text">0.00</span>
        </div>
        <div class="stalta-bar-track">
            <div class="stalta-bar-fill" id="stalta-fill" style="width: 0%;"></div>
            <div class="stalta-threshold-line" style="left: 40%; background: #ef4444; width: 3px;"></div>
        </div>
        <div class="flex justify-between text-xs text-slate mt-1">
            <span>0.0</span>
            <span>Threshold: 2.0</span>
            <span>5.0+</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const ctx = document.getElementById('liveChart').getContext('2d');
    const maxDataPoints = 50; 

    // Muat data grafik dari shared memory localStorage
    let localChartData = JSON.parse(localStorage.getItem('eews_chart_data')) || [];
    
    // Konfigurasi awal Chart.js
    let labels = Array(maxDataPoints).fill('');
    let dataDeviation = Array(maxDataPoints).fill(0);

    // Isikan data chart yang sempat terekam di tab sebelah
    if(localChartData.length > 0) {
        dataDeviation = localChartData.map(d => d.deviation);
        while(dataDeviation.length < maxDataPoints) dataDeviation.unshift(0);
    }

    const liveChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Deviasi Getaran (g)',
                data: dataDeviation,
                borderColor: '#06b6d4',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.1,
                fill: true,
                backgroundColor: 'rgba(6, 182, 212, 0.1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: {
                y: { min: -0.1, max: 1.5, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { display: false } }
            },
            plugins: { legend: { labels: { color: '#e2e8f0', font: { family: 'JetBrains Mono' } } } }
        }
    });

    function updateStaltaUI(stalta) {
        document.getElementById('stalta-text').innerText = parseFloat(stalta).toFixed(2);
        let percentage = Math.min((stalta / 5.0) * 100, 100);
        const barFill = document.getElementById('stalta-fill');
        barFill.style.width = percentage + '%';
        barFill.style.background = stalta >= 2.0 ? '#ef4444' : 'linear-gradient(90deg, #10b981, #f59e0b)';
    }

    // Tampilkan nilai bar STA/LTA terakhir dari cache
    if(localChartData.length > 0) {
        updateStaltaUI(localChartData[localChartData.length - 1].stalta_ratio);
    }

    // Jalur Sinkronisasi Tambahan via API InfluxDB
    try {
        const res = await fetch('/api/seismograf/data');
        const json = await res.json();
        if (json.success && json.data.length > 0) {
            dataDeviation = json.data.map(d => parseFloat(d.deviation) || 0);
            while(dataDeviation.length < maxDataPoints) dataDeviation.unshift(0);
            liveChart.data.datasets[0].data = dataDeviation;
            liveChart.update();
            updateStaltaUI(json.data[json.data.length - 1].stalta_ratio);
        }
    } catch (err) { console.warn(err); }

    // WebSocket Real-Time Listener
    if (window.Echo) {
        window.Echo.channel('seismic')
            .listen('.data.received', (e) => {
                let devVal = parseFloat(e.deviation) || 0;
                
                // 1. Dorong pergerakan grafik
                liveChart.data.datasets[0].data.push(devVal);
                if (liveChart.data.datasets[0].data.length > maxDataPoints) {
                    liveChart.data.datasets[0].data.shift();
                }
                liveChart.update('none');
                updateStaltaUI(e.stalta_ratio);

                // 2. 🌟 SINKRONISASI SUNTIK MEMORI KE TAB DASHBOARD (Kunci Sukses) 🌟
                localStorage.setItem('eews_last_telemetry', JSON.stringify(e));

                let sharedChart = JSON.parse(localStorage.getItem('eews_chart_data')) || [];
                sharedChart.push({ deviation: devVal, stalta_ratio: parseFloat(e.stalta_ratio) || 0 });
                if (sharedChart.length > 50) sharedChart.shift();
                localStorage.setItem('eews_chart_data', JSON.stringify(sharedChart));

                // JIKA GEMPA TERJADI SAAT DI TAB GRAFIK -> IKUT BERSIAP MENAMBAH ANGKA DI DASHBOARD
                const statusStr = String(e.status || '').toUpperCase();
                if (statusStr === 'EARTHQUAKE' || statusStr === 'GEMPA' || statusStr === 'P-WAVE' || statusStr === 'S-WAVE') {
                    let currentCount = parseInt(localStorage.getItem('eews_today_event_count')) || 0;
                    localStorage.setItem('eews_today_event_count', currentCount + 1);
                }
            });
    }
});
</script>
@endpush