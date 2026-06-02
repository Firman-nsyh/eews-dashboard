<?php
// app/Models/SensorNode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorNode extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'node_id', 'name', 'location', 'latitude', 'longitude',
        'status', 'last_seen', 'mpu6050_status', 'hc_sr04_status',
        'esp_now_signal', 'battery_level'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function telemetries()
    {
        return $this->hasMany(Telemetry::class, 'node_id', 'node_id');
    }

    public function isOnline()
    {
        return $this->last_seen && $this->last_seen->diffInMinutes(now()) < 5;
    }
}