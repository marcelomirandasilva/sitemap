<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoSiteController extends Controller
{
    private const IDIOMAS = ['pt', 'en'];
    private const IDIOMA_PADRAO = 'pt';
    private const SLUGS_ARTIGOS = [
        'about-sitemaps',
        'broken-links',
        'images-sitemap',
        'video-sitemap',
        'news-sitemap',
        'html-sitemap',
        'rss-feed',
        'text-sitemap',
        'mobile-sitemap',
        'privacy-policy',
        'terms-of-use',
    ];

    public function robots(): Response
    {
        $linhas = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /projects',
            'Disallow: /gestao',
            'Disallow: /account',
            'Disallow: /profile',
            'Disallow: /support',
            'Disallow: /dev',
            'Disallow: /subscription',
            'Disallow: /billing',
            'Disallow: /api/internal',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /verify-email',
            'Sitemap: ' . route('seo.sitemap'),
        ];

        return response(implode("\n", $linhas) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $paginas = [];

        foreach (self::IDIOMAS as $idioma) {
            $paginas[] = [
                'loc' => route('public.landing', ['locale' => $idioma]),
                'alternativas' => $this->alternativasLanding(),
            ];

            foreach (self::SLUGS_ARTIGOS as $slug) {
                $paginas[] = [
                    'loc' => route('info.article', ['locale' => $idioma, 'slug' => $slug]),
                    'alternativas' => $this->alternativasArtigo($slug),
                ];
            }
        }

        $xml = view('seo.sitemap', [
            'paginas' => $paginas,
            'ultimaAtualizacao' => now()->toAtomString(),
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function alternativasLanding(): array
    {
        return [
            'pt' => route('public.landing', ['locale' => 'pt']),
            'en' => route('public.landing', ['locale' => 'en']),
            'x-default' => route('public.landing', ['locale' => self::IDIOMA_PADRAO]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function alternativasArtigo(string $slug): array
    {
        return [
            'pt' => route('info.article', ['locale' => 'pt', 'slug' => $slug]),
            'en' => route('info.article', ['locale' => 'en', 'slug' => $slug]),
            'x-default' => route('info.article', ['locale' => self::IDIOMA_PADRAO, 'slug' => $slug]),
        ];
    }
}
