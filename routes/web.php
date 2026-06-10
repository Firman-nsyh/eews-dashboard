<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TelemetryController;

// Halaman web
Route::get('/',            [DashboardController::class, 'dashboard'])->name('dashboard');
Route::get('/seismograph', [DashboardController::class, 'seismograph'])->name('seismograph');
Route::get('/alerts',      [DashboardController::class, 'alerts'])->name('alerts');
Route::get('/logdata',     [DashboardController::class, 'logdata'])->name('logdata');

// API — menerima data dari Node-RED
Route::post('/api/telemetry',       [TelemetryController::class, 'store']);
Route::get('/api/telemetry/latest', [TelemetryController::class, 'latest']);
Route::get('/api/telemetry/history',[TelemetryController::class, 'history']);
Route::get('/api/alert/stats',      [TelemetryController::class, 'alertStats']);
Route::get('/api/seismograf/data',  [TelemetryController::class, 'seismografData']);

Route::get('/control', function () {
    return view('control');
});

// 1. Rute Pengendali Hardware Jarak Jauh (Ke Node-RED)
Route::post('/api/hardware/control', [TelemetryController::class, 'sendControl']);

// 2. Rute Penghancur Data Kalibrasi Permanen (Ke InfluxDB)
Route::post('/api/database/purge-calibration', [TelemetryController::class, 'deleteCalibrationData']);