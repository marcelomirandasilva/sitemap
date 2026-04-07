<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DefinirIdiomaAplicacao
{
    public function handle(Request $request, Closure $next): Response
    {
        $idioma = $this->resolverIdioma($request);

        app()->setLocale($idioma);
        $request->session()->put('app_locale', $idioma);

        return $next($request);
    }

    private function resolverIdioma(Request $request): string
    {
        $usuario = $request->user();
        $idiomaUsuario = $this->normalizarIdioma(data_get($usuario?->ui_preferences, 'locale'));

        if ($idiomaUsuario) {
            return $idiomaUsuario;
        }

        $idiomaSessao = $this->normalizarIdioma($request->session()->get('app_locale'));

        if ($idiomaSessao) {
            return $idiomaSessao;
        }

        return $this->normalizarIdioma(config('app.locale', 'pt')) ?? 'pt';
    }

    private function normalizarIdioma(?string $idioma): ?string
    {
        if (!$idioma) {
            return null;
        }

        $idiomaNormalizado = mb_strtolower(trim($idioma));

        if (str_starts_with($idiomaNormalizado, 'pt')) {
            return 'pt';
        }

        if (str_starts_with($idiomaNormalizado, 'en')) {
            return 'en';
        }

        return in_array($idiomaNormalizado, ['pt', 'en'], true)
            ? $idiomaNormalizado
            : null;
    }
}
