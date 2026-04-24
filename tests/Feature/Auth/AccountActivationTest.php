<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_activation_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->get('/activate/'.$token.'?email='.urlencode($user->email));

        $response->assertStatus(200);
    }

    public function test_account_can_be_activated_with_valid_token(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->post('/activate', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'SenhaNova#123',
            'password_confirmation' => 'SenhaNova#123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertTrue(Hash::check('SenhaNova#123', $user->fresh()->password));
    }

    public function test_account_activation_screen_can_be_rendered_for_authenticated_user(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Password::broker()->createToken($user);

        $response = $this
            ->actingAs($user)
            ->get('/activate/'.$token.'?email='.urlencode($user->email));

        $response->assertStatus(200);
    }
}
