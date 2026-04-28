<?php

namespace App\Services;

use App\Models\Projeto;
use App\Models\TarefaSitemap;
use Illuminate\Support\Facades\Log;

class ExecucaoRastreamentoService
{
    public function __construct(protected SitemapGeneratorService $sitemapService)
    {
    }

    public function localizarJobAtivo(Projeto $projeto): ?TarefaSitemap
    {
        return $projeto->tarefasSitemap()
            ->whereIn('status', ['queued', 'running'])
            ->latest()
            ->first();
    }

    public function iniciar(Projeto $projeto, string $mensagemInicial): array
    {
        $jobAtivo = $this->localizarJobAtivo($projeto);

        if ($jobAtivo) {
            return [
                'success' => false,
                'reason' => 'job_ativo',
                'job' => $jobAtivo,
                'message' => 'Ja existe um rastreamento em andamento para este projeto.',
            ];
        }

        $usuario = $projeto->user()->first();
        $planoEfetivo = $usuario?->planoEfetivo();
        $limitePlano = $planoEfetivo?->max_pages ?? 500;
        $limiteApi = $this->sitemapService->limiteMaximoPaginasApi();
        $limitePlanoEfetivo = $planoEfetivo?->limitePaginasEfetivo($limiteApi) ?? min($limitePlano, $limiteApi);
        $limiteEfetivo = min($projeto->max_pages ?? $limitePlanoEfetivo, $limitePlanoEfetivo);

        Log::info("ExecucaoRastreamentoService: iniciando job com limite de {$limiteEfetivo} paginas", [
            'project_id' => $projeto->id,
            'user_id' => $usuario?->id,
            'limite_plano' => $limitePlano,
            'limite_api' => $limiteApi,
            'limite_plano_efetivo' => $limitePlanoEfetivo,
            'limite_projeto' => $projeto->max_pages,
        ]);

        $permiteImagens = (bool) ($planoEfetivo?->permite_imagens);
        $permiteVideos = (bool) ($planoEfetivo?->permite_videos);
        $permiteOpcoesAvancadas = (bool) ($planoEfetivo?->has_advanced_features);
        $permiteNoticias = (bool) ($planoEfetivo?->has_advanced_features && $planoEfetivo?->permite_noticias);
        $permiteMobile = (bool) ($planoEfetivo?->has_advanced_features && $planoEfetivo?->permite_mobile);
        $permiteCompactacao = (bool) ($planoEfetivo?->has_advanced_features && $planoEfetivo?->permite_compactacao);
        $permiteCacheCrawler = (bool) ($planoEfetivo?->has_advanced_features && $planoEfetivo?->permite_cache_crawler);
        $permitePadroesExclusao = (bool) ($planoEfetivo?->has_advanced_features && $planoEfetivo?->permite_padroes_exclusao);
        $permitePoliticasCrawl = (bool) ($planoEfetivo?->has_advanced_features && $planoEfetivo?->permite_politicas_crawl);
        $ajustesProjeto = [];

        if ($projeto->check_images && !$permiteImagens) {
            $ajustesProjeto['check_images'] = false;
        }

        if ($projeto->check_videos && !$permiteVideos) {
            $ajustesProjeto['check_videos'] = false;
        }

        if (!$permiteOpcoesAvancadas) {
            if ((int) ($projeto->max_concurrent_requests ?? 0) !== SitemapGeneratorService::CONCORRENCIA_PADRAO_API) {
                $ajustesProjeto['max_concurrent_requests'] = SitemapGeneratorService::CONCORRENCIA_PADRAO_API;
            }

            if ((float) ($projeto->delay_between_requests ?? 0.0) !== SitemapGeneratorService::ATRASO_PADRAO_API) {
                $ajustesProjeto['delay_between_requests'] = SitemapGeneratorService::ATRASO_PADRAO_API;
            }

            if ($projeto->check_news) {
                $ajustesProjeto['check_news'] = false;
            }

            if ($projeto->check_mobile) {
                $ajustesProjeto['check_mobile'] = false;
            }

            if (!empty($projeto->exclude_patterns)) {
                $ajustesProjeto['exclude_patterns'] = null;
            }

            if (!empty($projeto->crawl_policy_id)) {
                $ajustesProjeto['crawl_policy_id'] = null;
            }

            if (($projeto->compress_output ?? true) === false) {
                $ajustesProjeto['compress_output'] = true;
            }

            if (($projeto->enable_cache ?? true) === false) {
                $ajustesProjeto['enable_cache'] = true;
            }
        } else {
            if ($projeto->check_news && !$permiteNoticias) {
                $ajustesProjeto['check_news'] = false;
            }

            if ($projeto->check_mobile && !$permiteMobile) {
                $ajustesProjeto['check_mobile'] = false;
            }

            if (!empty($projeto->exclude_patterns) && !$permitePadroesExclusao) {
                $ajustesProjeto['exclude_patterns'] = null;
            }

            if (!empty($projeto->crawl_policy_id) && !$permitePoliticasCrawl) {
                $ajustesProjeto['crawl_policy_id'] = null;
            }

            if (($projeto->compress_output ?? true) === false && !$permiteCompactacao) {
                $ajustesProjeto['compress_output'] = true;
            }

            if (($projeto->enable_cache ?? true) === false && !$permiteCacheCrawler) {
                $ajustesProjeto['enable_cache'] = true;
            }
        }

        if (!empty($ajustesProjeto)) {
            $projeto->update($ajustesProjeto);
            $projeto->refresh();
        }

        if (($projeto->max_pages ?? $limitePlanoEfetivo) > $limitePlanoEfetivo) {
            $projeto->update([
                'max_pages' => $limitePlanoEfetivo,
            ]);
            $projeto->refresh();
        }

        $limiteEfetivo = min($projeto->max_pages ?? $limitePlanoEfetivo, $limitePlanoEfetivo);

        $externalJobId = $this->sitemapService->startJob($projeto, $limiteEfetivo);

        if (!$externalJobId) {
            return [
                'success' => false,
                'reason' => 'falha_api',
                'message' => 'Falha ao iniciar o servico de crawler. Tente novamente.',
            ];
        }

        $job = $projeto->tarefasSitemap()->create([
            'external_job_id' => $externalJobId,
            'status' => 'queued',
            'started_at' => now(),
            'message' => $mensagemInicial,
        ]);

        return [
            'success' => true,
            'job' => $job->refresh(),
            'job_id' => $externalJobId,
            'status' => 'queued',
            'limite_paginas' => $limiteEfetivo,
        ];
    }
}
