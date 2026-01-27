<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

echo "--- TESTE DE DOWNLOAD VIA PROXY LARAVEL ---\n";

$jobId = 'bbedaab8-be9a-446c-8995-2e99cd11e9f3';
$filename = 'sitemap.xml';

// Como a rota está protegida por auth, vou simular um usuário logado
$user = \App\Models\User::first();
if (!$user) {
    die("Erro: Nenhum usuário encontrado no BD para simular login.\n");
}
auth()->login($user);

// Gerar a URL usando o helper do Laravel
$url = route('downloads.sitemap', ['jobId' => $jobId, 'filename' => $filename]);
echo "URL gerada: $url\n";

// Em ambiente local/sail, o 'localhost' pode variar. Vou tentar converter para a URL real se necessário.
// Mas para o teste interno, o Http::get deve funcionar se o servidor estiver rodando.

try {
    // Como estamos dentro do mesmo processo PHP, podemos chamar o controlador diretamente ou usar Http::get se o server estiver UP.
    // Vamos tentar via Http::get primeiro (presumindo que o server local está na porta 80 ou similar)
    // Se falhar (ex: conexão recusada), tentaremos instanciar o controller.

    echo "Testando requisição HTTP...\n";
    $response = Http::get($url);

    echo "Status code: " . $response->status() . "\n";

    if ($response->successful()) {
        echo "Sucesso! Recebido " . strlen($response->body()) . " bytes.\n";
        echo "Content-Type: " . $response->header('Content-Type') . "\n";
        echo "Primeiros 50 caracteres: " . substr($response->body(), 0, 50) . "\n";
    } else {
        echo "Falha via HTTP (isso é comum se o server web não estiver rodando no CLI).\n";
        echo "Tentando chamada direta ao Controller...\n";

        $controller = app(\App\Http\Controllers\DownloadController::class);
        $result = $controller->sitemap($jobId, $filename);

        echo "Status da resposta do Controller: " . $result->getStatusCode() . "\n";
        if ($result->getStatusCode() === 200) {
            echo "Sucesso via Controller! Tamanho do corpo: " . strlen($result->getContent()) . " bytes.\n";
            echo "Primeiros 50 caracteres: " . substr($result->getContent(), 0, 50) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";

    echo "Tentando fallback direto ao Controller...\n";
    try {
        $controller = app(\App\Http\Controllers\DownloadController::class);
        $result = $controller->sitemap($jobId, $filename);
        echo "Status: " . $result->getStatusCode() . "\n";
        echo "Sucesso via Controller Fallback!\n";
    } catch (\Exception $e2) {
        echo "Erro no fallback: " . $e2->getMessage() . "\n";
    }
}

echo "--- FIM DO TESTE ---\n";
