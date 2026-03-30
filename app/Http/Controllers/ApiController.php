<?php

namespace App\Http\Controllers;

use App\Models\ChaveApi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiController extends Controller
{
    protected function userHasExternalApiAccess($user): bool
    {
        $user->loadMissing('plano');

        $subscription = $user->subscription('default');
        $subscriptionActive = $subscription && in_array($subscription->stripe_status, ['active', 'trialing'], true);
        $trialActive = method_exists($user, 'onTrial') ? (bool) $user->onTrial() : false;

        return (bool) ($user->plano?->has_advanced_features) && ($subscriptionActive || $trialActive);
    }
    /**
     * Exibe a página de API do usuário.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('plano');

        $subscription = $user->subscription('default');
        $subscriptionActive = $subscription && in_array($subscription->stripe_status, ['active', 'trialing'], true);
        $trialActive = method_exists($user, 'onTrial') ? (bool) $user->onTrial() : false;

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
            ->select('id', 'url', 'max_depth', 'max_pages', 'check_images', 'check_videos', 'delay_between_requests', 'max_concurrent_requests')
            ->orderBy('url')
            ->get();

        return Inertia::render('Account/Api', [
            'apiKey' => $chaveAtiva ? $chaveAtiva->key : null,
            'endpointUrl' => rtrim(config('services.sitemap_generator.base_url'), '/') . '/api/v1',
            'callbackUrl' => $user->api_callback_url,
            'projetos' => $projetos,
            'podeAcessarApi' => $this->userHasExternalApiAccess($user),
            'temPlanoApi' => (bool) ($user->plano?->has_advanced_features),
            'assinaturaAtivaApi' => $subscriptionActive || $trialActive,
        ]);
    }

    /**
     * Gera uma nova API Key, revogando a anterior.
     */
    public function resetKey(Request $request)
    {
        $user = $request->user();

        // Revoga todas as chaves ativas sem expiração (chave principal)
        if (!$this->userHasExternalApiAccess($user)) {
            abort(403, 'Seu plano ou assinatura atual nao permite o uso da API externa.');
        }

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
        if (!$this->userHasExternalApiAccess($request->user())) {
            abort(403, 'Seu plano ou assinatura atual nao permite o uso da API externa.');
        }

        $request->validate([
            'callback_url' => 'nullable|url|max:500',
        ]);

        $request->user()->update([
            'api_callback_url' => $request->callback_url,
        ]);

        return back()->with('success', 'URL de callback salva com sucesso.');
    }
}
