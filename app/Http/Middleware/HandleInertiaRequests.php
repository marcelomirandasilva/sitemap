<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'appName' => config('app.name'),
            'userProjects' => $request->user() ?
                $request->user()->projetos()
                    ->select('id', 'name', 'url', 'last_crawled_at', 'status', 'max_pages')
                    ->withCount('paginas')
                    ->orderBy('updated_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($project) use ($request) {
                        return [
                            'id' => $project->id,
                            'name' => $project->name,
                            'url' => str_replace(['http://', 'https://'], '', rtrim($project->url, '/')),
                            'last_crawled_at' => $project->last_crawled_at ? $project->last_crawled_at->toISOString() : null,
                            'pages_count' => $project->paginas_count,
                            'status' => $project->status,
                            'plan_name' => $request->user()->plano ? $request->user()->plano->name : 'Free'
                        ];
                    }) : [],
            'userProjectsCount' => $request->user() ? $request->user()->projetos()->count() : 0,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ];
    }
}
