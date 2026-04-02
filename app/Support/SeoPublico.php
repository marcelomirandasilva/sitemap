<?php

namespace App\Support;

class SeoPublico
{
    /**
     * @var array<int, string>
     */
    private const IDIOMAS_SUPORTADOS = ['pt', 'en'];

    private const IDIOMA_PADRAO = 'pt';

    public static function normalizarLocale(?string $locale): string
    {
        $localeNormalizado = mb_strtolower((string) $locale);

        foreach (self::IDIOMAS_SUPORTADOS as $idioma) {
            if (str_starts_with($localeNormalizado, $idioma)) {
                return $idioma;
            }
        }

        return self::IDIOMA_PADRAO;
    }

    /**
     * @return array<string, string>
     */
    public static function alternativasLanding(): array
    {
        return [
            'pt' => route('public.landing', ['locale' => 'pt']),
            'en' => route('public.landing', ['locale' => 'en']),
            'x-default' => route('public.landing', ['locale' => self::IDIOMA_PADRAO]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dadosLanding(string $locale): array
    {
        $localeNormalizado = self::normalizarLocale($locale);

        return [
            'title' => __('seo_publico.home.title'),
            'description' => __('seo_publico.home.description'),
            'canonical' => route('public.landing', ['locale' => $localeNormalizado]),
            'robots' => 'index,follow,max-image-preview:large',
            'alternativas' => self::alternativasLanding(),
        ];
    }
}
