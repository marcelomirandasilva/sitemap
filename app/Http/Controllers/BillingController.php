<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    /**
     * Lista o histórico de faturas do usuário.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $invoices = [];

        // Verifica se o usuário tem faturas no Stripe
        if ($user->hasStripeId()) {
            try {
                $stripeInvoices = $user->invoices();
                $invoices = $stripeInvoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'date' => $invoice->date()->toIso8601String(),
                        'total' => $invoice->total(),
                        'total_formatted' => $invoice->realTotal(),
                        'status' => $invoice->status,
                        'invoice_pdf' => $invoice->invoice_pdf,
                    ];
                });
            } catch (\Exception $e) {
                \Log::error('Erro ao buscar faturas: ' . $e->getMessage());
            }
        }

        $subscription = $user->subscription('default');

        $activeSubscription = null;
        if ($user->plan && strtolower($user->plan->name) !== 'free') {
            $activeSubscription = [
                'name' => $user->plan->name,
                'status' => $subscription ? $subscription->stripe_status : 'active',
                'cancel_at_period_end' => $subscription ? $subscription->onGracePeriod() : false,
                'ends_at' => $subscription && $subscription->ends_at ? $subscription->ends_at->toIso8601String() : null,
            ];
        }

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
            'activeSubscription' => $activeSubscription,
        ]);
    }
}
