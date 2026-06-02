<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\SensorNode;
use App\Models\Telemetry;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_nodes' => SensorNode::count(),
            'online_nodes' => SensorNode::where('last_seen', '>=', now()->subMinutes(5))->count(),
            'today_events' => Telemetry::where('is_earthquake', true)
                ->whereDate('recorded_at', today())->count(),
            'last_earthquake' => Telemetry::where('is_earthquake', true)
                ->latest('recorded_at')->first(),
        ];

        $recentAlerts = Telemetry::where('alert_level', '!=', 'safe')
            ->with('sensorNode')
            ->latest('recorded_at')
            ->take(10)
            ->get();

        $nodes = SensorNode::with(['telemetries' => function($q) {
            $q->latest('recorded_at')->take(1);
        }])->get();

        return view('dashboard', compact('stats', 'recentAlerts', 'nodes'));
    }

    public function seismograph()
    {
        return view('seismograph');
    }
}