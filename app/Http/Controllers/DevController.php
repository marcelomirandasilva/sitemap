<?php

namespace App\Http\Controllers;

use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DevController extends Controller
{
    public function showApiTest()
    {
        return Inertia::render('Dev/ApiTest');
    }

    public function runApiTest(SitemapGeneratorService $service)
    {
        // Apenas admins deveriam acessar isso em prod, mas é ambiente dev
        return response()->json($service->testConnection());
    }
}
