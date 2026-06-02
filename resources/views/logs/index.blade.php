@extends('layouts.app')

@section('title', 'Log Data Gempa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h1 class="text-2xl font-bold text-white">Riwayat Data Telemetry</h1>
        
        <form class="flex gap-2" method="GET">
            <select name="alert_level" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-seismo-accent focus:outline-none">
                <option value="">Semua Level</option>
                <option value="safe" {{ request('alert_level') == 'safe' ? 'selected' : '' }}>Safe</option>
                <option value="watch" {{ request('alert_level') == 'watch' ? 'selected' : '' }}>Watch</option>
                <option value="warning" {{ request('alert_level') == 'warning' ? 'selected' : '' }}>Warning</option>
                <option value="danger" {{ request('alert_level') == 'danger' ? 'selected' : '' }}>Danger</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-seismo-accent focus:outline-none">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-seismo-accent focus:outline-none">
            <button type="submit" class="px-4 py-2 bg-seismo-accent hover:bg-cyan-600 rounded-lg text-sm transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    <div class="glass-panel rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Node</th>
                        <th class="px-6 py-4">Accel (X/Y/Z)</th>
                        <th class="px-6 py-4">Gyro (X/Y/Z)</th Berikut adalah **file-file lengkap** yang harus kamu buat untuk project pendeteksi gempa berbasis Laravel ini. Saya akan melanjutkan dan menyelesaikan semua file yang tadi terpotong:

---

## 📁 Struktur File Lengkap

### 1. **resources/views/logs/index.blade.php** (Lanjutan)

```blade
                        <th class="px-6 py-4">Jarak</th>
                        <th class="px-6 py-4">STA/LTA</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-800/30 transition-colors {{ $log->is_earthquake ? 'bg-danger/5' : '' }}">
                        <td class="px-6 py-4">
                            <div class="text-sm text-white font-mono">{{ $log->recorded_at->format('H:i:s') }}</div>
                            <div class="text-xs text-slate-500">{{ $log->recorded_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-white">{{ $log->sensorNode->name ?? $log->node_id }}</span>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs">
                            <div class="flex gap-2">
                                <span class="text-seismo-accent">{{ number_format($log->accel_x, 3) }}</span>
                                <span class="text-warning">{{ number_format($log->accel_y, 3) }}</span>
                                <span class="text-safe">{{ number_format($log->accel_z, 3) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-slate-400">
                            {{ number_format($log->gyro_x, 2) }} / {{ number_format($log->gyro_y, 2) }} / {{ number_format($log->gyro_z, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-300">
                            {{ number_format($log->distance, 1) }} cm
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-12 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $log->sta_lta_ratio > 1.5 ? 'bg-danger' : 'bg-safe' }}" 
                                         style="width: {{ min(($log->sta_lta_ratio / 3) * 100, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-mono {{ $log->sta_lta_ratio > 1.5 ? 'text-danger' : 'text-slate-400' }}">
                                    {{ number_format($log->sta_lta_ratio, 2) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($log->is_earthquake)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-danger/20 text-danger border border-danger/30">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                GEMPA
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-safe/20 text-safe">
                                Normal
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="showDetail({{ $log->id }})" class="p-2 hover:bg-slate-700 rounded-lg text-slate-400 hover:text-white transition-colors">
                                <i class="fa-solid fa-circle-info"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            <i class="fa-solid fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data telemetry
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-700">
            {{ $logs->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function showDetail(id) {
    // Implementasi modal detail jika diperlukan
    alert('Detail telemetry ID: ' + id);
}
</script>
@endpush
@endsection