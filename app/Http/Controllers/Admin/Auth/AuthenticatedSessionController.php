<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Exibe a tela de login administrativa.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Processa a autenticação administrativa.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Validação rigorosa de Role para o login de Gestão
        if (Auth::user()->role !== 'admin') {
            Auth::logout();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Acesso negado. Esta conta não possui privilégios administrativos.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Logout administrativo.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
