<?php

namespace Tests\Feature\Preferences;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IdiomaAplicacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_idioma_salvo_do_usuario_define_o_locale_do_app(): void
    {
        $usuario = User::factory()->create([
            'ui_preferences' => [
                'theme' => 'light',
                'locale' => 'en',
            ],
        ]);

        $response = $this
            ->actingAs($usuario)
            ->get(route('preferences.edit'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Account/Preferences')
            ->where('locale', 'en')
            ->where('preferences.locale', 'en')
        );
    }

    public function test_usuario_pode_atualizar_o_locale_pelas_preferencias(): void
    {
        $usuario = User::factory()->create([
            'ui_preferences' => [
                'theme' => 'light',
                'locale' => 'pt',
            ],
        ]);

        $response = $this
            ->actingAs($usuario)
            ->from(route('preferences.edit'))
            ->put(route('preferences.locale.update'), [
                'locale' => 'en',
            ]);

        $response->assertRedirect(route('preferences.edit'));

        $this->assertSame('en', data_get($usuario->fresh()->ui_preferences, 'locale'));

        $this
            ->actingAs($usuario->fresh())
            ->get(route('preferences.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('preferences.locale', 'en')
            );
    }
}
