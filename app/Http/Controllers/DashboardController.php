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

        return Inertia::render('App/Dashboard/Index', [
            'projects' => $projects,
        ]);
    }
}
