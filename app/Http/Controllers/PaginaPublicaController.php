<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Support\SeoPublico;
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

    /**
     * @return array<string, string>
     */
    public function alternativasLanding(): array
    {
        return SeoPublico::alternativasLanding();
    }
}
