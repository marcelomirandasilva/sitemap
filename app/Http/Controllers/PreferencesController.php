<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PreferencesController extends Controller
{
    /**
     * Atualiza preferências de UI (Tema, Layout, etc)
     */
    public function updateUi(Request $request)
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            // Validação aninhada para segurança
            'ui_preferences' => ['required', 'array'],
            'ui_preferences.theme' => ['required', 'in:light,dark'],
        ]);

        $request->user()->update([
            'ui_preferences' => array_merge($request->user()->ui_preferences ?? [], $validated['ui_preferences']),
            'timezone' => $validated['timezone'],
        ]);

        return back()->with('success', 'Preferências de interface atualizadas.');
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'notification_preferences' => ['required', 'array'],
            'notification_preferences.weekly_summary' => ['boolean'],
            'notification_preferences.broken_links' => ['boolean'],
        ]);

        $request->user()->update([
            'notification_preferences' => array_merge($request->user()->notification_preferences ?? [], $validated['notification_preferences']),
        ]);

        return back()->with('success', 'Preferências de notificação atualizadas.');
    }

    /**
     * Atualiza dados de cobrança e Sincroniza com Stripe
     */
    public function updateBilling(Request $request)
    {
        $validated = $request->validate([
            'vat_number' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // 1. Atualiza banco local
        $user->update([
            'vat_number' => $validated['vat_number'],
            'billing_address' => $validated['billing_address'],
        ]);

        // 2. Sincroniza endereço com Stripe (Se o usuário já for cliente lá)
        if ($user->hasStripeId()) {
            try {
                // O Stripe aceita o endereço como um objeto. 
                // Como você tem um campo de texto livre, vamos colocar tudo na 'line1' 
                // ou você precisaria quebrar o endereço no frontend.
                $user->updateStripeCustomer([
                    'address' => [
                        'line1' => $validated['billing_address'],
                    ],
                    // Nota: Atualizar VAT ID (Tax ID) via API é mais complexo.
                    // Geralmente recomenda-se usar o Portal do Cliente para Tax IDs.
                    // Mas o endereço é seguro atualizar por aqui.
                ]);
            } catch (\Exception $e) {
                // Logar erro silenciosamente para não travar o usuário, 
                // mas saber que falhou no Stripe
                \Log::error('Erro ao sincronizar Stripe: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Dados de faturamento atualizados.');
    }

    public function edit(Request $request)
    {
        $timezones = collect(timezone_identifiers_list())->groupBy(function ($timezone) {
            return explode('/', $timezone)[0];
        });

        return Inertia::render('Account/Preferences', [ // <--- Verifique se o caminho do arquivo Vue é este mesmo
            'timezones' => $timezones,
            'user' => $request->user(),
            // Garantir valores default caso seja null no banco
            'preferences' => array_merge(
                ['theme' => 'light'],
                $request->user()->ui_preferences ?? []
            ),
        ]);
    }
}