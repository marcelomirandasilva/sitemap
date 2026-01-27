<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Http\Controllers\ProjectController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Public/LandingPage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    // Rota de Download via Proxy
    Route::get('/downloads/{jobId}/{filename}', [\App\Http\Controllers\DownloadController::class, 'sitemap'])->name('downloads.sitemap');
});

Route::get('auth/google', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'handleGoogleCallback']);

Route::middleware(['auth'])->group(function () {
    Route::post('/projects/{project}/crawler', [App\Http\Controllers\CrawlerController::class, 'store'])->name('crawler.store');
    Route::get('/projects/{project}/crawler-status', [App\Http\Controllers\CrawlerController::class, 'show'])->name('crawler.show');
});

// Rotas de Desenvolvimento
Route::middleware(['auth'])->prefix('dev')->group(function () {
    Route::get('/api-test', [App\Http\Controllers\DevController::class, 'showApiTest'])->name('dev.api-test');
    Route::post('/api-test/run', [App\Http\Controllers\DevController::class, 'runApiTest'])->name('dev.api-test.run');
});

require __DIR__ . '/auth.php';
