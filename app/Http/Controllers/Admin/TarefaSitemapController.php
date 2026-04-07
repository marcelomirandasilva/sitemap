<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarefaSitemap;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarefaSitemapController extends Controller
{
    public function __construct(protected SitemapGeneratorService $sitemapService)
    {
    }

    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['id', 'status', 'progress', 'pages_count', 'created_at', 'started_at', 'completed_at'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query = TarefaSitemap::with(['projeto.user'])->orderBy($sortBy, $sortOrder);

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => $jobs,
            'filters' => $request->only(['status', 'sort_by', 'sort_order']),
        ]);
    }

    public function show(TarefaSitemap $job)
    {
        $job->load('projeto.user');

        $logs = [];

        if ($job->message && in_array($job->status, ['failed', 'cancelled'])) {
            $logs[] = ['type' => 'error', 'message' => $job->message];
        }

        return Inertia::render('Admin/Jobs/Show', [
            'job' => $job,
            'logs' => $logs,
        ]);
    }

    public function cancel(TarefaSitemap $job)
    {
        $job->loadMissing('projeto.user');

        if (!in_array($job->status, ['running', 'queued'])) {
            return back()->with('error', 'Esta tarefa ja foi concluida, cancelada ou falhou.');
        }

        if (!$job->projeto || !$job->projeto->user_id) {
            return back()->with('error', 'Nao foi possivel localizar o projeto associado a esta tarefa.');
        }

        $resultado = $this->sitemapService->cancelJob(
            $job->external_job_id,
            $job->projeto->user_id,
            $job->project_id
        );

        if (!$resultado['success']) {
            $statusData = $this->sitemapService->checkStatus($job->external_job_id, $job->projeto->user_id);

            if ($statusData) {
                $job->update([
                    'status' => $statusData['status'] ?? $job->status,
                    'progress' => $statusData['progress'] ?? $job->progress,
                    'message' => $statusData['message'] ?? $statusData['error_message'] ?? $job->message,
                    'completed_at' => $statusData['completed_at'] ?? $job->completed_at,
                ]);
                $job->refresh();

                if (in_array($job->status, ['completed', 'failed', 'cancelled']) && $job->projeto->current_crawler_job_id === $job->external_job_id) {
                    $job->projeto->update(['current_crawler_job_id' => null]);
                }
            }

            return back()->with('error', $resultado['message'] ?? 'Nao foi possivel cancelar o job remoto.');
        }

        $job->update([
            'status' => 'cancelled',
            'message' => $resultado['message'] ?? 'Job cancelado manualmente por um administrador.',
            'completed_at' => $resultado['cancelled_at'] ?? now(),
        ]);

        if ($job->projeto->current_crawler_job_id === $job->external_job_id) {
            $job->projeto->update(['current_crawler_job_id' => null]);
        }

        return back()->with('success', 'O processo de crawler foi cancelado com sucesso.');
    }
}
