@extends('layouts.app')
@section('title', 'Panel Kontrol & Pemeliharaan')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-bold text-white"><i class="fa-solid fa-sliders text-cyan"></i> Panel Kontrol Hardware & Database</h2>
    <div class="text-sm text-slate mt-1">Pusat kendali jarak jauh (Reverse Control) gateway ESP32-C6 dan manajemen InfluxDB.</div>
</div>

<div class="grid-2">
    <div class="glass-panel p-6">
        <h3 class="font-semibold text-lg border-b border-slate-700 pb-3 mb-4">
            <i class="fa-solid fa-microchip text-yellow"></i> Kendali Node Gateway
        </h3>

        <div class="mb-6 p-4" style="background: rgba(15,23,42,0.4); border-radius: 8px;">
            <div class="font-semibold text-white mb-1"><i class="fa-solid fa-bullhorn text-red"></i> Uji Sirine Manual</div>
            <div class="text-xs text-slate mb-3">Picu buzzer dan indikator LED darurat dalam durasi tertentu.</div>
            <div class="flex items-center gap-2">
                <input type="number" id="siren-duration" value="5" min="1" max="60" class="input-theme" style="width: 80px; padding: 0.4rem; border-radius: 4px; background: #1e293b; color: white; border: 1px solid #334155;" title="Durasi dalam detik">
                <span class="text-sm text-slate">Detik</span>
                <button onclick="sendControl('SIREN')" class="btn" style="padding: 0.4rem 1rem; border-radius: 4px; background: #ef4444; color: white; margin-left: auto; font-weight: bold;">
                    <i class="fa-solid fa-play"></i> Bunyikan
                </button>
            </div>
        </div>

        <div class="mb-6 p-4" style="background: rgba(15,23,42,0.4); border-radius: 8px;">
            <div class="font-semibold text-white mb-1"><i class="fa-solid fa-toggle-on text-cyan"></i> Kendali Relay Darurat</div>
            <div class="text-xs text-slate mb-3">Kunci (Latching) status relay untuk memutus listrik / membuka pintu darurat.</div>
            <div class="flex gap-2">
                <button onclick="sendControl('RELAY', 1)" class="btn" style="flex:1; padding: 0.5rem; border-radius: 4px; background: #10b981; color: white; font-weight: bold;">
                    <i class="fa-solid fa-power-off"></i> Turn ON
                </button>
                <button onclick="sendControl('RELAY', 0)" class="btn" style="flex:1; padding: 0.5rem; border-radius: 4px; background: #475569; color: white; font-weight: bold;">
                    <i class="fa-solid fa-xmark"></i> Turn OFF
                </button>
            </div>
        </div>

        <div class="p-4" style="background: rgba(15,23,42,0.4); border-radius: 8px; border: 1px solid rgba(245,158,11,0.3);">
            <div class="font-semibold text-yellow mb-1"><i class="fa-solid fa-screwdriver-wrench"></i> Mode Kalibrasi Sensor</div>
            <div class="text-xs text-slate mb-3">Bungkam alarm fisik (Buzzer/Relay) pada Gateway selama proses pengujian MPU6050.</div>
            <div class="flex gap-2">
                <button onclick="sendControl('CALIB', 1)" class="btn" style="flex:1; padding: 0.5rem; border-radius: 4px; background: #f59e0b; color: white; font-weight: bold;">
                    <i class="fa-solid fa-lock"></i> Aktifkan Mode
                </button>
                <button onclick="sendControl('CALIB', 0)" class="btn" style="flex:1; padding: 0.5rem; border-radius: 4px; background: #475569; color: white; font-weight: bold;">
                    <i class="fa-solid fa-unlock"></i> Kembalikan Normal
                </button>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6">
        <h3 class="font-semibold text-lg border-b border-slate-700 pb-3 mb-4">
            <i class="fa-solid fa-database text-green"></i> Manajemen Database (InfluxDB)
        </h3>

        <div class="p-4" style="background: rgba(15,23,42,0.4); border-radius: 8px; border-left: 4px solid #ef4444;">
            <div class="font-semibold text-red mb-1"><i class="fa-solid fa-trash-can"></i> Hard Delete: Pembersih Data Kalibrasi</div>
            <div class="text-xs text-slate mb-4">
                Fitur ini akan menghapus data telemetri dan log secara permanen dari database berdasarkan rentang waktu. 
                Sangat berguna untuk membersihkan jejak anomali grafik pasca pengujian sensor.
            </div>
            
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-xs text-slate block mb-1">Mulai Waktu Pengujian:</label>
                    <input type="datetime-local" id="delete-start" class="input-theme" style="width: 100%; padding: 0.5rem; border-radius: 4px; background: #1e293b; color: white; border: 1px solid #334155;">
                </div>
                <div>
                    <label class="text-xs text-slate block mb-1">Akhir Waktu Pengujian:</label>
                    <input type="datetime-local" id="delete-end" class="input-theme" style="width: 100%; padding: 0.5rem; border-radius: 4px; background: #1e293b; color: white; border: 1px solid #334155;">
                </div>
                <button onclick="purgeData()" class="btn mt-2" style="width: 100%; padding: 0.6rem; border-radius: 4px; background: #ef4444; color: white; font-weight: bold; text-transform: uppercase;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Eksekusi Pembersihan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// =============================================
