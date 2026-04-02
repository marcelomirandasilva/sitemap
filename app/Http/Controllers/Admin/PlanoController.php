<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PlanoController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'price_monthly_brl');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSorts = ['name', 'max_projects', 'max_pages', 'price_monthly_brl'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'price_monthly_brl';
        }

        $planos = Plano::orderBy($sortBy, $sortOrder)->get();
        return Inertia::render('Admin/Plans/Index', [
            'planos' => $planos,
            'filters' => $request->only(['sort_by', 'sort_order'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plano' => new Plano([
                'has_advanced_features' => false,
                'max_projects' => 1,
                'max_pages' => 500,
                'permite_noticias' => false,
                'permite_mobile' => false,
                'permite_compactacao' => false,
                'permite_cache_crawler' => false,
                'permite_padroes_exclusao' => false,
                'permite_politicas_crawl' => false,
            ])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        Plano::create($validated);
        return redirect()->route('admin.plans.index')->with('success', 'Plano criado com sucesso no sistema (Não esqueça de configurar no Stripe).');
    }

    public function edit(Plano $plan) // Argument is matched with Route param 'plan' since resource is 'plans'
    {
        return Inertia::render('Admin/Plans/Edit', ['plano' => $plan]);
    }

    public function update(Request $request, Plano $plan)
    {
        $validated = $this->validatePlan($request, $plan->id);
        $plan->update($validated);
        return redirect()->route('admin.plans.index')->with('success', 'Configurações de limites e preço do plano salvas.');
    }

    public function destroy(Plano $plan)
    {
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'O Plano foi permanentemente deletado.');
    }

    private function validatePlan(Request $request, $id = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug' . ($id ? ",{$id}" : ''),
            'ideal_for' => 'nullable|string|max:255',
            'update_frequency' => 'required|string|in:diario,semanal,quinzenal,mensal,anual,manual',
            'max_projects' => 'required|integer|min:-1',
            'max_pages' => 'required|integer|min:-1',
            'has_advanced_features' => 'boolean',
            'permite_imagens' => 'boolean',
            'permite_videos' => 'boolean',
            'permite_noticias' => 'boolean',
            'permite_mobile' => 'boolean',
            'permite_compactacao' => 'boolean',
            'permite_cache_crawler' => 'boolean',
            'permite_padroes_exclusao' => 'boolean',
            'permite_politicas_crawl' => 'boolean',
            'stripe_monthly_price_id' => 'nullable|string|max:255',
            'stripe_yearly_price_id' => 'nullable|string|max:255',
            'price_monthly_brl' => 'nullable|numeric|min:0',
            'price_yearly_brl' => 'nullable|numeric|min:0',
            'price_monthly_usd' => 'nullable|numeric|min:0',
            'price_yearly_usd' => 'nullable|numeric|min:0',
        ]);

        $data['slug'] = Str::slug($data['slug']);

        return $data;
    }
}
