<?php

namespace App\Http\Controllers;

use App\Models\ChaveApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChaveApiController extends Controller
{
    /**
     * Lista as API Keys do usuario autenticado.
     */
    public function index(Request $request)
    {
        $planoEfetivo = $request->user()->planoEfetivo();

        if (!$planoEfetivo || !$planoEfetivo->has_advanced_features) {
            abort(403, 'Seu plano nao inclui acesso a API Externa. Faca um upgrade.');
        }

        $chaves = $request->user()
            ->chavesApi()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($chave) {
                return [
                    'id' => $chave->id,
                    'name' => $chave->name,
                    'key_preview' => 'sk_live_...' . substr($chave->key, -6),
                    'last_used_at' => $chave->last_used_at?->diffForHumans(),
                    'expires_at' => $chave->expires_at?->toDateString(),
                    'is_active' => $chave->is_active,
                    'created_at' => $chave->created_at->toDateString(),
                ];
            });

        return response()->json([
            'keys' => $chaves,
            'limit' => 5,
        ]);
    }

    /**
     * Cria uma nova API Key. A chave completa e retornada apenas uma vez.
     */
    public function store(Request $request)
    {
        $planoEfetivo = $request->user()->planoEfetivo();

        if (!$planoEfetivo || !$planoEfetivo->has_advanced_features) {
            abort(403, 'Seu plano nao inclui acesso a API Externa.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $contagemAtivas = $request->user()->chavesApi()->active()->count();

        if ($contagemAtivas >= 5) {
            abort(422, 'Limite de 5 chaves ativas atingido. Revogue uma chave antes de criar outra.');
        }

        $chaveRaw = ChaveApi::gerarChave();

        $chaveApi = $request->user()->chavesApi()->create([
            'name' => $request->name,
            'key' => $chaveRaw,
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'message' => 'API Key criada com sucesso. Guarde a chave abaixo, ela nao sera exibida novamente.',
            'id' => $chaveApi->id,
            'name' => $chaveApi->name,
            'key' => $chaveRaw,
            'expires_at' => $chaveApi->expires_at?->toDateString(),
        ], 201);
    }

    /**
     * Revoga (desativa) uma API Key.
     */
    public function revoke(Request $request, ChaveApi $chaveApi)
    {
        Gate::authorize('manage', $chaveApi);

        $chaveApi->update(['is_active' => false]);

        return response()->json(['message' => 'Chave revogada com sucesso.']);
    }

    /**
     * Remove permanentemente uma API Key.
     */
    public function destroy(Request $request, ChaveApi $chaveApi)
    {
        Gate::authorize('manage', $chaveApi);

        $chaveApi->delete();

        return response()->json(['message' => 'Chave removida com sucesso.']);
    }
}
