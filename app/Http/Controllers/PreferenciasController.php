<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use App\Notifications\SenhaAlterada;
use Inertia\Inertia;

class PreferenciasController extends Controller
{
    public function updateUi(Request $request)
    {
        $validated = $request->validate([
            'ui_preferences' => ['required', 'array'],
            'ui_preferences.theme' => ['nullable', 'in:light,dark'],
            'ui_preferences.locale' => ['nullable', Rule::in(['pt', 'en'])],
        ]);

        $preferencias = array_merge(
            $request->user()->ui_preferences ?? [],
            array_filter($validated['ui_preferences'], fn ($valor) => $valor !== null)
        );

        $request->user()->update([
            'ui_preferences' => $preferencias,
        ]);

        if (!empty($preferencias['locale'])) {
            $request->session()->put('app_locale', $preferencias['locale']);
            app()->setLocale($preferencias['locale']);
        }

        return back()->with('success', 'Preferencias de interface atualizadas.');
    }

    public function updateLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['pt', 'en'])],
        ]);

        $preferencias = array_merge(
            $request->user()->ui_preferences ?? [],
            ['locale' => $validated['locale']]
        );

        $request->user()->update([
            'ui_preferences' => $preferencias,
        ]);

        $request->session()->put('app_locale', $validated['locale']);
        app()->setLocale($validated['locale']);

        return back();
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'notification_preferences' => ['required', 'array'],
            'notification_preferences.weekly_summary' => ['boolean'],
            'notification_preferences.broken_links' => ['boolean'],
            'notification_preferences.crawler_updates' => ['boolean'],
            'notification_preferences.search_engine_updates' => ['boolean'],
            'notification_preferences.support_updates' => ['boolean'],
            'notification_preferences.email_crawler_updates' => ['boolean'],
            'notification_preferences.email_search_engine_updates' => ['boolean'],
            'notification_preferences.email_support_updates' => ['boolean'],
            'notification_preferences.email_billing_updates' => ['boolean'],
        ]);

        $request->user()->update([
            'notification_preferences' => array_merge($request->user()->notification_preferences ?? [], $validated['notification_preferences']),
        ]);

        return back()->with('success', 'Preferencias de notificacao atualizadas.');
    }

    public function updateBilling(Request $request)
    {
        $validated = $request->validate([
            'vat_number' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $user->update([
            'vat_number' => $validated['vat_number'],
            'billing_address' => $validated['billing_address'],
        ]);

        if ($user->hasStripeId()) {
            try {
                $user->updateStripeCustomer([
                    'address' => [
                        'line1' => $validated['billing_address'],
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::error('Erro ao sincronizar Stripe: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Dados de faturamento atualizados.');
    }

    public function edit(Request $request)
    {
        return Inertia::render('Account/Preferences', [
            'user' => $request->user(),
            'preferences' => array_merge(
                ['theme' => 'light'],
                $request->user()->ui_preferences ?? []
            ),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);

        $request->user()->notify(new SenhaAlterada($request->ip()));

        return back()->with('success', 'Senha alterada com sucesso.');
    }

    public function deactivate(Request $request)
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Sua conta foi desativada com sucesso.');
    }
}
