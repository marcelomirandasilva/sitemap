<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarefaSitemap;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarefaSitemapController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['id', 'status', 'pages_processed', 'created_at', 'projeto.url']; // projeto.url needs join if used directly, but usually we sort by model columns
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
            'filters' => $request->only(['status', 'sort_by', 'sort_order'])
        ]);
    }

    public function show(TarefaSitemap $job)
    {
        $job->load('projeto.user');

        $logs = [];
        if ($job->error_message) {
            $logs[] = ['type' => 'error', 'message' => $job->error_message];
        }

        // Recuperar JSONs (cast em array natural do Laravel Model)
        $paginasMapeadas = is_array($job->mapped_urls) ? $job->mapped_urls : json_decode($job->mapped_urls, true);

        return Inertia::render('Admin/Jobs/Show', [
            'job' => $job,
            'logs' => $logs,
            'paginas' => $paginasMapeadas
        ]);
    }

    public function cancel(TarefaSitemap $job)
    {
        if (in_array($job->status, ['running', 'queued'])) {
            $job->update([
                'status' => 'failed',
                'error_message' => 'Cancelado manualmente por um administrador (Back-office Vue).'
            ]);
            return back()->with('success', 'O processo de Crawler foi abortado à força. Demais engrenagens associadas serão soltas em breve.');
        }

        return back()->with('error', 'Esta tarefa já foi concluída, falhou ou nunca iniciou.');
    }
}
