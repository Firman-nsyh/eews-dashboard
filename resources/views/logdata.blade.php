@extends('layouts.app')
@section('title', 'Log Data Mentah')

@section('content')
<div class="glass-panel p-6">
    <div class="flex justify-between items-center mb-4 border-b border-slate-700 pb-4">
        <div>
            <h2 class="text-xl font-bold text-white">
                <i class="fa-solid fa-database text-cyan"></i> Log Riwayat Aktivitas Seismik
            </h2>
            <div class="text-sm text-slate mt-1">Daftar rekaman mentah (Raw Log) aktivitas sensor secara keseluruhan.</div>
        </div>
        
        <div>
            <select id="range-filter" class="input-theme" style="padding: 0.4rem; border-radius: 4px; background: #1e293b; color: white; border: 1px solid #334155;">
                <option value="1h">1 Jam Terakhir</option>
                <option value="24h" selected>24 Jam Terakhir</option>
                <option value="7d">7 Hari Terakhir</option>
            </select>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(148,163,184,0.2); color: #94a3b8; font-size: 0.85rem;">
                    <th style="padding: 1rem 0.5rem;">Waktu Sistem</th>
                    <th style="padding: 1rem 0.5rem;">Sensor ID</th>
                    <th style="padding: 1rem 0.5rem;">Status</th>
                    <th style="padding: 1rem 0.5rem;">STA/LTA Ratio</th>
                    <th style="padding: 1rem 0.5rem;">Deviasi Getaran</th>
                </tr>
            </thead>
            <tbody id="log-table-body">
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i> Memuat data dari database...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('log-table-body');

    // 1. Fungsi pembuat baris tabel (Bisa dipakai ulang untuk API & WebSocket)
    function createRow(row) {
        const dateObj = new Date(row.time || row.timestamp || new Date());
        const timeStr = dateObj.toLocaleDateString('id-ID') + ' ' + dateObj.toLocaleTimeString('id-ID');
        
        // Berikan warna badge kustom berdasarkan status
        let badgeClass = 'badge-aman';
        const statusStr = String(row.status || 'IDLE').toUpperCase();
        
        if (['EARTHQUAKE', 'GEMPA', 'P-WAVE', 'S-WAVE'].includes(statusStr)) {
            badgeClass = 'badge-gempa';
        } else if (['SIAGA', 'EVALUASI'].includes(statusStr)) {
            badgeClass = 'badge-siaga';
        }

        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(148,163,184,0.1)';
        tr.innerHTML = `
            <td class="font-mono text-sm" style="padding: 0.75rem 0.5rem;">${timeStr}</td>
            <td style="padding: 0.75rem 0.5rem;"><span class="text-slate">${row.sensor_id || 'NODE-01'}</span></td>
            <td style="padding: 0.75rem 0.5rem;"><span class="badge ${badgeClass}">${statusStr}</span></td>
            <td class="font-mono text-cyan font-bold" style="padding: 0.75rem 0.5rem;">${parseFloat(row.stalta_ratio || 0).toFixed(3)}</td>
            <td class="font-mono text-slate" style="padding: 0.75rem 0.5rem;">${parseFloat(row.deviation || 0).toFixed(4)} g</td>
        `;
        return tr;
    }

    // 2. Fungsi untuk memuat data historis (Raw Data) dari API Laravel
    async function fetchLogData(range = '24h') {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Mengambil data...</td></tr>`;
        try {
            const response = await fetch(`/api/telemetry/history?range=${range}`);
            const result = await response.json();

            if (response.ok && result.success) {
                tableBody.innerHTML = ''; 

                if (result.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">Tidak ada rekaman data aktivitas seismik.</td></tr>`;
                    return;
                }

                // Masukkan semua baris ke dalam tabel
                result.data.forEach(row => {
                    tableBody.appendChild(createRow(row));
                });
            }
        } catch (error) {
            console.error('Gagal memuat log data:', error);
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #ef4444;">Gagal terhubung ke database InfluxDB.</td></tr>`;
        }
    }

    // Jalankan fetch saat halaman pertama kali dibuka
    fetchLogData();

    // Event listener untuk Dropdown Filter Waktu
    const filterSelect = document.getElementById('range-filter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            fetchLogData(this.value);
        });
    }

    // 3. FITUR REAL-TIME WEBSOCKET: Tabel otomatis nambah baris jika ada data baru masuk
    if (window.Echo) {
        window.Echo.channel('seismic')
            .listen('.data.received', (e) => {
                // Hapus pesan "Tidak ada rekaman" jika tabel sebelumnya kosong
                if (tableBody.querySelector('td[colspan="5"]')) {
                    tableBody.innerHTML = '';
                }
                
                // Tambahkan baris data baru di PALING ATAS tabel
                tableBody.insertBefore(createRow(e), tableBody.firstChild);
                
                // Jaga agar tabel tidak membuat browser lag (maksimal tampil 500 baris terbaru)
                if (tableBody.children.length > 500) {
                    tableBody.removeChild(tableBody.lastChild);
                }
            });
    }
});
</script>
@endpush