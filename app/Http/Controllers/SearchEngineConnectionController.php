<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Models\SearchEngineConnection;
use App\Services\BingWebmasterService;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchEngineConnectionController extends Controller
{
    public function redirectToGoogle(Request $request, GoogleSearchConsoleService $googleSearchConsoleService)
    {
        $redirectProjetoId = $request->integer('project');
        $redirectRoute = route('dashboard');

        if ($redirectProjetoId) {
            $projeto = Projeto::findOrFail($redirectProjetoId);

            abort_unless($projeto->user_id === $request->user()->id, 403);

            $redirectRoute = route('projects.show', $projeto);
        }

        $request->session()->put('search_engines.google_redirect_to', $redirectRoute);

        return $googleSearchConsoleService->redirectResponse();
    }

    public function handleGoogleCallback(Request $request, GoogleSearchConsoleService $googleSearchConsoleService): RedirectResponse
    {
        $redirectRoute = $request->session()->pull('search_engines.google_redirect_to', route('dashboard'));

        try {
            $googleSearchConsoleService->connectFromCallback($request->user());

            return redirect()->to($redirectRoute)->with('success', 'Google Search Console conectado com sucesso.');
        } catch (\Throwable $exception) {
            return redirect()->to($redirectRoute)->with('error', 'Nao foi possivel conectar o Google Search Console.');
        }
    }

    public function disconnectGoogle(Request $request): JsonResponse
    {
        SearchEngineConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'google')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Google Search Console desconectado.',
        ]);
    }

    public function storeBingKey(Request $request, BingWebmasterService $bingWebmasterService): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'required|string|min:20|max:255',
        ]);

        try {
            $sites = $bingWebmasterService->listSites($validated['api_key']);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Nao foi possivel validar a API key do Bing Webmaster Tools.',
            ], 422);
        }

        SearchEngineConnection::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'provider' => 'bing',
            ],
            [
                'api_key' => $validated['api_key'],
                'meta' => [
                    'sites_count' => count($sites),
                ],
                'connected_at' => now(),
                'last_synced_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Chave do Bing Webmaster Tools validada e salva.',
            'sites_count' => count($sites),
            'connection' => [
                'connected' => true,
                'label' => $this->maskSecret($validated['api_key']),
                'connected_at' => now()->toISOString(),
            ],
        ]);
    }

    public function destroyBingKey(Request $request): JsonResponse
    {
        SearchEngineConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'bing')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Integracao com o Bing Webmaster Tools removida.',
        ]);
    }

    protected function maskSecret(string $value): string
    {
        $visible = substr($value, -6);

        return str_repeat('*', max(strlen($value) - 6, 8)) . $visible;
    }
}
