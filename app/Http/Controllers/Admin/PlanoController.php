<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PlanoController extends Controller
{
    public function index()
    {
        $planos = Plano::orderBy('price_monthly_brl', 'asc')->get();
        return Inertia::render('Admin/Plans/Index', ['planos' => $planos]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plano' => new Plano([
                'has_advanced_features' => false,
                'max_projects' => 1,
                'max_pages' => 500,
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
            'update_frequency' => 'nullable|string|max:255',
            'max_projects' => 'required|integer|min:-1',
            'max_pages' => 'required|integer|min:-1',
            'has_advanced_features' => 'boolean',
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
