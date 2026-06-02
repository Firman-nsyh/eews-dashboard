@extends('layouts.app')

@section('title', 'Alert & Peringatan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Sistem Peringatan Gempa</h1>
        <div class="flex gap-2">
            <span class="px-3 py-1 rounded-full bg-danger/20 text-danger text-sm border border-danger/30 animate-pulse">
                <i class="fa-solid fa-circle text-[8px] mr-1"></i>
                Sistem Monitoring Aktif
            </span>
        </div>
    </div>

    {{-- Alert Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach(['danger' => 'Bahaya', 'warning' => 'Peringatan', 'watch' => 'Waspada', 'safe' => 'Aman'] as $level => $label)
        <div class="glass-panel rounded-xl p-6 border-l-4 border-{{ $level === 'watch' ? 'watch' : $level }}">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-400 text-sm">Level {{ $label }}</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $activeAlerts->get($level, collect())->count() }}</p>
                </div>
                <i class="fa-solid {{ 
                    $level === 'danger' ? 'fa-triangle-exclamation text-danger' : 
                    ($level === 'warning' ? 'fa-bell text-warning' : 
                    ($level === 'watch' ? 'fa-eye text-watch' : 'fa-shield-halved text-safe')) 
                }} text-2xl"></i>
            </div>
            <p class="text-xs text-slate-500 mt-3">24 jam terakhir</p>
        </div>
        @endforeach
    </div>

    {{-- Active Danger Alerts --}}
    @if($activeAlerts->has('danger'))
    <div class="glass-panel rounded-xl p-6 border-2 border-danger/50 bg-danger/5">
        <h2 class="text-lg font-bold text-danger mb-4 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
            ALERT BAHAYA AKTIF
        </h2>
        <div class="space-y-3">
            @foreach($activeAlerts['danger'] as $alert)
            <div class="bg-slate-900/80 rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-house-crack text-danger text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white">Gempa Terdeteksi!</p>
                        <p class="text-sm text-slate-400">
                            Node: {{ $alert->sensorNode->name ?? $alert->node_id }} | 
                            Magnitudo: {{ $alert->magnitude }} | 
                            {{ $alert->recorded_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-mono font-bold text-danger">{{ number_format($alert->sta_lta_ratio, 2) }}</p>
                    <p class="text-xs text-slate-500">STA/LTA Ratio</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Alert Timeline --}}
    <div class="glass-panel rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-6">Timeline Alert (24 Jam)</h2>
        
        <div class="relative">
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-700"></div>
            
            <div class="space-y-6">
                @forelse($activeAlerts->flatten()->sortByDesc('recorded_at') as $alert)
                <div class="relative pl-12">
                    <div class="absolute left-2 w-4 h-4 rounded-full border-2 border-slate-800 
                        {{ $alert->alert_level === 'danger' ? 'bg-danger' : 
                           ($alert->alert_level === 'warning' ? 'bg-warning' : 
                           ($alert->alert_level === 'watch' ? 'bg-watch' : 'bg-safe')) }}">
                    </div>
                    
                    <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800 transition-colors">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold uppercase
                                        {{ $alert->alert_level === 'danger' ? 'bg-danger/20 text-danger' : 
                                           ($alert->alert_level === 'warning' ? 'bg-warning/20 text-warning' : 
                                           ($alert->alert_level === 'watch' ? 'bg-watch/20 text-watch' : 'bg-safe/20 text-safe')) }}">
                                        {{ $alert->alert_level }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $alert->recorded_at->format('H:i:s') }}</span>
                                </div>
                                <p class="text-white font-medium">{{ $alert->sensorNode->name ?? $alert->node_id }}</p>
                                <p class="text-sm text-slate-400 mt-1">
                                    Accel: X={{ number_format($alert->accel_x, 2) }} 
                                    Y={{ number_format($alert->accel_y, 2) }} 
                                    Z={{ number_format($alert->accel_z, 2) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-mono font-bold text-white">{{ number_format($alert->magnitude, 1) }}</p>
                                <p class="text-xs text-slate-500">Magnitudo</p>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="pl-12 text-slate-500 py-8">
                    <i class="fa-solid fa-check-circle text-safe text-2xl mb-2 block"></i>
                    Tidak ada alert dalam 24 jam terakhir
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection