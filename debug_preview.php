<?php

use App\Models\SitemapJob;
use App\Services\SitemapGeneratorService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$job = SitemapJob::latest()->first();

if (!$job) {
    echo "Nenhum job encontrado no banco de dados.\n";
    exit;
}

echo "Job ID: " . $job->external_job_id . "\n";
echo "Artifacts (JSON): " . json_encode($job->artifacts, JSON_PRETTY_PRINT) . "\n";

$service = new SitemapGeneratorService();
echo "Tentando gerar preview...\n";

// Mock da função getPreviewUrls se necessário, mas vamos chamar a real
$preview = $service->getPreviewUrls($job->artifacts ?? [], $job->external_job_id);

echo "Preview count: " . count($preview) . "\n";
if (empty($preview)) {
    echo "Preview vazio! Tentando debug de getFileContent...\n";

    foreach ($job->artifacts as $art) {
        echo "Check artifact: " . $art['name'] . "\n";
        $content = $service->getFileContent($job->external_job_id, $art['name']);
        if ($content) {
            echo " - Content found (" . strlen($content) . " bytes)\n";
            echo " - Start: " . substr($content, 0, 50) . "...\n";
        } else {
            echo " - Content NOT found (getFileContent returned null)\n";
            // Tenta ver se existe o arquivo no disco
            $basePath = base_path('../api-sitemap/sitemaps/' . $job->external_job_id . '/');
            $filePath = $basePath . $art['name'];
            echo " - Checking path: $filePath\n";
            if (file_exists($filePath))
                echo "   - File EXISTS on disk.\n";
            else
                echo "   - File DOES NOT EXIST on disk.\n";

            if (file_exists($filePath . '.zip'))
                echo "   - File.zip EXISTS on disk.\n";
        }
    }
} else {
    print_r(array_slice($preview, 0, 3));
}
