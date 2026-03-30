<?php

namespace App\Http\Controllers;

use App\Models\ChaveApi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiController extends Controller
{
    /**
     * Exibe a página de API do usuário.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Busca a chave ativa mais recente (sem expiração ou ainda válida)
        $chaveAtiva = $user->chavesApi()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        // Lista de projetos para o dropdown "Site-specific"
        $projetos = $user->projetos()
            ->select('id', 'url')
            ->orderBy('url')
            ->get();

        return Inertia::render('Account/Api', [
            'apiKey' => $chaveAtiva ? $chaveAtiva->key : null,
            'endpointUrl' => rtrim(config('services.sitemap_generator.base_url'), '/') . '/api/v1',
            'callbackUrl' => $user->api_callback_url,
            'projetos' => $projetos,
            'podeAcessarApi' => $user->plano && $user->plano->has_advanced_features,
        ]);
    }

    /**
     * Gera uma nova API Key, revogando a anterior.
     */
    public function resetKey(Request $request)
    {
        $user = $request->user();

        // Revoga todas as chaves ativas sem expiração (chave principal)
        $user->chavesApi()
            ->where('is_active', true)
            ->whereNull('expires_at')
            ->update(['is_active' => false]);

        // Cria nova chave
        $novaChave = ChaveApi::gerarChave();
        $user->chavesApi()->create([
            'name' => 'Chave Principal',
            'key' => $novaChave,
            'expires_at' => null,
        ]);

        return back()->with('apiKey', $novaChave);
    }

    /**
     * Salva a URL de callback de notificações de sitemap.
     */
    public function saveCallbackUrl(Request $request)
    {
        $request->validate([
            'callback_url' => 'nullable|url|max:500',
        ]);

        $request->user()->update([
            'api_callback_url' => $request->callback_url,
        ]);

        return back()->with('success', 'URL de callback salva com sucesso.');
    }
}
