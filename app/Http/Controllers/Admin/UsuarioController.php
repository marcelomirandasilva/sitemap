<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plano;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validação de colunas permitidas para evitar SQL Injection via order
        $allowedSorts = ['name', 'email', 'role', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query = User::with(['plano', 'subscriptions'])->orderBy($sortBy, $sortOrder);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        // Mapear o status da assinatura antes de enviar ao Vue para simular a tabela do Filament
        $users->getCollection()->transform(function ($user) {
            $user->stripe_status = $user->subscriptions->first()?->stripe_status ?? 'nenhuma';
            return $user;
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'sort_by', 'sort_order'])
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('plano'),
            'planos' => Plano::all()
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'plan_id' => 'nullable|exists:plans,id',
            'role' => 'required|in:admin,user',
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function impersonate(User $user)
    {
        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
