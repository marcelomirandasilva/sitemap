# Skill: Arquitetura do Projeto (Laravel + Vue + Inertia)

Esta skill define as regras obrigatórias para geração de código neste projeto. Sempre que for solicitado criar ou refatorar código, aplique estas diretrizes.

## 1. Fonte da Verdade (Banco de Dados)
- **Regra de Ouro:** Antes de escrever qualquer código que envolva Models ou Migrations, você DEVE ler o arquivo `database/schema/mysql-schema.sql` usando a ferramenta de leitura de arquivos.
- Nunca alucine nomes de colunas. Use apenas o que está no schema.
- Se uma coluna não existe, sugira criar uma migration para ela.

## 2. Backend (Laravel 11/12)
- **Controllers:** Devem ser magros. Use `FormRequests` para validação.
- **Retorno:** Use sempre `Inertia::render('NomeComponente', [props])`.
- **API:** Não crie APIs REST (json) a menos que explicitamente pedido. O foco é Monólito Modular com Inertia.
- **Automação:** Lembre-se que o comando `php artisan migrate` já atualiza o dump do banco automaticamente (via AppServiceProvider). Não peça para rodar `schema:dump` manualmente.

## 3. Frontend (Vue 3 + Composition API)
- **Sintaxe:** Use estritamente `<script setup lang="ts">` (ou js, conforme o projeto).
- **Proibido:** Não use Options API (`export default { ... }`).
- **Formulários:** Utilize o helper `useForm` do Inertia (`@inertiajs/vue3`) para gerenciar estado, envio e erros de validação.
- **UI/CSS:** Use classes utilitárias do **Tailwind CSS**. Evite CSS customizado em tags `<style>`.

## 4. Fluxo de Trabalho
- Ao criar uma nova funcionalidade, siga a ordem:
  1. Verifique o Schema (`mysql-schema.sql`).
  2. Crie/Ajuste a Migration (se necessário).
  3. Crie o Model e Controller.
  4. Crie a View (Vue/Inertia).
  5. Registre a Rota (`web.php`).