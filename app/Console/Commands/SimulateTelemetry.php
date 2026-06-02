<?php

namespace App\Console\Commands;

use App\Models\Telemetry;
use App\Models\SensorNode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulateTelemetry extends Command
{
    protected $signature = 'telemetry:simulate {--node=ESP32-NODE-001} {--duration=60}';
    protected $description = 'Simulate telemetry data for demo purposes';

    public function handle()
    {
        $nodeId = $this->option('node');
        $duration = $this->option('duration');
        
        $this->info("Simulating telemetry for node: {$nodeId}");
        $this->info("Duration: {$duration} seconds");
        
        $startTime = time();
        $earthquakeTriggered = false;
        $earthquakeStart = 0;
        
        while (time() - $startTime < $duration) {
            $elapsed = time() - $startTime;
            
            // Trigger earthquake at 20 seconds
            if ($elapsed > 20 && $elapsed < 35 && !$earthquakeTriggered) {
                $earthquakeTriggered = true;
                $earthquakeStart = $elapsed;
                $this->warn("🌍 EARTHQUAKE SIMULATION STARTED!");
            }
            
            if ($earthquakeTriggered && $elapsed - $earthquakeStart > 15) {
                $earthquakeTriggered = false;
                $this->info("✅ Earthquake ended");
            }
            
            $data = $this->generateData($earthquakeTriggered, $elapsed - $earthquakeStart);
            $data['node_id'] = $nodeId;
            $data['timestamp'] = now()->toDateTimeString();
            
            // Send to API
            try {
                Http::post(url('/api/telemetry'), $data);
                $this->info("[" . now()->format('H:i:s') . "] Sent: Accel(X:{$data['accel_x']}, Y:{$data['accel_y']}, Z:{$data['accel_z']}) | STA/LTA: {$data['sta_lta_ratio']}");
            } catch (\Exception $e) {
                $this->error("Failed to send: " . $e->getMessage());
            }
            
            sleep(1);
        }
        
        $this->info("Simulation completed!");
        return 0;
    }
    
    private function generateData($isEarthquake, $eqTime)
    {
        if ($isEarthquake) {
            $magnitude = 3 + (sin($eqTime) + 1) * 2; // 3-7 magnitude
            $decay = exp(-$eqTime * 0.2);
            
            return [
                'accel_x' => round(sin($eqTime * 5) * $magnitude * $decay + (rand(-10, 10) / 100), 3),
                'accel_y' => round(cos($eqTime * 3) * $magnitude * 0.8 * $decay + (rand(-10, 10) / 100), 3),
                'accel_z' => round(9.8 + sin($eqTime * 7) * $magnitude * 0.5 * $decay, 3),
                'gyro_x' => round(rand(-100, 100) / 100, 2),
                'gyro_y' => round(rand(-100, 100) / 100, 2),
                'gyro_z' => round(rand(-100, 100) / 100, 2),
                'distance' => rand(50, 200),
                'sta_lta_ratio' => round(1.5 + $magnitude * 0.3 * $decay, 2),
                'is_earthquake' => true,
                'magnitude' => round($magnitude, 1),
                'alert_level' => $magnitude > 5 ? 'danger' : 'warning',
            ];
        }
        
        return [
            'accel_x' => round(rand(-5, 5) / 100, 3),
            'accel_y' => round(rand(-5, 5) / 100, 3),
            'accel_z' => round(9.8 + rand(-2, 2) / 100, 3),
            'gyro_x' => round(rand(-20, 20) / 100, 2),
            'gyro_y' => round(rand(-20, 20) / 100, 2),
            'gyro_z' => round(rand(-20, 20) / 100, 2),
            'distance' => rand(50, 200),
            'sta_lta_ratio' => round(rand(2, 8) / 10, 2),
            'is_earthquake' => false,
            'magnitude' => 0,
            'alert_level' => 'safe',
        ];
    }
}