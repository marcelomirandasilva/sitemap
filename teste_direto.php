<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- TESTE DE LEITURA DIRETA --- \n";

$jobId = 'bbedaab8-be9a-446c-8995-2e99cd11e9f3';
$filename = 'sitemap.xml.zip';

// Tentando localizar a pasta da API (assumindo que estão no mesmo diretório pai)
$apiPath = realpath(__DIR__ . '/../api-sitemap/sitemaps/' . $jobId . '/' . $filename);

echo "Caminho calculado: " . ($apiPath ?: 'NÃO ENCONTRADO') . "\n";

if ($apiPath && file_exists($apiPath)) {
    echo "Sucesso! O arquivo existe e tem " . filesize($apiPath) . " bytes.\n";

    // Se for zip, podemos tentar ler os primeiros bytes para confirmar
    $handle = fopen($apiPath, "rb");
    $content = fread($handle, 20);
    fclose($handle);
    echo "Header (Hex): " . bin2hex($content) . "\n";
} else {
    echo "Falha: Não foi possível acessar o arquivo diretamente.\n";
}

echo "--- FIM DO TESTE ---\n";
