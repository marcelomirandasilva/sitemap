<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SitemapGeneratorService;

echo "--- TESTE DE PREVIEW (ZIP EXTRACTION) --- \n";

$service = app(SitemapGeneratorService::class);
$jobId = 'bbedaab8-be9a-446c-8995-2e99cd11e9f3';

$artifacts = [
    ['name' => 'sitemap.xml', 'download_url' => 'fake']
];

echo "Buscando preview para Job: $jobId\n";

$preview = $service->getPreviewUrls($artifacts, $jobId);

echo "Total de URLs no preview: " . count($preview) . "\n";

if (count($preview) > 0) {
    echo "Primeira URL: " . $preview[0]['url'] . "\n";
    echo "Sucesso! A extração do ZIP e leitura do TXT funcionou.\n";
} else {
    echo "Falha: Nenhum preview gerado. Verifique se o urllist.txt.zip existe ou se a extração falhou.\n";
}

echo "--- FIM DO TESTE ---\n";
