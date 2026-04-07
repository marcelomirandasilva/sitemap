<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DefinirIdiomaPublico
{
    private const IDIOMAS_SUPORTADOS = ['pt', 'en'];

    /**
     * Define o idioma do site público a partir da rota.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idioma = $request->route('locale');

        if (!is_string($idioma) || !in_array($idioma, self::IDIOMAS_SUPORTADOS, true)) {
            $idioma = config('app.locale', 'pt');
        }

        app()->setLocale($idioma);

        return $next($request);
    }
}
