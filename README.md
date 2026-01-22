# SiteMapGen - Plataforma SaaS Enterprise

Interface oficial e painel de controle para o sistema **SiteMapGen**. Este projeto é uma plataforma SaaS completa, construída com tecnologias de ponta para oferecer uma experiência de usuário robusta, multilíngue e escalável.

## 🚀 Visão Geral do Sistema

O **SiteMapGen SaaS** é mais do que um gerador de sitemaps; é uma suíte completa de ferramentas SEO. A arquitetura desacoplada (Frontend Laravel/Vue + Backend Python API) permite performance extrema em crawling distribuído, enquanto o frontend gerencia toda a complexidade de assinaturas, usuários e orquestração de jobs.

### 🏗️ Stack Tecnológico (Atualizado)

O núcleo do frontend foi modernizado para utilizar as versões mais recentes das ferramentas do ecossistema Laravel e Javascript:

-   **Backend Framework**: **Laravel 12** (PHP 8.4+) - *A base mais robusta e segura.*
-   **Frontend Framework**: **Vue.js 3** (Composition API) - *Reatividade e performance.*
-   **Roteamento & SPA**: **Inertia.js 2.0** - *Experiência de Single Page Application sem complexidade de API.*
-   **Estilização**: **Tailwind CSS 4.0** - *Design moderno com o novo engine Rust.*
-   **Internacionalização**: **Laravel Vue i18n** - *Suporte nativo a múltiplos idiomas (PT-BR / EN-US).*
-   **Autenticação Social**: **Laravel Socialite** - *Login com Google, GitHub, etc.*

## ✨ Funcionalidades e Módulos

O sistema conta com uma estrutura de menus e módulos completa para operação SaaS:

### 🌍 Internacionalização (I18n)
-   **Suporte Multi-idioma**: Alternância instantânea entre Português e Inglês com persistência de preferência do usuário.
-   **Flags Dinâmicas**: Interface visual para seleção de idioma no topo da aplicação.

### 💳 Módulo Financeiro Completo
Acesso direto via menu de usuário para autogestão financeira:
-   **Assinaturas**: Gestão de planos (Free, Pro, Enterprise) e upgrades/downgrades.
-   **Faturas (Invoices)**: Histórico completo de pagamentos e download de faturas.
-   **Dados de Pagamento**: Gerenciamento seguro de cartões e métodos de pagamento.

### 🤖 Gestão de Projetos & Crawls
-   **Monitoramento em Tempo Real**: Dropdown de "Em Progresso" para acompanhar crawls ativos.
-   **Meus Sites**: Listagem centralizada de todos os domínios do usuário com status rápido.
-   **Adicionar Website**: Fluxo simplificado para configuração de novos jobs de sitemap.
-   **Notificações**: Central de alertas para avisar sobre conclusão de sitemaps ou problemas encontrados.

### 🔌 API & Desenvolvedor
-   **Gestão de API Keys**: Área dedicada para o usuário gerar e revogar tokens de acesso pessoal.
-   **Webhooks**: Configuração de URLs para receber notificações de eventos do sistema.

### 👤 Área do Usuário
-   **Preferências**: Configurações globais de conta.
-   **Suporte & Ajuda**: Acesso rápido à documentação e canais de suporte diretamente da barra de navegação.

## 🛠️ Instalação e Ambiente

### Pré-requisitos
-   PHP 8.4+
-   Node.js 20+
-   Composer
-   Banco de Dados (MySQL 8+ ou PostgreSQL)

### Passos para Desenvolvimento

1.  **Clone o repositório**
    ```bash
    git clone https://github.com/seu-usuario/sitemap-saas.git
    ```

2.  **Instale Dependências**
    ```bash
    composer install
    npm install
    ```

3.  **Configuração de Ambiente**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Banco de Dados & Migrations**
    ```bash
    php artisan migrate
    ```

5.  **Inicie os Servidores**
    ```bash
    # Terminal 1 (Laravel)
    php artisan serve

    # Terminal 2 (Vite/Frontend)
    npm run dev
    ```

## 🔗 Integração Backend Python

Este frontend opera como o orquestrador para a API Python (**Gerador de Sitemaps Perfeito**). Certifique-se de configurar as rotas da API no `.env`:

```env
# Conexão com Crawler
PYTHON_CRAWLER_API=http://localhost:8000/api/v1
PYTHON_API_SECRET=seu_token_secreto
```

---
© 2026 **SyNesis Tecnologia**. Todos os direitos reservados.
