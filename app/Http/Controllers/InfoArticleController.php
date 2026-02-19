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
            'privacy-policy' => [
                'title_key' => 'legal.privacy.title',
                'page_title_key' => 'legal.privacy.page_title',
                'section_title_key' => 'legal.privacy.intro_title',
                'paragraphs_keys' => [
                    'legal.privacy.intro_p1',
                    'legal.privacy.intro_p2',
                    'legal.privacy.intro_p3',
                ],
                'sections' => [
                    [
                        'subtitle_key' => 'legal.privacy.collect_title',
                        'paragraphs_keys' => [
                            'legal.privacy.collect_p1',
                            'legal.privacy.collect_p2',
                            'legal.privacy.collect_p3',
                            'legal.privacy.collect_p4',
                            'legal.privacy.collect_p5',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.privacy.security_title',
                        'paragraphs_keys' => [
                            'legal.privacy.security_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.privacy.cookies_title',
                        'paragraphs_keys' => [
                            'legal.privacy.cookies_p1',
                            'legal.privacy.cookies_p2',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.privacy.external_title',
                        'paragraphs_keys' => [
                            'legal.privacy.external_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.privacy.control_title',
                        'paragraphs_keys' => [
                            'legal.privacy.control_p1',
                            'legal.privacy.control_p2',
                            'legal.privacy.control_p3',
                            'legal.privacy.control_p4',
                        ],
                    ],
                ],
            ],
            'terms-of-use' => [
                'title_key' => 'legal.terms.title',
                'page_title_key' => 'legal.terms.page_title',
                'section_title_key' => 'legal.terms.intro_title',
                'paragraphs_keys' => [
                    'legal.terms.intro_p1',
                    'legal.terms.intro_p2',
                    'legal.terms.intro_p3',
                ],
                'sections' => [
                    [
                        'subtitle_key' => 'legal.terms.website_title',
                        'paragraphs_keys' => [
                            'legal.terms.website_p1',
                            'legal.terms.website_p2',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.links_title',
                        'paragraphs_keys' => [
                            'legal.terms.links_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.content_title',
                        'paragraphs_keys' => [
                            'legal.terms.content_p1',
                            'legal.terms.content_p2',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.liability_title',
                        'paragraphs_keys' => [
                            'legal.terms.liability_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.ip_title',
                        'paragraphs_keys' => [
                            'legal.terms.ip_p1',
                            'legal.terms.ip_p2',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.modification_title',
                        'paragraphs_keys' => [
                            'legal.terms.modification_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.subscription_title',
                        'paragraphs_keys' => [
                            'legal.terms.subscription_p1',
                            'legal.terms.subscription_p2',
                            'legal.terms.subscription_p3',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.refunds_title',
                        'paragraphs_keys' => [
                            'legal.terms.refunds_p1',
                            'legal.terms.refunds_p2',
                            'legal.terms.refunds_p3',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.upgrades_title',
                        'paragraphs_keys' => [
                            'legal.terms.upgrades_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.external_title',
                        'paragraphs_keys' => [
                            'legal.terms.external_p1',
                        ],
                    ],
                    [
                        'subtitle_key' => 'legal.terms.general_title',
                        'paragraphs_keys' => [
                            'legal.terms.general_p1',
                            'legal.terms.general_p2',
                            'legal.terms.general_p3',
                            'legal.terms.general_p4',
                        ],
                    ],
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
