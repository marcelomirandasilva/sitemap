# PRO-Sitemaps - Frontend SaaS

Interface oficial e painel de controle para o sistema **Gerador de Sitemaps Perfeito** (API Python). Este projeto fornece uma experiência de usuário moderna e responsiva para criação de contas, gerenciamento de assinaturas e configuração de jobs de sitemap.

## 🚀 Visão Geral

Este repositório contém o código-fonte do Frontend e da camada de orquestração SaaS, construído com **Laravel**, **Vue.js 3** e **Inertia.js**. Ele se comunica com a API Python de backend para realizar o processamento pesado de crawling e geração de XML.

### Stack Tecnológica

-   **Backend SaaS**: Laravel 10+ (PHP 8.2+)
-   **Frontend**: Vue.js 3 (Composition API)
-   **Roteamento/SPA**: Inertia.js
-   **Estilização**: Tailwind CSS v3
-   **Banco de Dados**: MySQL / PostgreSQL
-   **Autenticação**: Laravel Breeze / Jetstream

## ✨ Funcionalidades do Frontend

-   **Landing Page de Alta Conversão**: Design moderno focado em conversão com card interativo de Login/Signup.
-   **Painel do Usuário (Dashboard)**:
    -   Visão geral de sitemaps gerados.
    -   Status de jobs em tempo real.
    -   Download de arquivos XML.
-   **Integração com API Python**: Conexão transparente com o serviço de crawling distribuído.
-   **Gestão de Assinaturas**: Interface para upgrade de planos (Free vs Pro).

## 🛠️ Instalação e Configuração

### Pré-requisitos

-   PHP 8.2+
-   Composer
-   Node.js & NPM
-   Banco de Dados (MySQL)

### Passos

1.  **Clone o repositório**
    ```bash
    git clone https://github.com/seu-usuario/sitemap.git
    cd sitemap
    ```

2.  **Instale as dependências do PHP**
    ```bash
    composer install
    ```

3.  **Instale as dependências do Node.js**
    ```bash
    npm install
    ```

4.  **Configure o ambiente**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Configure as credenciais do banco de dados no arquivo `.env`.

5.  **Execute as migrações**
    ```bash
    php artisan migrate
    ```

6.  **Inicie o servidor de desenvolvimento**
    -   Backend (Laravel):
        ```bash
        php artisan serve
        ```
    -   Frontend (Vite):
        ```bash
        npm run dev
        ```

## 🔗 Integração com Backend Python

Este frontend espera que a API Python esteja rodando para funcionalidades avançadas. Configure a URL da API no seu `.env`:

```env
PYTHON_API_URL=http://localhost:8000/api
PYTHON_API_TOKEN=seu-token-de-servico
```

## 📄 Licença

Este software é proprietário da **SyNesis Tecnologia**. Todos os direitos reservados.
