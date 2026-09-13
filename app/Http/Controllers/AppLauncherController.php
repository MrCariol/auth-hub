<?php

namespace App\Http\Controllers;

use App\Models\PwaClient;
use Illuminate\View\View;

class AppLauncherController extends Controller
{
    public function index(): View
    {
        return view('apps.index', [
            'pwaClients' => PwaClient::orderBy('name')->get(),
        ]);
    }
}
