# 📚 Documentação: Implementação de Assinaturas (Stripe)

Este documento detalha os arquivos e lógicas criados para implementar o sistema de assinaturas e pagamentos com Stripe, utilizando o Laravel Cashier.

## 1. ⚙️ Sincronização de Planos (Backend <-> Stripe)

**Arquivo:** `app/Console/Commands/SyncStripePlans.php`

*   **Função:** Cria/Sincroniza produtos e preços no Stripe baseados na tabela local `plans`.
*   **Uso:** `php artisan stripe:sync-plans`.
*   **Lógica:** Garante que cada plano no banco tenha um `stripe_price_id` válido para uso no checkout.

---

## 2. 🔄 Webhooks e Ciclo de Vida (Vital)

O sistema depende dos Webhooks para manter a tabela `subscriptions` e o campo `plan_id` do usuário atualizados.

**Arquivo:** `app/Listeners/StripeEventListener.php`

*   **Função:** Reage a eventos críticos do Stripe.
*   **Eventos Monitorados:**
    *   `customer.subscription.created` / `updated`:
        *   Verifica se o status é `active`.
        *   Sincroniza a tabela `subscriptions` (fonte da verdade).
        *   Atualiza o cache `users.plan_id` para performance.
    *   `customer.subscription.deleted` (Cancelamento):
        *   Define `subscriptions.status` como `cancelled` (via Cashier).
        *   Remove o plano Pro do usuário (`users.plan_id` = null ou Free).
    *   `invoice.payment_failed`:
        *   Marca a assinatura como `past_due` e pode notificar o usuário.

**Arquivo:** `bootstrap/app.php`

*   **Segurança:** Exceção CSRF configurada para a rota `stripe/*`.

---

## 3. 💳 Checkout e Troca de Plano (Swap)

**Arquivo:** `app/Http/Controllers/SubscriptionController.php`

*   **Novo Assinante:** Inicia sessão de Checkout (`$user->newSubscription(...)`).
*   **Assinante Existente (Upgrade/Downgrade):**
    *   Usa o método `swap()` do Cashier.
    *   Calcula o prorata (cobrança proporcional) automaticamente.
    *   Não requer re-inserção de cartão (usa o `pm_xxxx` salvo).

---

## 4. 🛠️ Fluxo de Funcionamento Atualizado

1.  **SysAdmin** executa `php artisan stripe:sync-plans`.
2.  **Usuário** seleciona plano na UI.
3.  **Checkout:**
    *   Stripe processa o cartão e valida fundos.
4.  **Webhook (Assíncrono):**
    *   Stripe envia `customer.subscription.created` para `/stripe/webhook`.
5.  **Processamento Local:**
    *   Cashier cria o registro na tabela `subscriptions`.
    *   `StripeEventListener` atualiza `users.plan_id`.
6.  **Acesso:** O Middleware/UI verifica se `users.plan_id` é válido.
