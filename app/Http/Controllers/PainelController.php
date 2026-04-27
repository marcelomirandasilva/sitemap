<?php

namespace App\Http\Controllers;

use App\Services\SincronizacaoAssinaturaStripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PainelController extends Controller
{
    public function __construct(private SincronizacaoAssinaturaStripeService $sincronizacaoAssinaturaStripe)
    {
    }

    public function index(Request $request)
    {
        $usuario = Auth::user();

        if ($request->query('checkout') === 'success') {
            try {
                $sincronizado = $this->sincronizacaoAssinaturaStripe->sincronizarAssinaturaAtivaMaisRecente($usuario);

                if ($sincronizado) {
                    return redirect()
                        ->route('dashboard')
                        ->with('success', 'Assinatura confirmada e plano atualizado com sucesso.');
                }
            } catch (\Throwable $erro) {
                report($erro);
            }
        }

        $projetos = Auth::user()->projetos()
            ->with([
                'tarefasSitemap' => function ($query) {
                    $query->latest()->limit(1);
                },
            ])
            ->latest()
            ->get()
            ->map(function ($projeto) {
                $projeto->ultimo_job = $projeto->tarefasSitemap->first();

                return $projeto;
            });

        $planoEfetivo = $usuario->planoEfetivo();

        return Inertia::render('App/Dashboard/Index', [
            'projetos' => $projetos,
            'userPlan' => $planoEfetivo,
        ]);
    }
}
