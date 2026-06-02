<?php
// app/Http/Controllers/SensorNodeController.php
namespace App\Http\Controllers;

use App\Models\SensorNode;
use Illuminate\Http\Request;

class SensorNodeController extends Controller
{
    public function index()
    {
        $nodes = SensorNode::withCount('telemetries')
            ->with(['telemetries' => function($q) {
                $q->latest('recorded_at')->take(5);
            }])
            ->paginate(10);

        return view('nodes.index', compact('nodes'));
    }
}