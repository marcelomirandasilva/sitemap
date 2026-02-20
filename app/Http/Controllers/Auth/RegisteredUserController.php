<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SitemapGeneratorService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;

class RegisteredUserController extends Controller
{
    protected SitemapGeneratorService $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }
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

        // Cria uma senha aleatória que o usuário nunca saberá, pois ele vai criar a dele na ativação
        // 1. Cria o Usuário (Sempre no plano Free ID 1)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)),
            'plan_id' => 1,
            'role' => 'user',
        ]);

        // 2. Cria o Primeiro Projeto automaticamente
        $user->load('plan');
        $temRecursosAvancados = $user->plan && $user->plan->has_advanced_features;

        $domain = parse_url($request->url, PHP_URL_HOST) ?? $request->url;
        $project = Project::create([
            'user_id' => $user->id,
            'name' => $domain,
            'url' => $request->url,
            'status' => 'pending',
            'frequency' => 'manual',
            'check_images' => $temRecursosAvancados,
            'check_videos' => $temRecursosAvancados,
        ]);

        // 3. Inicia o crawler automaticamente
        try {
            $externalJobId = $this->sitemapService->startJob($project);

            if ($externalJobId) {
                $project->sitemapJobs()->create([
                    'external_job_id' => $externalJobId,
                    'status' => 'queued',
                    'started_at' => now(),
                ]);
            } else {
                Log::warning("Falha ao iniciar crawler no cadastro para o projeto {$project->id}");
            }
        } catch (\Exception $e) {
            Log::error('Erro ao iniciar crawler no cadastro: ' . $e->getMessage());
        }

        event(new Registered($user));

        // Gerar o token seguro de Reset de Senha nativo do Laravel
        $token = Password::broker()->createToken($user);

        // Enviar notificação unificada omitindo a senha mas mandando o token
        try {
            $user->notify(new \App\Notifications\WelcomeAndVerifyUser($token));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de boas-vindas e verificação: ' . $e->getMessage());
        }

        Auth::login($user);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Conta criada com sucesso! Verifique seu email para ativá-la.');
    }
}
