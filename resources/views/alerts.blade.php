@extends('layouts.app')
@section('title', 'Statistik Alert Sistem')

@section('content')
<div class="stats-grid mb-6">
    <div class="stat-card red">
        <div class="stat-label">Total Gelombang Primer (P-Wave)</div>
        <div class="stat-value text-red" id="stat-pwave">0</div>
        <div class="stat-sub">Deteksi Awal Mikrokontroler</div>
    </div>
    <div class="stat-card orange" style="background: rgba(15,23,42,0.6); border: 1px solid rgba(245,158,11,0.2); border-radius: 12px; padding: 1.5rem; position: relative;">
        <div class="stat-label" style="font-size: 0.85rem; color: #94a3b8; font-weight: 600;">Total Gelombang Sekunder (S-Wave)</div>
        <div class="stat-value text-yellow" id="stat-swave" style="font-size: 2.25rem; font-weight: 700; color: #f59e0b; margin: 0.5rem 0;">0</div>
        <div class="stat-sub" style="font-size: 0.75rem; color: #64748b;">Guncangan Utama Merusak</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Status Utama Gempa (Earthquake)</div>
        <div class="stat-value text-red" id="stat-quake">0</div>
        <div class="stat-sub">Rasio STA/LTA Konfirmasi Krisis</div>
    </div>
</div>

<div class="glass-panel p-6">
    <h3 class="font-semibold text-lg mb-4 text-white">
        <i class="fa-solid fa-clock-history text-red"></i> Garis Waktu Riwayat Pemicu Sistem (Timeline)
    </h3>
    <div id="alert-timeline-container" style="display: flex; flex-direction: column; gap: 1rem;">
        </div>
</div>
@endsection

@push('scripts')
<script>
function renderAlertUI(stats, logs) {
    // 1. Update Jumlah Kartu Atas
    document.getElementById('stat-pwave').innerText = stats['P-WAVE'] || 0;
    document.getElementById('stat-swave').innerText = stats['S-WAVE'] || 0;
    document.getElementById('stat-quake').innerText = (stats['EARTHQUAKE'] || 0) + (stats['GEMPA'] || 0);

    // 2. Update Susunan Kotak Timeline Bawah
    const container = document.getElementById('alert-timeline-container');
    if (logs.length === 0) {
        container.innerHTML = `<div style="padding: 2rem; text-align: center; color: #64748b;">Tidak ada riwayat pemicu aktif dalam memori.</div>`;
        return;
    }

    container.innerHTML = logs.map(log => {
        let dateObj = log.time ? new Date(log.time) : new Date();
        let status = String(log.status || 'ALERT').toUpperCase();
        return `
            <div style="background: rgba(15,23,42,0.4); border-left: 4px solid #ef4444; padding: 1rem; border-radius: 4px; display: flex; justify-between; align-items: center;">
                <div>
                    <div style="font-weight: bold; color: #fff; font-size: 0.95rem;">SISTEM TERPICU: STATUS <span style="color: #ef4444;">${status}</span></div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">ID Alat: ${log.sensor_id || 'NODE-01'} | Posisi: Kampus Politeknik Negeri Jember</div>
                </div>
                <div style="text-align: right; font-family: 'JetBrains Mono', monospace;">
                    <div style="font-weight: bold; color: #fff; font-size: 1.1rem;">${parseFloat(log.stalta_ratio || 0).toFixed(2)} <span style="font-size:0.75rem; color:#64748b;">Ratio</span></div>
                    <div style="font-size: 0.7rem; color: #64748b; margin-top: 0.2rem;">${dateObj.toLocaleTimeString('id-ID')}</div>
                </div>
            </div>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', async function () {
    // 🛡️ AMBIL DATA DARI MEMORI BERSAMA LOCALSTORAGE CACHE AWAL
    let alertStats = JSON.parse(localStorage.getItem('eews_alert_stats')) || { 'P-WAVE':0, 'S-WAVE':0, 'EARTHQUAKE':0, 'GEMPA':0, 'EVALUASI':0 };
    let cachedLogs = JSON.parse(localStorage.getItem('eews_log_history')) || [];
    renderAlertUI(alertStats, cachedLogs);

    // 🌐 AMBIL DATA SINKRONISASI LANJUTAN DARI DATABASE INFLUXDB (HYDRATION JALUR BACKEND)
    try {
        const res = await fetch('/api/alert/stats');
        const json = await res.json();
        if (json.success && json.data) {
            // Bandingkan dan ambil nilai yang paling besar demi kestabilan data dev
            let s = json.data;
            if((s['P-WAVE'] || 0) >= alertStats['P-WAVE']) {
                alertStats = s;
                localStorage.setItem('eews_alert_stats', JSON.stringify(alertStats));
                renderAlertUI(alertStats, cachedLogs);
            }
        }
    } catch (err) { console.warn(err); }

    // 📡 REAL-TIME OMNI-SYNCHRONIZER LISTENER WEBSOCKET
    if (window.Echo) {
        window.Echo.channel('seismic')
            .listen('.data.received', (e) => {
                const statusStr = String(e.status || '').toUpperCase();

                // Selalu amankan status telemetri dasar & grafik tab sebelah agar tetap Sinkron
                localStorage.setItem('eews_last_telemetry', JSON.stringify(e));
                
                let localChartData = JSON.parse(localStorage.getItem('eews_chart_data')) || [];
                localChartData.push({ deviation: parseFloat(e.deviation) || 0, stalta_ratio: parseFloat(e.stalta_ratio) || 0 });
                if (localChartData.length > 50) localChartData.shift();
                localStorage.setItem('eews_chart_data', JSON.stringify(localChartData));

                // JIKA TERJADI GEMPA/ANOMALI (NON-IDLE)
                if (statusStr !== 'IDLE' && statusStr !== 'AMAN' && statusStr !== 'RESET') {
                    // 1. Naikkan Angka Counter Statistik di LocalStorage
                    let currentStats = JSON.parse(localStorage.getItem('eews_alert_stats')) || { 'P-WAVE':0, 'S-WAVE':0, 'EARTHQUAKE':0, 'GEMPA':0, 'EVALUASI':0 };
                    if (currentStats[statusStr] !== undefined) {
                        currentStats[statusStr]++;
                    }
                    localStorage.setItem('eews_alert_stats', JSON.stringify(currentStats));

                    // 2. Tambahkan Ke Riwayat Log Bersama
                    let currentLogs = JSON.parse(localStorage.getItem('eews_log_history')) || [];
                    currentLogs.unshift(e);
                    if (currentLogs.length > 50) currentLogs.pop();
                    localStorage.setItem('eews_log_history', JSON.stringify(currentLogs));

                    // 4. Naikkan Counter Kartu Utama Merah di Tab Dashboard Depan
                    let currentCount = parseInt(localStorage.getItem('eews_today_event_count')) || 0;
                    localStorage.setItem('eews_today_event_count', currentCount + 1);

                    // Render Pembaruan Instan di Layar Alert
                    renderAlertUI(currentStats, currentLogs);
                }
            });
    }
});
</script>
@endpush