<?php
// app/Http/Controllers/EarthquakeLogController.php
namespace App\Http\Controllers;

use App\Models\Telemetry;
use Illuminate\Http\Request;

class EarthquakeLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Telemetry::with('sensorNode');

        if ($request->filled('alert_level')) {
            $query->where('alert_level', $request->alert_level);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('recorded_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('recorded_at', '<=', $request->date_to);
        }

        $logs = $query->latest('recorded_at')->paginate(50);

        return view('logs.index', compact('logs'));
    }

    public function alerts()
    {
        $activeAlerts = Telemetry::where('alert_level', '!=', 'safe')
            ->where('recorded_at', '>=', now()->subHours(24))
            ->with('sensorNode')
            ->latest('recorded_at')
            ->get()
            ->groupBy('alert_level');

        return view('logs.alerts', compact('activeAlerts'));
    }
}