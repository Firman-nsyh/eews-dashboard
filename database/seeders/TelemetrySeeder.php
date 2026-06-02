<?php

namespace Database\Seeders;

use App\Models\Telemetry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TelemetrySeeder extends Seeder
{
    public function run()
    {
        $nodeIds = ['ESP32-NODE-001', 'ESP32-NODE-002'];
        $now = Carbon::now();

        // Generate 100 data points for each node (simulating last 10 minutes)
        foreach ($nodeIds as $nodeId) {
            for ($i = 100; $i >= 0; $i--) {
                $time = $now->copy()->subSeconds($i * 6);
                
                // Simulate earthquake event at specific time
                $isEarthquake = ($i > 40 && $i < 55);
                $magnitude = $isEarthquake ? rand(20, 45) / 10 : rand(0, 5) / 10;
                $staLta = $isEarthquake ? rand(15, 35) / 10 : rand(2, 8) / 10;
                
                Telemetry::create([
                    'node_id' => $nodeId,
                    'accel_x' => $isEarthquake ? rand(-500, 500) / 100 : rand(-10, 10) / 100,
                    'accel_y' => $isEarthquake ? rand(-500, 500) / 100 : rand(-10, 10) / 100,
                    'accel_z' => $isEarthquake ? 9.8 + rand(-200, 200) / 100 : 9.8 + rand(-5, 5) / 100,
                    'gyro_x' => rand(-50, 50) / 100,
                    'gyro_y' => rand(-50, 50) / 100,
                    'gyro_z' => rand(-50, 50) / 100,
                    'distance' => rand(50, 200),
                    'sta_lta_ratio' => $staLta,
                    'is_earthquake' => $isEarthquake,
                    'magnitude' => $magnitude,
                    'alert_level' => $staLta > 1.5 ? ($staLta > 2.5 ? 'danger' : 'warning') : 'safe',
                    'raw_json' => [
                        'temp' => rand(250, 350) / 10,
                        'humidity' => rand(40, 80),
                    ],
                    'recorded_at' => $time,
                ]);
            }
        }
    }
}