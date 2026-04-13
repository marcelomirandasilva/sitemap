<?php

namespace App\Http\Controllers;

use App\Services\SitemapGeneratorService;
use Inertia\Inertia;

class DevController extends Controller
{
    public function showApiTest()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return Inertia::render('Dev/ApiTest');
    }

    public function runApiTest(SitemapGeneratorService $service)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->json($service->testConnection());
    }
}
