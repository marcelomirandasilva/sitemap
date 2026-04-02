<?php

namespace App\Console\Commands;

use App\Models\Projeto;
use App\Services\ExecucaoRastreamentoService;
use App\Services\FrequenciaRastreamentoService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AgendarRastreamentosProjetos extends Command
{
    protected $signature = 'projetos:agendar-rastreamentos
        {--projeto= : Processa apenas um projeto especifico}
        {--dry-run : Apenas informa o que seria feito}';

    protected $description = 'Dispara rastreamentos automaticos conforme a frequencia configurada em cada projeto.';

    public function __construct(
        protected FrequenciaRastreamentoService $frequenciaRastreamento,
        protected ExecucaoRastreamentoService $execucaoRastreamento
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $agora = Carbon::now();
        $dryRun = (bool) $this->option('dry-run');
        $projetoId = $this->option('projeto');

        $consulta = Projeto::query()
            ->with('user.plano')
            ->where('frequency', '!=', 'manual');

        if ($projetoId) {
            $consulta->whereKey($projetoId);
        }

        $processados = 0;
        $agendados = 0;
        $ignorados = 0;

        $consulta->orderBy('id')->chunkById(100, function ($projetos) use ($agora, $dryRun, &$processados, &$agendados, &$ignorados) {
            foreach ($projetos as $projeto) {
                $processados++;

                $usuario = $projeto->user;
                $planoEfetivo = $usuario?->planoEfetivo();

                $projeto = $this->frequenciaRastreamento->sincronizarFrequenciaProjeto($projeto, $planoEfetivo);
                $projeto = $this->frequenciaRastreamento->garantirProximoRastreamento($projeto, $planoEfetivo);

                if ($projeto->frequency === 'manual') {
                    $ignorados++;
                    $this->line("Projeto {$projeto->id}: frequencia desativada para o plano atual.");
                    continue;
                }

                if ($this->execucaoRastreamento->localizarJobAtivo($projeto)) {
                    $ignorados++;
                    $this->line("Projeto {$projeto->id}: job ja ativo, ignorado.");
                    continue;
                }

                if ($projeto->next_scheduled_crawl_at && $projeto->next_scheduled_crawl_at->greaterThan($agora)) {
                    $ignorados++;
                    $this->line(sprintf(
                        'Projeto %d: proximo rastreamento em %s.',
                        $projeto->id,
                        $projeto->next_scheduled_crawl_at->toDateTimeString()
                    ));
                    continue;
                }

                if ($dryRun) {
                    $agendados++;
                    $this->info("Projeto {$projeto->id}: seria agendado agora.");
                    continue;
                }

                $resultado = $this->execucaoRastreamento->iniciar(
                    $projeto,
                    'Job agendado automaticamente conforme a frequencia configurada.'
                );

                if (!$resultado['success']) {
                    $ignorados++;
                    $this->warn("Projeto {$projeto->id}: {$resultado['message']}");
                    continue;
                }

                $this->frequenciaRastreamento->atualizarProximoRastreamento($projeto, $agora, $planoEfetivo);
                $agendados++;

                $this->info(sprintf(
                    'Projeto %d: job %s criado automaticamente.',
                    $projeto->id,
                    $resultado['job_id']
                ));
            }
        });

        $this->info("Agendamento concluido. Processados: {$processados}. Agendados: {$agendados}. Ignorados: {$ignorados}.");

        return self::SUCCESS;
    }
}
