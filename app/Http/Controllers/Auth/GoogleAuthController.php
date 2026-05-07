<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(), // Assumimos verificado se veio do Google
                // Senha aleatória segura pois o login é via social
                'password' => Hash::make(Str::random(24)),
            ]);

            Auth::login($user);

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            Log::warning('Falha no callback Google OAuth.', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Nao foi possivel autenticar com Google no momento.');
        }
    }
}
