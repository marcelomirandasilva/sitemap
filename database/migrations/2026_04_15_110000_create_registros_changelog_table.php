<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_changelog', function (Blueprint $table) {
            $table->id();
            $table->string('versao', 50);
            $table->date('data_lancamento');
            $table->integer('ordem_exibicao')->default(0);
            $table->boolean('publicado')->default(true);
            $table->string('categoria_pt', 120);
            $table->string('categoria_en', 120);
            $table->string('titulo_pt');
            $table->string('titulo_en');
            $table->text('resumo_pt');
            $table->text('resumo_en');
            $table->json('itens_pt')->nullable();
            $table->json('itens_en')->nullable();
            $table->timestamps();
        });

        DB::table('registros_changelog')->insert([
            [
                'versao' => '1.3.0',
                'data_lancamento' => '2026-04-15',
                'ordem_exibicao' => 30,
                'publicado' => true,
                'categoria_pt' => 'Produto',
                'categoria_en' => 'Product',
                'titulo_pt' => 'Changelog publico inaugurado',
                'titulo_en' => 'Public changelog launched',
                'resumo_pt' => 'Abrimos uma pagina publica para registrar entregas, ajustes estruturais e marcos operacionais do GenMap.',
                'resumo_en' => 'We added a public page to register releases, structural adjustments and operational milestones for GenMap.',
                'itens_pt' => json_encode([
                    'Nova rota publica dedicada ao changelog em portugues e ingles.',
                    'Estrutura inicial preparada para publicar versoes e comunicados de produto.',
                    'Base criada para a futura pagina publica de status seguir o mesmo padrao de transparencia.',
                ], JSON_UNESCAPED_UNICODE),
                'itens_en' => json_encode([
                    'New public changelog route available in Portuguese and English.',
                    'Initial structure prepared for publishing product releases and notices.',
                    'Foundation created for the upcoming public status page to follow the same transparency model.',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'versao' => '1.2.0',
                'data_lancamento' => '2026-04-14',
                'ordem_exibicao' => 20,
                'publicado' => true,
                'categoria_pt' => 'Crawler',
                'categoria_en' => 'Crawler',
                'titulo_pt' => 'Controles avancados por plano e recorrencia customizada',
                'titulo_en' => 'Advanced per-plan controls and custom recurrence',
                'resumo_pt' => 'O painel passou a refletir melhor os limites reais por plano, incluindo a base para recorrencia customizada do Enterprise.',
                'resumo_en' => 'The dashboard now reflects real plan limits more accurately, including the base for custom Enterprise recurrence.',
                'itens_pt' => json_encode([
                    'Limites efetivos de paginas, profundidade, concorrencia e atraso aplicados conforme o plano.',
                    'Frequencia customizada por projeto habilitada para o plano Enterprise.',
                    'Agendamento automatico conectado ao scheduler do Laravel.',
                ], JSON_UNESCAPED_UNICODE),
                'itens_en' => json_encode([
                    'Effective limits for pages, depth, concurrency and delay now follow each plan.',
                    'Custom per-project recurrence enabled for the Enterprise plan.',
                    'Automatic scheduling connected to the Laravel scheduler.',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'versao' => '1.1.0',
                'data_lancamento' => '2026-04-07',
                'ordem_exibicao' => 10,
                'publicado' => true,
                'categoria_pt' => 'Infraestrutura',
                'categoria_en' => 'Infrastructure',
                'titulo_pt' => 'Notificacoes em tempo real e estabilizacao do rastreamento',
                'titulo_en' => 'Real-time notifications and crawl stability improvements',
                'resumo_pt' => 'A stack de notificacoes em tempo real foi conectada e o fluxo entre Laravel e API Python recebeu ajustes para maior estabilidade.',
                'resumo_en' => 'The real-time notification stack was connected and the Laravel to Python API flow received stability improvements.',
                'itens_pt' => json_encode([
                    'Notificacoes em tempo real ativadas na aplicacao.',
                    'Fluxo de rastreamento revisto para reduzir travamentos e falhas de inicio.',
                    'Documentacao operacional atualizada com os processos necessarios para o ambiente.',
                ], JSON_UNESCAPED_UNICODE),
                'itens_en' => json_encode([
                    'Real-time notifications enabled in the application.',
                    'Crawl start flow revised to reduce startup failures and stalls.',
                    'Operational documentation updated with required environment processes.',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_changelog');
    }
};
