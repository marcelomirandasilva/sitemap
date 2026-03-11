<?php

namespace App\Http\Controllers;

use App\Models\SitemapJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recebe notificações da API Python sobre conclusão ou falha de jobs.
 * Rota: POST /api/internal/webhook/job-completed
 *
 * A autenticação é feita via X-Internal-Token (mesmo segredo compartilhado).
 */
class SitemapWebhookController extends Controller
{
    /**
     * Processa o aviso de conclusão de job enviado pela API Python.
     */
    public function jobCompleted(Request $request)
    {
        // Valida o Token de Sistema (mesmo usado no SitemapGeneratorService)
        $secret = config('services.sitemap_generator.internal_secret');

        if (empty($secret) || $request->header('X-Internal-Token') !== $secret) {
            Log::warning('Webhook rejeitado: token inválido ou ausente.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'job_id' => 'required|string',
            'status' => 'required|string|in:completed,failed,cancelled',
        ]);

        $externalJobId = $request->input('job_id');
        $status = $request->input('status');
        $pagesFound = $request->input('pages_found', 0);
        $errorMessage = $request->input('error_message');

        // Busca o job no banco
        $job = SitemapJob::where('external_job_id', $externalJobId)->first();

        if (!$job) {
            Log::warning("Webhook recebido para job_id desconhecido: {$externalJobId}");
            // Retorna 200 para não causar retry infinito na API Python
            return response()->json(['message' => 'Job não encontrado, ignorado.']);
        }

        // Atualiza o status no banco de dados do Laravel
        $updateData = [
            'status' => $status,
            'pages_found' => $pagesFound,
            'finished_at' => now(),
        ];

        if ($status === 'completed') {
            $updateData['progress'] = 100;
        }

        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        $job->update($updateData);

        Log::info("Webhook processado com sucesso.", [
            'job_id' => $externalJobId,
            'status' => $status,
            'user_id' => $job->user_id ?? 'N/A',
        ]);

        return response()->json(['message' => 'Webhook processado com sucesso.']);
    }
}
