<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('dashboard');
    }

    public function seismograph()
    {
        return view('seismograph');
    }

    public function alerts()
    {
        return view('alerts');
    }

    public function logdata()
    {
        return view('logdata');
    }
}