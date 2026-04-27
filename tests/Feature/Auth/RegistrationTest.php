<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeAndVerifyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);
        Notification::fake();
        Http::fake([
            '*' => Http::response(['job_id' => 'job-teste-local'], 201),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'url' => 'https://example.com',
        ]);

        $this->assertAuthenticated();

        $usuario = User::where('email', 'test@example.com')->firstOrFail();
        $projeto = $usuario->projetos()->first();

        Notification::assertSentTo($usuario, WelcomeAndVerifyUser::class);
        $this->assertNotNull($projeto);
        $response->assertRedirect(route('projects.show', $projeto));
    }
}