// FUNGSI 1: KIRIM KONTROL HARDWARE (MQTT)
// =============================================
async function sendControl(cmd, action = null) {
    let payload = { cmd: cmd };

    if (cmd === 'SIREN') {
        const seconds = parseInt(document.getElementById('siren-duration').value) || 5;
        payload.duration = seconds * 1000;
    } else if (cmd === 'RELAY' || cmd === 'CALIB') {
        payload.action = action; 
    }

    try {
        const response = await fetch('/api/hardware/control', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('✅ Berhasil: ' + result.message);
        } else {
            alert('❌ Gagal: ' + (result.error || 'Terjadi kesalahan jaringan'));
        }
    } catch (error) {
        alert('❌ Error Koneksi: ' + error.message);
    }
}

// =============================================
// FUNGSI 2: EKSEKUSI HARD DELETE DATABASE
// =============================================
async function purgeData() {
    const startStr = document.getElementById('delete-start').value;
    const endStr = document.getElementById('delete-end').value;

    if (!startStr || !endStr) {
        alert('⚠️ Peringatan: Harap isi rentang waktu Mulai dan Akhir dengan lengkap!');
        return;
    }

    const startTime = startStr.replace('T', ' ') + ':00';
    const endTime = endStr.replace('T', ' ') + ':00';

    const confirmDelete = confirm(`PERINGATAN KRITIS!\n\nAnda yakin ingin menghapus data InfluxDB secara permanen dari:\n${startTime}\nsampai\n${endTime} ?\n\nTindakan ini tidak bisa dibatalkan.`);
    
    if (!confirmDelete) return;

    try {
        const response = await fetch('/api/database/purge-calibration', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                start_time: startTime,
                end_time: endTime
            })
        });

        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('✅ ' + result.message);
            
            // Bersihkan cache browser & redirect ke Dashboard agar Web bersih total
            localStorage.removeItem('eews_log_history');
            localStorage.removeItem('eews_today_event_count');
            localStorage.removeItem('eews_alert_stats');
            localStorage.removeItem('eews_chart_data');
            window.location.href = '{{ route("dashboard") }}';
            
        } else {
            // 🔥 Tangkap pesan error murni dari \Throwable Laravel
            const rincianError = result.error || result.message || 'Kesalahan Sistem Tidak Diketahui';
            alert('❌ Gagal menghapus data:\n' + rincianError);
        }
    } catch (error) {
        alert('❌ Error Koneksi Database: ' + error.message);
    }
}
</script>
@endpush