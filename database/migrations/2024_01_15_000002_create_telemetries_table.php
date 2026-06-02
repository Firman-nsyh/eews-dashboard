<?php
// database/migrations/2024_01_15_000002_create_telemetries_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('telemetries', function (Blueprint $table) {
            $table->id();
            $table->string('node_id');
            $table->float('accel_x')->default(0);
            $table->float('accel_y')->default(0);
            $table->float('accel_z')->default(0);
            $table->float('gyro_x')->default(0);
            $table->float('gyro_y')->default(0);
            $table->float('gyro_z')->default(0);
            $table->float('distance')->default(0); // cm from HC-SR04
            $table->float('sta_lta_ratio')->default(0);
            $table->boolean('is_earthquake')->default(false);
            $table->float('magnitude')->default(0);
            $table->enum('alert_level', ['safe', 'watch', 'warning', 'danger'])->default('safe');
            $table->json('raw_json')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['node_id', 'recorded_at']);
            $table->index('is_earthquake');
        });
    }

    public function down()
    {
        Schema::dropIfExists('telemetries');
    }
};