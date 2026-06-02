<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SensorNodeController;
use App\Http\Controllers\EarthquakeLogController;
use App\Http\Controllers\Api\TelemetryController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/seismograph', [DashboardController::class, 'seismograph'])->name('seismograph');
Route::get('/nodes', [SensorNodeController::class, 'index'])->name('nodes.index');
Route::get('/logs', [EarthquakeLogController::class, 'index'])->name('logs.index');
Route::get('/alerts', [EarthquakeLogController::class, 'alerts'])->name('alerts');

// API Routes untuk menerima data dari Node Gateway via MQTT/HTTP
Route::prefix('api')->group(function () {
    Route::post('/telemetry', [TelemetryController::class, 'store']);
    Route::get('/telemetry/latest', [TelemetryController::class, 'latest']);
    Route::get('/telemetry/stream', [TelemetryController::class, 'stream']);
});