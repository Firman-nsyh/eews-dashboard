@extends('layouts.app')

@section('title', 'Seismograf Real-Time')

@section('content')
<div class="space-y-6">
    {{-- Full Screen Seismograph --}}
    <div class="glass-panel rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i class="fa-solid fa-wave-square text-seismo-accent animate-pulse"></i>
                    Seismograf Real-Time
                </h2>
                <p class="text-slate-400 text-sm mt-1">Visualisasi data akselerometer MPU6050 (X, Y, Z) & STA/LTA Ratio</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 bg-seismo-accent rounded-full animate-pulse"></span>
                    <span class="text-seismo-accent font-mono">LIVE STREAM</span>
                </div>
                <button onclick="togglePause()" id="pause-btn" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm transition-colors">
                    <i class="fa-solid fa-pause mr-1"></i> Pause
                </button>
            </div>
        </div>

        {{-- Main Chart --}}
        <div class="relative h-96 seismograph-grid rounded-lg border border-slate-700 overflow-hidden bg-slate-900/50">
            <canvas id="main-seismo"></canvas>
            
            {{-- Grid overlay info --}}
            <div class="absolute top-4 left-4 bg-slate-900/80 rounded-lg p-3 text-xs font-mono space-y-1">
                <div class="text-seismo-accent">X-Axis (Lateral)</div>
                <div class="text-warning">Y-Axis (Longitudinal)</div>
                <div class="text-safe">Z-Axis (Vertical)</div>
            </div>
        </div>

        {{-- STA/LTA Ratio Bar --}}
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-slate-400">STA/LTA Ratio (Trigger Threshold)</span>
                <span class="font-mono text-seismo-accent" id="sta-lta-value">0.00</span>
            </div>
            <div class="h-4 bg-slate-800 rounded-full overflow-hidden relative">
                <div id="sta-lta-bar" class="h-full bg-gradient-to-r from-safe via-warning to-danger transition-all duration-300" style="width: 0%"></div>
                <div class="absolute top-0 bottom-0 w-0.5 bg-white" style="left: 75%" title="Trigger Threshold"></div>
            </div>
            <div class="flex justify-between text-xs text-slate-500 mt-1">
                <span>0.0</span>
                <span>Threshold: 1.5</span>
                <span>3.0+</span>
            </div>
        </div>
    </div>

    {{-- 3-Axis Visualization --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-panel rounded-xl p-4">
            <h3 class="text-sm font-medium text-seismo-accent mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-seismo-accent rounded-full"></span>
                Axis X (Surge/Sway)
            </h3>
            <div class="h-32 relative">
                <canvas id="axis-x-chart"></canvas>
            </div>
            <div class="mt-2 text-center">
                <span class="text-2xl font-mono font-bold text-white" id="axis-x-value">0.00</span>
                <span class="text-xs text-slate-500">m/s²</span>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-4">
            <h3 class="text-sm font-medium text-warning mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-warning rounded-full"></span>
                Axis Y (Heave/Sway)
            </h3>
            <div class="h-32 relative">
                <canvas id="axis-y-chart"></canvas>
            </div>
            <div class="mt-2 text-center">
                <span class="text-2xl font-mono font-bold text-white" id="axis-y-value">0.00</span>
                <span class="text-xs text-slate-500">m/s²</span>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-4">
            <h3 class="text-sm font-medium text-safe mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-safe rounded-full"></span>
                Axis Z (Vertical)
            </h3>
            <div class="h-32 relative">
                <canvas id="axis-z-chart"></canvas>
            </div>
            <div class="mt-2 text-center">
                <span class="text-2xl font-mono font-bold text-white" id="axis-z-value">0.00</span>
                <span class="text-xs text-slate-500">m/s²</span>
            </div>
        </div>
    </div>

    {{-- FFT Spectrum (Simulated) --}}
    <div class="glass-panel rounded-xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Frequency Spectrum (FFT)</h3>
        <div class="h-48 relative">
            <canvas id="fft-chart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let isPaused = false;
    const maxPoints = 300;
    
    function createChart(canvasId, color, fill = false) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array(maxPoints).fill(''),
                datasets: [{
                    data: Array(maxPoints).fill(0),
                    borderColor: color,
                    backgroundColor: fill ? color.replace(')', ', 0.1)').replace('rgb', 'rgba') : 'transparent',
                    borderWidth: 1.5,
                    tension: 0.3,
                    pointRadius: 0,
                    fill: fill
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false },
                    y: { 
                        display: true,
                        grid: { color: 'rgba(148, 163, 184, 0.05)' },
                        ticks: { color: '#64748b', font: { size: 9 } }
                    }
                }
            }
        });
    }

    const mainChart = new Chart(document.getElementById('main-seismo'), {
        type: 'line',
        data: {
            labels: Array(maxPoints).fill(''),
            datasets: [
                {
                    label: 'X',
                    data: Array(maxPoints).fill(0),
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.05)',
                    borderWidth: 1.5,
                    tension: 0.3,
                    pointRadius: 0
                },
                {
                    label: 'Y',
                    data: Array(maxPoints).fill(0),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.05)',
                    borderWidth: 1.5,
                    tension: 0.3,
                    pointRadius: 0
                },
                {
                    label: 'Z',
                    data: Array(maxPoints).fill(0),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderWidth: 1.5,
                    tension: 0.3,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: { color: '#94a3b8', boxWidth: 12, font: { size: 11 } }
                }
            },
            scales: {
                x: { display: false },
                y: {
                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                }
            }
        }
    });

    const chartX = createChart('axis-x-chart', '#06b6d4', true);
    const chartY = createChart('axis-y-chart', '#f59e0b', true);
    const chartZ = createChart('axis-z-chart', '#10b981', true);

    // FFT Chart
    const fftChart = new Chart(document.getElementById('fft-chart'), {
        type: 'bar',
        data: {
            labels: ['0.5', '1', '2', '3', '5', '7', '10', '15', '20', '30', '50', '100'],
            datasets: [{
                label: 'Amplitude',
                data: Array(12).fill(0),
                backgroundColor: 'rgba(6, 182, 212, 0.6)',
                borderColor: '#06b6d4',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    title: { display: true, text: 'Frequency (Hz)', color: '#64748b' },
                    ticks: { color: '#64748b' }
                },
                y: {
                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    ticks: { color: '#64748b' }
                }
            }
        }
    });

    function togglePause() {
        isPaused = !isPaused;
        document.getElementById('pause-btn').innerHTML = isPaused 
            ? '<i class="fa-solid fa-play mr-1"></i> Resume' 
            : '<i class="fa-solid fa-pause mr-1"></i> Pause';
    }

    function generateEarthquakeSignal(t, magnitude) {
        const freq1 = 2 + Math.random() * 3;
        const freq2 = 5 + Math.random() * 5;
        const decay = Math.exp(-t * 0.5);
        return magnitude * decay * (
            Math.sin(t * freq1 * Math.PI * 2) * 0.6 +
            Math.sin(t * freq2 * Math.PI * 2) * 0.4 +
            (Math.random() - 0.5) * 0.3
        );
    }

    let earthquakeActive = false;
    let earthquakeTime = 0;
    let earthquakeMagnitude = 0;

    function updateData() {
        if (isPaused) return;

        const now = Date.now() / 1000;
        
        // Randomly trigger earthquake (5% chance every second)
        if (!earthquakeActive && Math.random() < 0.02) {
            earthquakeActive = true;
            earthquakeTime = 0;
            earthquakeMagnitude = 2 + Math.random() * 5; // Magnitude 2-7
        }

        let x, y, z, staLta;

        if (earthquakeActive) {
            earthquakeTime += 0.1;
            x = generateEarthquakeSignal(earthquakeTime, earthquakeMagnitude);
            y = generateEarthquakeSignal(earthquakeTime + 0.5, earthquakeMagnitude * 0.8);
            z = 9.8 + generateEarthquakeSignal(earthquakeTime + 1, earthquakeMagnitude * 0.6);
            staLta = 1.0 + earthquakeMagnitude * 0.3 * Math.exp(-earthquakeTime * 0.3);
            
            if (earthquakeTime > 10) {
                earthquakeActive = false;
            }
        } else {
            x = (Math.random() - 0.5) * 0.05;
            y = (Math.random() - 0.5) * 0.05;
            z = 9.8 + (Math.random() - 0.5) * 0.02;
            staLta = 0.2 + Math.random() * 0.3;
        }

        // Update main chart
        mainChart.data.datasets[0].data.shift();
        mainChart.data.datasets[0].data.push(x);
        mainChart.data.datasets[1].data.shift();
        mainChart.data.datasets[1].data.push(y);
        mainChart.data.datasets[2].data.shift();
        mainChart.data.datasets[2].data.push(z);
        mainChart.update('none');

        // Update individual charts
        chartX.data.datasets[0].data.shift();
        chartX.data.datasets[0].data.push(x);
        chartX.update('none');

        chartY.data.datasets[0].data.shift();
        chartY.data.datasets[0].data.push(y);
        chartY.update('none');

        chartZ.data.datasets[0].data.shift();
        chartZ.data.datasets[0].data.push(z);
        chartZ.update('none');

        // Update values
        document.getElementById('axis-x-value').textContent = x.toFixed(3);
        document.getElementById('axis-y-value').textContent = y.toFixed(3);
        document.getElementById('axis-z-value').textContent = z.toFixed(3);

        // Update STA/LTA
        const staLtaPercent = Math.min((staLta / 3.0) * 100, 100);
        document.getElementById('sta-lta-bar').style.width = staLtaPercent + '%';
        document.getElementById('sta-lta-value').textContent = staLta.toFixed(2);
        
        if (staLta > 1.5) {
            document.getElementById('sta-lta-bar').classList.add('animate-pulse');
        } else {
            document.getElementById('sta-lta-bar').classList.remove('animate-pulse');
        }

        // Update FFT (simulated)
        const fftData = fftChart.data.datasets[0].data;
        for (let i = 0; i < fftData.length; i++) {
            if (earthquakeActive) {
                const freq = [0.5, 1, 2, 3, 5, 7, 10, 15, 20, 30, 50, 100][i];
                fftData[i] = earthquakeMagnitude * 10 * Math.exp(-Math.pow((freq - 5) / 5, 2)) + Math.random() * 2;
            } else {
                fftData[i] = Math.random() * 2;
            }
        }
        fftChart.update('none');
    }

    setInterval(updateData, 100);
</script>
@endpush
@endsection