<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICAÇÃO DO JOB d5b34df6 ===\n\n";

// 1. Verificar se arquivo existe no disco
$paths = [
    base_path('../api-sitemap/sitemaps/projects/4/sitemap.xml'),
    base_path('../api-sitemap/sitemaps/d5b34df6-d113-4d08-8e2e-2a20af0b2ebe/sitemap.xml'),
];
echo "--- Arquivos no disco ---\n";
foreach ($paths as $p) {
    echo ($p . ': ' . (file_exists($p) ? 'ENCONTRADO ('.filesize($p).' bytes)' : 'NÃO ENCONTRADO') . "\n");
}

// 2. Verificar jobs na fila (failed_jobs)
echo "\n--- Failed Jobs ---\n";
$failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
    ->where('payload', 'like', '%d5b34df6%')
    ->orderBy('failed_at', 'desc')
    ->get();
echo "Quantidade de failed jobs para este job_id: " . $failedJobs->count() . "\n";
foreach ($failedJobs as $fj) {
    echo "  failed_at: " . $fj->failed_at . "\n";
    echo "  exception: " . substr($fj->exception, 0, 300) . "\n";
    echo "---\n";
}

// 3. Verificar últimas linhas do log relevantes
echo "\n--- Últimas entradas de log relacionadas ao job ---\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    if (preg_match_all('/\[.*?\].*?d5b34df6.*?\n/s', $content, $matches)) {
        $relevant = array_slice($matches[0], -10);
        foreach ($relevant as $line) {
            echo $line;
        }
    } else {
        echo "Nenhuma entrada de log encontrada para este job_id.\n";
    }
} else {
    echo "Arquivo de log não encontrado.\n";
}
