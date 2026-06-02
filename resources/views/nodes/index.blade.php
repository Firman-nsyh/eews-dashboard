@extends('layouts.app')

@section('title', 'Manajemen Node Sensor')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Node Sensor & Gateway</h1>
        <button class="px-4 py-2 bg-seismo-accent hover:bg-cyan-600 rounded-lg text-sm font-medium transition-colors">
            <i class="fa-solid fa-plus mr-1"></i> Tambah Node
        </button>
    </div>

    <div class="glass-panel rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-4">Node ID</th>
                        <th class="px-6 py-4">Lokasi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Sensor</th>
                        <th class="px-6 py-4">Signal</th>
                        <th class="px-6 py-4">Baterai</th>
                        <th class="px-6 py-4">Last Seen</th>
                        <th class="px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @foreach($nodes as $node)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-slate-700 flex items-center justify-center">
                                    <i class="fa-solid fa-wifi text-xs {{ $node->isOnline() ? 'text-safe' : 'text-slate-500' }}"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-white">{{ $node->name }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $node->node_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-300">
                            {{ $node->location }}
                            @if($node->latitude)
                            <p class="text-xs text-slate-500 font-mono">{{ $node->latitude }}, {{ $node->longitude }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $node->isOnline() ? 'bg-safe/20 text-safe' : 'bg-slate-700 text-slate-400' }}">
                                {{ $node->isOnline() ? 'Online' : 'Offline' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <span class="text-xs px-2 py-1 rounded {{ $node->mpu6050_status ? 'bg-seismo-accent/20 text-seismo-accent' : 'bg-danger/20 text-danger' }}">
                                    MPU6050
                                </span>
                                <span class="text-xs px-2 py-1 rounded {{ $node->hc_sr04_status ? 'bg-seismo-accent/20 text-seismo-accent' : 'bg-danger/20 text-danger' }}">
                                    HC-SR04
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-mono text-slate-300">
                            {{ $node->esp_now_signal ?? '-' }} dBm
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-warning rounded-full" style="width: {{ $node->battery_level ?? 0 }}%"></div>
                                </div>
                                <span class="text-xs text-slate-400">{{ $node->battery_level ?? '-' }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-400">
                            {{ $node->last_seen ? $node->last_seen->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <button class="p-2 hover:bg-slate-700 rounded-lg text-slate-400 hover:text-white transition-colors">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="p-2 hover:bg-slate-700 rounded-lg text-slate-400 hover:text-white transition-colors">
                                    <i class="fa-solid fa-gear"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-700">
            {{ $nodes->links() }}
        </div>
    </div>
</div>
@endsection