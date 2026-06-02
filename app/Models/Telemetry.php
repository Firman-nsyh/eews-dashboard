<?php
// app/Models/Telemetry.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    use HasFactory;
    
    protected $table = 'telemetries';
    
    protected $fillable = [
        'node_id', 'accel_x', 'accel_y', 'accel_z',
        'gyro_x', 'gyro_y', 'gyro_z', 'distance',
        'sta_lta_ratio', 'is_earthquake', 'magnitude',
        'alert_level', 'raw_json', 'recorded_at'
    ];

    protected $casts = [
        'accel_x' => 'float',
        'accel_y' => 'float',
        'accel_z' => 'float',
        'gyro_x' => 'float',
        'gyro_y' => 'float',
        'gyro_z' => 'float',
        'distance' => 'float',
        'sta_lta_ratio' => 'float',
        'is_earthquake' => 'boolean',
        'magnitude' => 'float',
        'raw_json' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function sensorNode()
    {
        return $this->belongsTo(SensorNode::class, 'node_id', 'node_id');
    }

    public function getAlertColorAttribute()
    {
        return match($this->alert_level) {
            'danger' => 'red',
            'warning' => 'yellow',
            'watch' => 'orange',
            default => 'green'
        };
    }

    public function getAlertIconAttribute()
    {
        return match($this->alert_level) {
            'danger' => 'fa-solid fa-triangle-exclamation',
            'warning' => 'fa-solid fa-bell',
            'watch' => 'fa-solid fa-eye',
            default => 'fa-solid fa-check-circle'
        };
    }
}