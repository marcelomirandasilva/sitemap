<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccountActivationController extends Controller
{
    /**
     * Display the account activation view where the user sets their password.
     */
    public function show(Request $request, $token): Response
    {
        return Inertia::render('Auth/ActivateAccount', [
            'email' => $request->email,
            'token' => $token,
        ]);
    }

    /**
     * Handle an incoming account activation request (setting password).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                // Update password
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Mark email as verified if it isn't already
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                    event(new Verified($user));
                }
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            // Find the user to login automatically
            $user = User::where('email', $request->email)->first();
            if ($user && !Auth::check()) { // Avoid overriding an already authenticated different user, optionally
                Auth::login($user);
            }

            return redirect()->route('dashboard')->with('success', 'Conta ativada com sucesso!');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
