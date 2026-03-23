<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Projeto;
use App\Models\TarefaSitemap;
use Inertia\Inertia;

class PainelController extends Controller
{
    public function index()
    {
        $stats = [
            'totalUsers' => User::count(),
            'activeProjects' => Projeto::count(),
            'runningJobs' => TarefaSitemap::whereIn('status', ['running', 'queued'])->count(),
            'newUsersLastWeek' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $stats
        ]);
    }
}
