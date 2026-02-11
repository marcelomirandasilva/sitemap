---
description: Configurar Webhooks do Stripe Localmente
---

Para que o sistema reconheça o pagamento efetuado no Stripe (mesmo em ambiente de teste), é **obrigatório** que o "Webhook" esteja rodando. Sem isso, o Stripe cobra o cartão, mas não "avisa" o seu site que o pagamento deu certo.

Siga os passos abaixo:

1.  **Baixe a CLI do Stripe** (se ainda não tiver):
    *   Acesse: [https://github.com/stripe/stripe-cli/releases/latest](https://github.com/stripe/stripe-cli/releases/latest)
    *   Baixe a versão para Windows (`stripe_x.x.x_windows_x86_64.zip`).
    *   Descompacte o `stripe.exe` e coloque na pasta do seu projeto (ou adicione ao PATH).

2.  **Faça Login no Stripe via Terminal**:
    Abra seu terminal na pasta do projeto e rode:
    ```bash
    ./stripe login
    ```
    (Siga as instruções no navegador para autorizar).

3.  **Inicie o Ouvinte de Webhooks**:
    Ainda no terminal, rode o comando para redirecionar os eventos do Stripe para o seu site local:
    ```bash
    ./stripe listen --forward-to localhost:8000/stripe/webhook
    ```
    > **Atenção:** Se você usa **Laragon**, XAMPP ou tem um domínio virtual (ex: `http://sitemap.test`), use o endereço correto:
    > ```bash
    > ./stripe listen --forward-to http://sitemap.test/stripe/webhook
    > ```

4.  **Configure o Segredo do Webhook**:
    O comando acima vai mostrar uma chave de assinatura secreta, parecida com `whsec_...`.
    Copie essa chave e coloque no seu arquivo `.env`:
    ```env
    STRIPE_WEBHOOK_SECRET=whsec_...
    ```

5.  **Teste Novamente**:
    *   Com o comando `./stripe listen ...` **rodando** (não feche a janela do terminal), tente fazer um novo pagamento.
    *   Você verá no terminal os eventos chegando (`charge.succeeded`, `customer.subscription.created`, etc.).
    *   Atualize a página do sistema e o plano deve mudar!

> **Nota:** Em produção (servidor real), você apenas cadastra a URL `seusite.com/stripe/webhook` no painel do Stripe, não precisa desse comando.
