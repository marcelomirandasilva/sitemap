<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SitemapGeneratorService;
use Illuminate\Support\Facades\Log;

echo "--- INICIANDO TESTE DE API ---\n";

$service = app(SitemapGeneratorService::class);
$jobId = 'bbedaab8-be9a-446c-8995-2e99cd11e9f3'; // ID do screenshot recente

echo "Consultando STATUS para o Job: $jobId\n";

try {
    $status = $service->checkStatus($jobId);

    if ($status) {
        echo "RESPOSTA DA API:\n";
        echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        if (isset($status['artifacts'])) {
            echo "ARTEFATOS ENCONTRADOS NO STATUS: " . count($status['artifacts']) . "\n";
        } else {
            echo "AVISO: No campo 'artifacts' na raiz da resposta.\n";
        }

        if (isset($status['result'])) {
            echo "DETALHES DO RESULTADO:\n";
            print_r($status['result']);
        }
    } else {
        echo "ERRO: A API retornou NULL ou falha na conexão.\n";
    }
} catch (\Exception $e) {
    echo "EXCEÇÃO: " . $e->getMessage() . "\n";
}

echo "\n--- FIM DO TESTE ---\n";
