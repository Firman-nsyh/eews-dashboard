<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API untuk menerima data dari Node Gateway
Route::post('/telemetry', [TelemetryController::class, 'store']);
Route::get('/telemetry/latest', [TelemetryController::class, 'latest']);
Route::get('/telemetry/stream', [TelemetryController::class, 'stream']);