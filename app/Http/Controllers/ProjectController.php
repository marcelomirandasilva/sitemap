<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        // 1. Valida
        $validated = $request->validate([
            'url' => 'required|url|active_url',
        ]);

        // 2. Extrai o nome do site (ex: google.com)
        $parsed = parse_url($validated['url']);
        $name = $parsed['host'] ?? $validated['url'];

        // 3. Verifica limites do plano (OPCIONAL - Faremos depois)
        // ...

        // 4. Cria o projeto
        $request->user()->projects()->create([
            'name' => $name,
            'url' => $validated['url'],
            'status' => 'pending', // Começa pendente até rodar
            'frequency' => 'weekly',
        ]);

        return Redirect::back()->with('success', 'Website added successfully!');
    }
}