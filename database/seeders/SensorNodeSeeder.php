<?php

namespace Database\Seeders;

use App\Models\SensorNode;
use Illuminate\Database\Seeder;

class SensorNodeSeeder extends Seeder
{
    public function run()
    {
        $nodes = [
            [
                'node_id' => 'ESP32-NODE-001',
                'name' => 'Sensor Utama - Gedung A',
                'location' => 'Lantai 1, Gedung A',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'status' => 'active',
                'last_seen' => now(),
                'mpu6050_status' => true,
                'hc_sr04_status' => true,
                'esp_now_signal' => -45,
                'battery_level' => 87,
            ],
            [
                'node_id' => 'ESP32-NODE-002',
                'name' => 'Sensor Sekunder - Gedung B',
                'location' => 'Lantai 2, Gedung B',
                'latitude' => -6.2090,
                'longitude' => 106.8458,
                'status' => 'active',
                'last_seen' => now()->subMinutes(2),
                'mpu6050_status' => true,
                'hc_sr04_status' => true,
                'esp_now_signal' => -52,
                'battery_level' => 64,
            ],
            [
                'node_id' => 'ESP32-GATEWAY-001',
                'name' => 'Gateway Pusat',
                'location' => 'Ruang Server',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'status' => 'active',
                'last_seen' => now(),
                'mpu6050_status' => false,
                'hc_sr04_status' => false,
                'esp_now_signal' => -30,
                'battery_level' => 100,
            ],
        ];

        foreach ($nodes as $node) {
            SensorNode::create($node);
        }
    }
}