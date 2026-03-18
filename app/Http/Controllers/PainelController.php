<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PainelController extends Controller
{
    public function index()
    {
        $projetos = Auth::user()->projetos()
            ->with([
                'tarefasSitemap' => function ($query) {
                    $query->latest()->limit(1); // Traz apenas o ultimo job
                }
            ])
            ->latest()
            ->get()
            ->map(function ($projeto) {
                // Adiciona um atributo "latest_job" para facilitar no frontend
                $projeto->ultimo_job = $projeto->tarefasSitemap->first();
                return $projeto;
            });

        $user = Auth::user();
        $user->load('plano'); // Garante que o plano está carregado

        return Inertia::render('App/Dashboard/Index', [
            'projetos' => $projetos,
            'userPlan' => $user->plano, // Passa o objeto do plano
        ]);
    }
}
