<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class InfoArticleController extends Controller
{
    /**
     * Mapa de artigos informativos.
     * Cada chave é o slug da URL.
     */
    private function getArticles(): array
    {
        return [
            'about-sitemaps' => [
                'title_key' => 'about_sitemaps.title',
                'page_title_key' => 'about_sitemaps.page_title',
                'section_title_key' => 'about_sitemaps.section1_title',
                'paragraphs_keys' => [
                    'about_sitemaps.p1',
                    'about_sitemaps.p2',
                    'about_sitemaps.p3',
                    'about_sitemaps.p4',
                    'about_sitemaps.p5',
                    'about_sitemaps.p6',
                    'about_sitemaps.p7',
                ],
            ],
            'broken-links' => [
                'title_key' => 'articles.broken_links.title',
                'page_title_key' => 'articles.broken_links.page_title',
                'section_title_key' => 'articles.broken_links.section_title',
                'paragraphs_keys' => [
                    'articles.broken_links.p1',
                    'articles.broken_links.p2',
                    'articles.broken_links.p3',
                    'articles.broken_links.p4',
                    'articles.broken_links.p5',
                    'articles.broken_links.p6',
                    'articles.broken_links.p7',
                ],
            ],
            'images-sitemap' => [
                'title_key' => 'articles.images_sitemap.title',
                'page_title_key' => 'articles.images_sitemap.page_title',
                'section_title_key' => 'articles.images_sitemap.section_title',
                'paragraphs_keys' => [
                    'articles.images_sitemap.p1',
                    'articles.images_sitemap.p2',
                    'articles.images_sitemap.p3',
                ],
            ],
            'video-sitemap' => [
                'title_key' => 'articles.video_sitemap.title',
                'page_title_key' => 'articles.video_sitemap.page_title',
                'section_title_key' => 'articles.video_sitemap.section_title',
                'paragraphs_keys' => [
                    'articles.video_sitemap.p1',
                    'articles.video_sitemap.p2',
                    'articles.video_sitemap.p3',
                ],
            ],
            'news-sitemap' => [
                'title_key' => 'articles.news_sitemap.title',
                'page_title_key' => 'articles.news_sitemap.page_title',
                'section_title_key' => 'articles.news_sitemap.section_title',
                'paragraphs_keys' => [
                    'articles.news_sitemap.p1',
                    'articles.news_sitemap.p2',
                    'articles.news_sitemap.p3',
                ],
            ],
            'html-sitemap' => [
                'title_key' => 'articles.html_sitemap.title',
                'page_title_key' => 'articles.html_sitemap.page_title',
                'section_title_key' => 'articles.html_sitemap.section_title',
                'paragraphs_keys' => [
                    'articles.html_sitemap.p1',
                    'articles.html_sitemap.p2',
                    'articles.html_sitemap.p3',
                ],
            ],
            'rss-feed' => [
                'title_key' => 'articles.rss_feed.title',
                'page_title_key' => 'articles.rss_feed.page_title',
                'section_title_key' => 'articles.rss_feed.section_title',
                'paragraphs_keys' => [
                    'articles.rss_feed.p1',
                ],
            ],
            'text-sitemap' => [
                'title_key' => 'articles.text_sitemap.title',
                'page_title_key' => 'articles.text_sitemap.page_title',
                'section_title_key' => 'articles.text_sitemap.section_title',
                'paragraphs_keys' => [
                    'articles.text_sitemap.p1',
                    'articles.text_sitemap.p2',
                ],
            ],
            'mobile-sitemap' => [
                'title_key' => 'articles.mobile_sitemap.title',
                'page_title_key' => 'articles.mobile_sitemap.page_title',
                'section_title_key' => 'articles.mobile_sitemap.section_title',
                'paragraphs_keys' => [
                    'articles.mobile_sitemap.p1',
                    'articles.mobile_sitemap.p2',
                ],
            ],
        ];
    }

    public function show(string $slug)
    {
        $articles = $this->getArticles();

        if (!isset($articles[$slug])) {
            abort(404);
        }

        $article = $articles[$slug];

        // Slugs de todos os artigos para sidebar de artigos relacionados
        $allSlugs = array_keys($articles);

        return Inertia::render('Public/InfoArticle', [
            'article' => $article,
            'slug' => $slug,
            'allSlugs' => $allSlugs,
        ]);
    }
}
