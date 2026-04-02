<?xml version="1.0" encoding="UTF-8"?>
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
>
@foreach ($paginas as $pagina)
    <url>
        <loc>{{ $pagina['loc'] }}</loc>
        <lastmod>{{ $ultimaAtualizacao }}</lastmod>
@foreach ($pagina['alternativas'] as $idioma => $urlAlternativa)
        <xhtml:link rel="alternate" hreflang="{{ $idioma }}" href="{{ $urlAlternativa }}" />
@endforeach
    </url>
@endforeach
</urlset>
