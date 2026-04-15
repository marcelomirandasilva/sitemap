<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Support\ChangelogPublico;
use App\Support\SeoPublico;
use App\Services\StatusPublicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class PaginaPublicaController extends Controller
{
    private const IDIOMAS_SUPORTADOS = ['pt', 'en'];
    private const IDIOMA_PADRAO = 'pt';

    public function __construct(
        protected StatusPublicoService $statusPublicoService,
    ) {
    }

    public function redirecionarRaiz(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $idiomaPreferido = $request->getPreferredLanguage(self::IDIOMAS_SUPORTADOS);
        $idioma = in_array($idiomaPreferido, self::IDIOMAS_SUPORTADOS, true)
            ? $idiomaPreferido
            : self::IDIOMA_PADRAO;

        return redirect()->route('public.landing', ['locale' => $idioma]);
    }

    public function landing(Request $request, string $locale): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        app()->setLocale($locale);

        return Inertia::render('Public/LandingPage', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'plans' => Plano::all(),
            'locale' => $locale,
            'seo' => SeoPublico::dadosLanding($locale),
        ]);
    }

    public function redirecionarChangelog(Request $request): RedirectResponse
    {
        $idiomaPreferido = $request->getPreferredLanguage(self::IDIOMAS_SUPORTADOS);
        $idioma = in_array($idiomaPreferido, self::IDIOMAS_SUPORTADOS, true)
            ? $idiomaPreferido
            : self::IDIOMA_PADRAO;

        return redirect()->route('public.changelog', ['locale' => $idioma]);
    }

    public function changelog(Request $request, string $locale): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        app()->setLocale($locale);

        return Inertia::render('Public/ChangelogPage', [
            'locale' => $locale,
            'itens' => ChangelogPublico::itens($locale),
            'seo' => SeoPublico::dadosChangelog($locale),
        ]);
    }

    public function redirecionarStatus(Request $request): RedirectResponse
    {
        $idiomaPreferido = $request->getPreferredLanguage(self::IDIOMAS_SUPORTADOS);
        $idioma = in_array($idiomaPreferido, self::IDIOMAS_SUPORTADOS, true)
            ? $idiomaPreferido
            : self::IDIOMA_PADRAO;

        return redirect()->route('public.status', ['locale' => $idioma]);
    }

    public function status(Request $request, string $locale): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        app()->setLocale($locale);

        return Inertia::render('Public/StatusPage', [
            'locale' => $locale,
            'status' => $this->statusPublicoService->montarStatus(),
            'seo' => SeoPublico::dadosStatus($locale),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function alternativasLanding(): array
    {
        return SeoPublico::alternativasLanding();
    }
}
