<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiKeyController extends Controller
{
    /**
     * Lista as API Keys do usuário autenticado.
     */
    public function index(Request $request)
    {
        // Verifica se o plano do usuário permite acesso à API Externa
        if (!$request->user()->plan || !$request->user()->plan->has_advanced_features) {
            abort(403, 'Seu plano não inclui acesso à API Externa. Faça um upgrade.');
        }

        $keys = $request->user()
            ->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                // Retorna tudo exceto a chave em si (segurança)
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key_preview' => 'sk_live_...' . substr($key->key, -6),
                    'last_used_at' => $key->last_used_at?->diffForHumans(),
                    'expires_at' => $key->expires_at?->toDateString(),
                    'is_active' => $key->is_active,
                    'created_at' => $key->created_at->toDateString(),
                ];
            });

        return response()->json([
            'keys' => $keys,
            'limit' => 5, // Limite de chaves por usuário
        ]);
    }

    /**
     * Cria uma nova API Key. A chave completa é retornada APENAS UMA VEZ.
     */
    public function store(Request $request)
    {
        if (!$request->user()->plan || !$request->user()->plan->has_advanced_features) {
            abort(403, 'Seu plano não inclui acesso à API Externa.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'expires_at' => 'nullable|date|after:today',
        ]);

        // Limite de 5 chaves ativas por usuário
        $activeCount = $request->user()->apiKeys()->active()->count();
        if ($activeCount >= 5) {
            abort(422, 'Limite de 5 chaves ativas atingido. Revogue uma chave antes de criar outra.');
        }

        $rawKey = ApiKey::generateKey();

        $apiKey = $request->user()->apiKeys()->create([
            'name' => $request->name,
            'key' => $rawKey,
            'expires_at' => $request->expires_at,
        ]);

        // Retorna a chave completa apenas nesta resposta (nunca mais será exibida)
        return response()->json([
            'message' => 'API Key criada com sucesso. Guarde a chave abaixo, ela não será exibida novamente.',
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $rawKey, // Única vez que a chave completa é exposta
            'expires_at' => $apiKey->expires_at?->toDateString(),
        ], 201);
    }

    /**
     * Revoga (desativa) uma API Key.
     */
    public function revoke(Request $request, ApiKey $apiKey)
    {
        // Garante que o usuário só pode revogar as próprias chaves
        Gate::authorize('manage', $apiKey);

        $apiKey->update(['is_active' => false]);

        return response()->json(['message' => 'Chave revogada com sucesso.']);
    }

    /**
     * Remove permanentemente uma API Key.
     */
    public function destroy(Request $request, ApiKey $apiKey)
    {
        Gate::authorize('manage', $apiKey);

        $apiKey->delete();

        return response()->json(['message' => 'Chave removida com sucesso.']);
    }
}
