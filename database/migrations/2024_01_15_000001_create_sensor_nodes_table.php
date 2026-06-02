<?php
// database/migrations/2024_01_15_000001_create_sensor_nodes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sensor_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('node_id')->unique(); // ESP32 unique ID
            $table->string('name');
            $table->string('location');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'error'])->default('active');
            $table->timestamp('last_seen')->nullable();
            $table->boolean('mpu6050_status')->default(true);
            $table->boolean('hc_sr04_status')->default(true);
            $table->integer('esp_now_signal')->nullable(); // RSSI
            $table->integer('battery_level')->nullable(); // percentage
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sensor_nodes');
    }
};