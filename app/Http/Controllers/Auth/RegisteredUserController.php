<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Public/LandingPage', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => \Illuminate\Foundation\Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'defaultTab' => 'signup',
            'plans' => \App\Models\Plan::all(), // Planos injetados
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            // 'password' => ['required', 'confirmed', Rules\Password::defaults()], // Removido
            'url' => 'required|url',
        ]);

        // Gerar senha aleatória
        $password = \Illuminate\Support\Str::password(12);

        // 1. Cria o Usuário (Sempre no plano Free ID 1)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'plan_id' => 1,
            'role' => 'user',
        ]);

        // 2. Cria o Primeiro Projeto automaticamente
        $domain = parse_url($request->url, PHP_URL_HOST) ?? $request->url;
        Project::create([
            'user_id' => $user->id,
            'name' => $domain,
            'url' => $request->url,
        ]);

        event(new Registered($user));

        // Enviar notificação com a senha
        try {
            $user->notify(new \App\Notifications\WelcomeNewUser($password));
        } catch (\Exception $e) {
            // Logar erro de envio de email, mas não falhar o cadastro
            \Illuminate\Support\Facades\Log::error('Erro ao enviar email de boas-vindas: ' . $e->getMessage());
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))->with('success', 'Conta criada com sucesso! Sua senha foi enviada para o seu e-mail.');
    }
}
