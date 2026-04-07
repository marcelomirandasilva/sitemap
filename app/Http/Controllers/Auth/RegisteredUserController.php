<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use App\Services\SitemapGeneratorService;
use App\Support\SeoPublico;
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
use App\Models\User;
use App\Models\Projeto;

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
        $locale = SeoPublico::normalizarLocale(app()->getLocale());

        return Inertia::render('Public/LandingPage', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => \Illuminate\Foundation\Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'defaultTab' => 'signup',
            'plans' => Plano::all(),
            'locale' => $locale,
            'seo' => SeoPublico::dadosLanding($locale),
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
        // Atribui o Plano "Free" como Padrão do Sistema
        $plano = Plano::where('slug', 'free')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)),
            'plan_id' => $plano ? $plano->id : null,
            'role' => 'user',
            'ui_preferences' => [
                'theme' => 'light',
                'locale' => SeoPublico::normalizarLocale(app()->getLocale()),
            ],
        ]);

        // 2. Cria o Primeiro Projeto automaticamente
        $user->load('plano');
        $temRecursosAvancados = $user->plano && $user->plano->has_advanced_features;

        $domain = parse_url($request->url, PHP_URL_HOST) ?? $request->url;
        $projeto = Projeto::create([
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
            $externalJobId = $this->sitemapService->startJob($projeto);

            if ($externalJobId) {
                $projeto->tarefasSitemap()->create([
                    'external_job_id' => $externalJobId,
                    'status' => 'queued',
                    'started_at' => now(),
                ]);
            } else {
                Log::warning("Falha ao iniciar crawler no cadastro para o projeto {$projeto->id}");
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

        return redirect()->route('projects.show', $projeto->id)
            ->with('success', 'Conta criada com sucesso! Verifique seu email para ativá-la.');
    }
}
