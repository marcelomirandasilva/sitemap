<?php

namespace App\Http\Controllers;

use App\Models\ChaveApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChaveApiController extends Controller
{
    /**
     * Lista as API Keys do usuário autenticado.
     */
    public function index(Request $request)
    {
        // Verifica se o plano do usuário permite acesso à API Externa
        if (!$request->user()->plano || !$request->user()->plano->has_advanced_features) {
            abort(403, 'Seu plano não inclui acesso à API Externa. Faça um upgrade.');
        }

        $chaves = $request->user()
            ->chavesApi()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($chave) {
                // Retorna tudo exceto a chave em si (segurança)
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
            'limit' => 5, // Limite de chaves por usuário
        ]);
    }

    /**
     * Cria uma nova API Key. A chave completa é retornada APENAS UMA VEZ.
     */
    public function store(Request $request)
    {
        if (!$request->user()->plano || !$request->user()->plano->has_advanced_features) {
            abort(403, 'Seu plano não inclui acesso à API Externa.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'expires_at' => 'nullable|date|after:today',
        ]);

        // Limite de 5 chaves ativas por usuário
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

        // Retorna a chave completa apenas nesta resposta (nunca mais será exibida)
        return response()->json([
            'message' => 'API Key criada com sucesso. Guarde a chave abaixo, ela não será exibida novamente.',
            'id' => $chaveApi->id,
            'name' => $chaveApi->name,
            'key' => $chaveRaw, // Única vez que a chave completa é exposta
            'expires_at' => $chaveApi->expires_at?->toDateString(),
        ], 201);
    }

    /**
     * Revoga (desativa) uma API Key.
     */
    public function revoke(Request $request, ChaveApi $chaveApi)
    {
        // Garante que o usuário só pode revogar as próprias chaves
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
