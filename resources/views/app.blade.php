@php
    $seo = $page['props']['seo'] ?? null;
    $tituloSeo = $seo['title'] ?? config('app.name', 'Laravel');
    $descricaoSeo = $seo['description'] ?? null;
    $canonicalSeo = $seo['canonical'] ?? null;
    $robotsSeo = $seo['robots'] ?? null;
    $alternativasSeo = $seo['alternativas'] ?? [];
    $idiomaPagina = $page['props']['locale'] ?? app()->getLocale();
    $nomeAplicacao = config('app.name', 'Laravel');
    $tituloCompleto = $tituloSeo === $nomeAplicacao ? $tituloSeo : $tituloSeo . ' - ' . $nomeAplicacao;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $idiomaPagina) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0E4F8C">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <title inertia>{{ $tituloCompleto }}</title>
        @if ($descricaoSeo)
            <meta inertia head-key="description" name="description" content="{{ $descricaoSeo }}">
            <meta inertia head-key="og:description" property="og:description" content="{{ $descricaoSeo }}">
            <meta inertia head-key="twitter:description" name="twitter:description" content="{{ $descricaoSeo }}">
        @endif
        @if ($robotsSeo)
            <meta inertia head-key="robots" name="robots" content="{{ $robotsSeo }}">
        @endif
        @if ($canonicalSeo)
            <link inertia head-key="canonical" rel="canonical" href="{{ $canonicalSeo }}">
            <meta inertia head-key="og:url" property="og:url" content="{{ $canonicalSeo }}">
        @endif
        <meta inertia head-key="og:type" property="og:type" content="website">
        <meta inertia head-key="og:title" property="og:title" content="{{ $tituloSeo }}">
        <meta inertia head-key="twitter:card" name="twitter:card" content="summary_large_image">
        <meta inertia head-key="twitter:title" name="twitter:title" content="{{ $tituloSeo }}">
        @foreach ($alternativasSeo as $idiomaAlternativo => $urlAlternativa)
            <link inertia head-key="hreflang-{{ $idiomaAlternativo }}" rel="alternate" hreflang="{{ $idiomaAlternativo }}" href="{{ $urlAlternativa }}">
        @endforeach

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-bg-page text-text-primary">
        @inertia
    </body>
</html>
