<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SitemapGeneratorService;

class TestSitemapApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a conexão e autenticação com a API de Sitemaps';

    /**
     * Execute the console command.
     */
    public function handle(SitemapGeneratorService $service)
    {
        $this->info('Iniciando teste de conexão com a API...');

        $config = config('services.sitemap_generator');
        $this->line("URL Configurada:  " . ($config['base_url'] ?? 'N/A'));
        $this->line("Usuário: " . ($config['username'] ?? 'N/A'));

        $this->newLine();

        $result = $service->testConnection();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            $this->line("Status: " . $result['status']);
            $this->line("Duração: " . $result['duration_ms'] . "ms");
            $this->line("Token preview: " . ($result['token_preview'] ?? 'N/A'));
        } else {
            $this->error('❌ ' . $result['message']);
            $this->line("Status: " . ($result['status'] ?? 'N/A'));
            $this->line("Duração: " . ($result['duration_ms'] ?? 0) . "ms");

            if (isset($result['debug_creds'])) {
                $this->table(['Debug Info', 'Value'], [
                    ['User', $result['debug_creds']['user'] ?? ''],
                    ['Base URL', $result['base_url'] ?? ''],
                ]);
            }

            if (isset($result['response_body'])) {
                $this->newLine();
                $this->line("Resposta da API:");
                $this->warn(substr(json_encode($result['response_body']), 0, 500));
            }
        }
    }
}
