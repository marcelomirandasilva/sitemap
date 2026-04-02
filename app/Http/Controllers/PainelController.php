<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PainelController extends Controller
{
    public function index()
    {
        $projetos = Auth::user()->projetos()
            ->with([
                'tarefasSitemap' => function ($query) {
                    $query->latest()->limit(1);
                },
            ])
            ->latest()
            ->get()
            ->map(function ($projeto) {
                $projeto->ultimo_job = $projeto->tarefasSitemap->first();

                return $projeto;
            });

        $usuario = Auth::user();
        $planoEfetivo = $usuario->planoEfetivo();

        return Inertia::render('App/Dashboard/Index', [
            'projetos' => $projetos,
            'userPlan' => $planoEfetivo,
        ]);
    }
}
