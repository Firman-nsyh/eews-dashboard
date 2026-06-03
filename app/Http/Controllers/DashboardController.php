<?php

namespace App\Http\Controllers;

use App\Models\SensorNode;
use App\Models\Telemetry;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_nodes' => SensorNode::count(),
            'online_nodes' => SensorNode::where('last_seen', '>=', now()->subMinutes(5))->count(),
            'today_events' => Telemetry::whereDate('recorded_at', now())
                ->where('is_earthquake', true)
                ->count(),
            'last_earthquake' => Telemetry::where('is_earthquake', true)
                ->latest('recorded_at')
                ->first(),
        ];

        $recentAlerts = Telemetry::with('sensorNode')
            ->where('alert_level', '!=', 'safe')
            ->latest('recorded_at')
            ->take(8)
            ->get();

        $nodes = SensorNode::with(['telemetries' => function ($query) {
            $query->latest('recorded_at')->limit(1);
        }])->get();

        return view('dashboard', compact('stats', 'nodes', 'recentAlerts'));
    }

    public function seismograph()
    {
        return view('seismograph');
    }
}