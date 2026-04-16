<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroChangelog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChangelogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Changelog/Index', [
            'registros' => RegistroChangelog::query()
                ->orderByDesc('data_lancamento')
                ->orderByDesc('ordem_exibicao')
                ->orderByDesc('id')
                ->get()
                ->map(fn (RegistroChangelog $registro) => [
                    'id' => $registro->id,
                    'versao' => $registro->versao,
                    'data_lancamento' => $registro->data_lancamento instanceof \DateTimeInterface
                        ? $registro->data_lancamento->format('Y-m-d')
                        : $registro->data_lancamento,
                    'ordem_exibicao' => $registro->ordem_exibicao,
                    'publicado' => (bool) $registro->publicado,
                    'categoria_pt' => $registro->categoria_pt,
                    'categoria_en' => $registro->categoria_en,
                    'titulo_pt' => $registro->titulo_pt,
                    'titulo_en' => $registro->titulo_en,
                ])
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Changelog/Edit', [
            'registro' => $this->registroFormulario(new RegistroChangelog([
                'publicado' => true,
                'data_lancamento' => now()->toDateString(),
                'ordem_exibicao' => 0,
            ])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        RegistroChangelog::create($this->dadosValidados($request));

        return redirect()
            ->route('admin.changelog.index')
            ->with('success', 'Registro de changelog criado com sucesso.');
    }

    public function edit(RegistroChangelog $changelog): Response
    {
        return Inertia::render('Admin/Changelog/Edit', [
            'registro' => $this->registroFormulario($changelog),
        ]);
    }

    public function update(Request $request, RegistroChangelog $changelog): RedirectResponse
    {
        $changelog->update($this->dadosValidados($request));

        return redirect()
            ->route('admin.changelog.index')
            ->with('success', 'Registro de changelog atualizado com sucesso.');
    }

    public function destroy(RegistroChangelog $changelog): RedirectResponse
    {
        $changelog->delete();

        return redirect()
            ->route('admin.changelog.index')
            ->with('success', 'Registro de changelog excluido.');
    }

    protected function dadosValidados(Request $request): array
    {
        $dados = $request->validate([
            'versao' => 'required|string|max:50',
            'data_lancamento' => 'required|date',
            'ordem_exibicao' => 'required|integer|min:0|max:9999',
            'publicado' => 'boolean',
            'categoria_pt' => 'required|string|max:120',
            'categoria_en' => 'required|string|max:120',
            'titulo_pt' => 'required|string|max:255',
            'titulo_en' => 'required|string|max:255',
            'resumo_pt' => 'required|string',
            'resumo_en' => 'required|string',
            'itens_pt_texto' => 'nullable|string',
            'itens_en_texto' => 'nullable|string',
        ]);

        return [
            'versao' => trim((string) $dados['versao']),
            'data_lancamento' => $dados['data_lancamento'],
            'ordem_exibicao' => (int) $dados['ordem_exibicao'],
            'publicado' => (bool) ($dados['publicado'] ?? false),
            'categoria_pt' => trim((string) $dados['categoria_pt']),
            'categoria_en' => trim((string) $dados['categoria_en']),
            'titulo_pt' => trim((string) $dados['titulo_pt']),
            'titulo_en' => trim((string) $dados['titulo_en']),
            'resumo_pt' => trim((string) $dados['resumo_pt']),
            'resumo_en' => trim((string) $dados['resumo_en']),
            'itens_pt' => $this->normalizarLinhas($dados['itens_pt_texto'] ?? ''),
            'itens_en' => $this->normalizarLinhas($dados['itens_en_texto'] ?? ''),
        ];
    }

    protected function normalizarLinhas(string $texto): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $texto) ?: [])
            ->map(fn ($linha) => trim((string) $linha))
            ->filter()
            ->values()
            ->all();
    }

    protected function registroFormulario(RegistroChangelog $registro): array
    {
        $dataLancamento = $registro->data_lancamento;

        if ($dataLancamento instanceof \DateTimeInterface) {
            $dataLancamento = $dataLancamento->format('Y-m-d');
        }

        return [
            'id' => $registro->id,
            'versao' => $registro->versao,
            'data_lancamento' => $dataLancamento,
            'ordem_exibicao' => $registro->ordem_exibicao ?? 0,
            'publicado' => (bool) $registro->publicado,
            'categoria_pt' => $registro->categoria_pt ?? '',
            'categoria_en' => $registro->categoria_en ?? '',
            'titulo_pt' => $registro->titulo_pt ?? '',
            'titulo_en' => $registro->titulo_en ?? '',
            'resumo_pt' => $registro->resumo_pt ?? '',
            'resumo_en' => $registro->resumo_en ?? '',
            'itens_pt_texto' => implode(PHP_EOL, $registro->itens_pt ?? []),
            'itens_en_texto' => implode(PHP_EOL, $registro->itens_en ?? []),
            'created_at' => optional($registro->created_at)?->toISOString(),
            'updated_at' => optional($registro->updated_at)?->toISOString(),
        ];
    }
}
