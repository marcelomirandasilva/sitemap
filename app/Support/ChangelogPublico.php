<?php

namespace App\Support;

use App\Models\RegistroChangelog;
use Illuminate\Support\Facades\Schema;

class ChangelogPublico
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itens(string $locale): array
    {
        $localeNormalizado = SeoPublico::normalizarLocale($locale);

        $registros = self::buscarNoBanco($localeNormalizado);

        if (!empty($registros)) {
            return $registros;
        }

        return $localeNormalizado === 'en'
            ? self::itensFallbackEmIngles()
            : self::itensFallbackEmPortugues();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function buscarNoBanco(string $locale): array
    {
        if (!Schema::hasTable('registros_changelog')) {
            return [];
        }

        return RegistroChangelog::query()
            ->publicados()
            ->orderByDesc('data_lancamento')
            ->orderByDesc('ordem_exibicao')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RegistroChangelog $registro) => $registro->paraExibicao($locale))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function itensFallbackEmPortugues(): array
    {
        return [
            [
                'versao' => '1.3.0',
                'data' => '2026-04-15',
                'categoria' => 'Produto',
                'titulo' => 'Changelog publico inaugurado',
                'resumo' => 'Abrimos uma pagina publica para registrar entregas, ajustes estruturais e marcos operacionais do GenMap.',
                'itens' => [
                    'Nova rota publica dedicada ao changelog em portugues e ingles.',
                    'Estrutura inicial preparada para publicar versoes e comunicados de produto.',
                    'Base criada para a futura pagina publica de status seguir o mesmo padrao de transparencia.',
                ],
            ],
            [
                'versao' => '1.2.0',
                'data' => '2026-04-14',
                'categoria' => 'Crawler',
                'titulo' => 'Controles avancados por plano e recorrencia customizada',
                'resumo' => 'O painel passou a refletir melhor os limites reais por plano, incluindo a base para recorrencia customizada do Enterprise.',
                'itens' => [
                    'Limites efetivos de paginas, profundidade, concorrencia e atraso aplicados conforme o plano.',
                    'Frequencia customizada por projeto habilitada para o plano Enterprise.',
                    'Agendamento automatico conectado ao scheduler do Laravel.',
                ],
            ],
            [
                'versao' => '1.1.0',
                'data' => '2026-04-07',
                'categoria' => 'Infraestrutura',
                'titulo' => 'Notificacoes em tempo real e estabilizacao do rastreamento',
                'resumo' => 'A stack de notificacoes em tempo real foi conectada e o fluxo entre Laravel e API Python recebeu ajustes para maior estabilidade.',
                'itens' => [
                    'Notificacoes em tempo real ativadas na aplicacao.',
                    'Fluxo de rastreamento revisto para reduzir travamentos e falhas de inicio.',
                    'Documentacao operacional atualizada com os processos necessarios para o ambiente.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function itensFallbackEmIngles(): array
    {
        return [
            [
                'versao' => '1.3.0',
                'data' => '2026-04-15',
                'categoria' => 'Product',
                'titulo' => 'Public changelog launched',
                'resumo' => 'We added a public page to register releases, structural adjustments and operational milestones for GenMap.',
                'itens' => [
                    'New public changelog route available in Portuguese and English.',
                    'Initial structure prepared for publishing product releases and notices.',
                    'Foundation created for the upcoming public status page to follow the same transparency model.',
                ],
            ],
            [
                'versao' => '1.2.0',
                'data' => '2026-04-14',
                'categoria' => 'Crawler',
                'titulo' => 'Advanced per-plan controls and custom recurrence',
                'resumo' => 'The dashboard now reflects real plan limits more accurately, including the base for custom Enterprise recurrence.',
                'itens' => [
                    'Effective limits for pages, depth, concurrency and delay now follow each plan.',
                    'Custom per-project recurrence enabled for the Enterprise plan.',
                    'Automatic scheduling connected to the Laravel scheduler.',
                ],
            ],
            [
                'versao' => '1.1.0',
                'data' => '2026-04-07',
                'categoria' => 'Infrastructure',
                'titulo' => 'Real-time notifications and crawl stability improvements',
                'resumo' => 'The real-time notification stack was connected and the Laravel to Python API flow received stability improvements.',
                'itens' => [
                    'Real-time notifications enabled in the application.',
                    'Crawl start flow revised to reduce startup failures and stalls.',
                    'Operational documentation updated with required environment processes.',
                ],
            ],
        ];
    }
}
