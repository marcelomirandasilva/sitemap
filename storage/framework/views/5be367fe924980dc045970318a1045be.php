<?php
    $seo = $page['props']['seo'] ?? null;
    $tituloSeo = $seo['title'] ?? config('app.name', 'Laravel');
    $descricaoSeo = $seo['description'] ?? null;
    $canonicalSeo = $seo['canonical'] ?? null;
    $robotsSeo = $seo['robots'] ?? null;
    $alternativasSeo = $seo['alternativas'] ?? [];
    $idiomaPagina = $page['props']['locale'] ?? app()->getLocale();
    $nomeAplicacao = config('app.name', 'Laravel');
    $tituloCompleto = $tituloSeo === $nomeAplicacao ? $tituloSeo : $tituloSeo . ' - ' . $nomeAplicacao;
?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', $idiomaPagina)); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0E4F8C">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <title inertia><?php echo e($tituloCompleto); ?></title>
        <?php if($descricaoSeo): ?>
            <meta inertia head-key="description" name="description" content="<?php echo e($descricaoSeo); ?>">
            <meta inertia head-key="og:description" property="og:description" content="<?php echo e($descricaoSeo); ?>">
            <meta inertia head-key="twitter:description" name="twitter:description" content="<?php echo e($descricaoSeo); ?>">
        <?php endif; ?>
        <?php if($robotsSeo): ?>
            <meta inertia head-key="robots" name="robots" content="<?php echo e($robotsSeo); ?>">
        <?php endif; ?>
        <?php if($canonicalSeo): ?>
            <link inertia head-key="canonical" rel="canonical" href="<?php echo e($canonicalSeo); ?>">
            <meta inertia head-key="og:url" property="og:url" content="<?php echo e($canonicalSeo); ?>">
        <?php endif; ?>
        <meta inertia head-key="og:type" property="og:type" content="website">
        <meta inertia head-key="og:title" property="og:title" content="<?php echo e($tituloSeo); ?>">
        <meta inertia head-key="twitter:card" name="twitter:card" content="summary_large_image">
        <meta inertia head-key="twitter:title" name="twitter:title" content="<?php echo e($tituloSeo); ?>">
        <?php $__currentLoopData = $alternativasSeo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idiomaAlternativo => $urlAlternativa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <link inertia head-key="hreflang-<?php echo e($idiomaAlternativo); ?>" rel="alternate" hreflang="<?php echo e($idiomaAlternativo); ?>" href="<?php echo e($urlAlternativa); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"]); ?>
        <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
    </head>
    <body class="font-sans antialiased bg-bg-page text-text-primary">
        <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } elseif (config('inertia.use_script_element_for_initial_page')) { ?><script data-page="app" type="application/json"><?php echo json_encode($page); ?></script><div id="app"></div><?php } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
    </body>
</html>
<?php /**PATH D:\www\sitemap\resources\views/app.blade.php ENDPATH**/ ?>