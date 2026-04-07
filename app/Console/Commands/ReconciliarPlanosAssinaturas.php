<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ReconciliarPlanosAssinaturas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assinaturas:reconciliar-planos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebaixa ou corrige o plano dos usuarios conforme o estado real das assinaturas.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $usuarios = User::query()
            ->where(function ($query) {
                $query->whereNotNull('plan_id')
                    ->orWhereHas('subscriptions');
            })
            ->get();

        $quantidadeAtualizados = 0;

        foreach ($usuarios as $usuario) {
            $planoAnteriorId = $usuario->plan_id;
            $planoAtualizado = $usuario->sincronizarPlanoComAssinatura();

            if ($planoAnteriorId !== $planoAtualizado?->id) {
                $quantidadeAtualizados++;

                $this->line(sprintf(
                    'Usuario %d sincronizado: plano_id %s -> %s',
                    $usuario->id,
                    $planoAnteriorId ?? 'null',
                    $planoAtualizado?->id ?? 'null'
                ));
            }
        }

        $this->info("Reconciliacao concluida. Usuarios atualizados: {$quantidadeAtualizados}.");

        return self::SUCCESS;
    }
}
