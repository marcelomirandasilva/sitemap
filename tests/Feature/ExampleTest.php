<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_a_raiz_publica_redireciona_para_a_landing_localizada(): void
    {
        $response = $this->withHeader('Accept-Language', 'pt-BR,pt;q=0.9')->get('/');

        $response->assertRedirect(route('public.landing', ['locale' => 'pt'], false));
    }
}
