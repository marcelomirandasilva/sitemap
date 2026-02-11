<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Auth::user()->projects()
            ->with([
                'sitemapJobs' => function ($query) {
                    $query->latest()->limit(1); // Traz apenas o ultimo job
                }
            ])
            ->latest()
            ->get()
            ->map(function ($project) {
                // Adiciona um atributo "latest_job" para facilitar no frontend
                $project->latest_job = $project->sitemapJobs->first();
                return $project;
            });

        $user = Auth::user();
        $user->load('plan'); // Garante que o plano está carregado

        return Inertia::render('App/Dashboard/Index', [
            'projetos' => $projects,
            'userPlan' => $user->plan, // Passa o objeto do plano
        ]);
    }
}
