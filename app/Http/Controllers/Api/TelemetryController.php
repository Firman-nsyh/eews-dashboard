<?php
// app/Http/Controllers/Api/TelemetryController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorNode;
use App\Models\Telemetry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'node_id' => 'required|string',
            'accel_x' => 'nullable|numeric',
            'accel_y' => 'nullable|numeric',
            'accel_z' => 'nullable|numeric',
            'gyro_x' => 'nullable|numeric',
            'gyro_y' => 'nullable|numeric',
            'gyro_z' => 'nullable|numeric',
            'distance' => 'nullable|numeric',
            'sta_lta_ratio' => 'nullable|numeric',
            'is_earthquake' => 'nullable|boolean',
            'magnitude' => 'nullable|numeric',
            'alert_level' => 'nullable|in:safe,watch,warning,danger',
            'timestamp' => 'nullable|date',
        ]);

        // Update or create sensor node
        $node = SensorNode::updateOrCreate(
            ['node_id' => $validated['node_id']],
            [
                'name' => $validated['node_id'],
                'location' => 'Unknown',
                'last_seen' => now(),
            ]
        );

        $telemetry = Telemetry::create([
            'node_id' => $validated['node_id'],
            'accel_x' => $validated['accel_x'] ?? 0,
            'accel_y' => $validated['accel_y'] ?? 0,
            'accel_z' => $validated['accel_z'] ?? 0,
            'gyro_x' => $validated['gyro_x'] ?? 0,
            'gyro_y' => $validated['gyro_y'] ?? 0,
            'gyro_z' => $validated['gyro_z'] ?? 0,
            'distance' => $validated['distance'] ?? 0,
            'sta_lta_ratio' => $validated['sta_lta_ratio'] ?? 0,
            'is_earthquake' => $validated['is_earthquake'] ?? false,
            'magnitude' => $validated['magnitude'] ?? 0,
            'alert_level' => $validated['alert_level'] ?? 'safe',
            'raw_json' => $request->all(),
            'recorded_at' => $validated['timestamp'] ?? now(),
        ]);

        // Cache latest data for real-time
        Cache::put("telemetry:{$validated['node_id']}:latest", $telemetry, 60);
        
        // Broadcast to WebSocket (if using Laravel Echo/Pusher)
        // broadcast(new NewTelemetryEvent($telemetry))->toOthers();

        Log::info('Telemetry received', ['node' => $validated['node_id'], 'alert' => $telemetry->alert_level]);

        return response()->json([
            'status' => 'success',
            'data' => $telemetry
        ], 201);
    }

    public function latest(Request $request)
    {
        $nodeId = $request->get('node_id');
        
        if ($nodeId) {
            $data = Cache::get("telemetry:{$nodeId}:latest") 
                ?? Telemetry::where('node_id', $nodeId)->latest()->first();
        } else {
            $data = Telemetry::with('sensorNode')->latest('recorded_at')->take(50)->get();
        }

        return response()->json($data);
    }

    public function stream(Request $request)
    {
        // Server-Sent Events endpoint untuk real-time updates
        $nodeId = $request->get('node_id');
        
        return response()->stream(function () use ($nodeId) {
            while (true) {
                $data = $nodeId 
                    ? (Cache::get("telemetry:{$nodeId}:latest") ?? Telemetry::where('node_id', $nodeId)->latest()->first())
                    : Telemetry::with('sensorNode')->latest('recorded_at')->take(10)->get();

                echo "data: " . json_encode($data) . "\n\n";
                ob_flush();
                flush();
                
                if (connection_aborted()) break;
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
