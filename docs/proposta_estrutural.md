# Proposta Estrutural: SiteMapGen
**Data de Revisão:** 22/01/2026
**Responsável:** Equipe de Desenvolvimento, eu
**Status:** Em Desenvolvimento Ativo

---

## 1. Visão de Arquitetura

O sistema é uma plataforma **SaaS** construída sobre uma arquitetura híbrida e desacoplada, focada em alta performance e experiência de usuário fluida.

### Stack Tecnológico (Modernizado)
-   **Frontend**: Vue.js 3 (Composition API) + **Inertia.js 2.0**.
-   **Backend**: **Laravel 12** (PHP 8.4+).
-   **Estilização**: **Tailwind CSS 4.0** (Engine Rust).
-   **Banco de Dados**: MySQL 8.0 + Redis (Cache/Filas).
-   **Engine (Motor de Crawling)**: Python (Serviço Autônomo).
-   **Extras**: 
    -   **Laravel Socialite** (Auth Social).
    -   **Laravel Vue i18n** (Internacionalização).

### Diagrama Lógico de Alto Nível
1.  **Usuário** acessa o Painel via **Inertia SPA**.
2.  **Laravel** gerencia autenticação, assinaturas e orquestração.
3.  **Jobs** de sitemap são despachados para filas (Redis).
4.  **Engine Python** processa o crawling de forma distribuída.
5.  **Webhooks** atualizam o status no painel em tempo real.

---

## 2. Módulos do Sistema

### Módulo Core & Autenticação
-   Login/Registro com Email e Redes Sociais (Google/GitHub).
-   Gestão de Perfil e Preferências.
-   **Internacionalização (I18n)**: Suporte nativo PT-BR / EN-US.
-   **Multitenancy**: Isolamento lógico de dados por usuário.

### Módulo de Sitemaps (Gestão)
-   **Dashboard**: Visão geral de projetos e status de crawls.
-   **Meus Sites**: Listagem e status rápido de domínios.
-   **Crawls em Progresso**: Monitoramento em tempo real no Topbar.
-   **Exportação**: Download seguro de XMLs.

### Módulo Financeiro (SaaS)
-   **Gestão de Assinaturas**: Controle de planos (Free, Pro, Enterprise).
-   **Faturas (Invoices)**: Histórico e download de comprovantes.
-   **Métodos de Pagamento**: Gestão de cartões e billing.

### Módulo de API & Desenvolvedor
-   **API Keys**: Geração de tokens pessoais para automação.
-   **Webhooks**: Configuração de callbacks para eventos do sistema.
-   **Documentação**: Acesso direto à central de ajuda.

---

## 3. Integrações e Infraestrutura

1.  **Engine Python**: Via API REST Privada + Filas.
2.  **Storage**: AWS S3 / MinIO para armazenamento de XMLs.
3.  **Email**: SMTP/SES para notificações transacionais.
4.  **Pagamentos**: Stripe/Paddle (Internacional) e Mercado Pago/Pagar.me (Brasil).

---

## 4. Cronograma de Execução (2026)

**Prazo Total:** 19/01/2026 a 27/04/2026

### 📅 Macros & Marcos Oficiais

-   **19/01 (Seg)**: Kickoff e Brainstorming. **(Concluído)**
-   **20/01 (Ter)**: Alinhamento de Requisitos. **(Concluído)**
-   **23/01 (Sex)**: Entrega da Proposta Estrutural. **(Concluído)**
-   **26/01 (Seg)**: 🚀 **Início do Desenvolvimento** (Execução).
-   **29/01 (Qui)**: 🤝 Reunião Técnica: Gateways de Pagamento.
-   **26/03 (Qui)**: 🎥 **Demonstração Funcional** (Fim da Fase de Dev).
-   **27/04 (Seg)**: 🏆 **Entrega Final** (Produção).

### ⚡ Fases de Desenvolvimento (26/01 a 26/03)

-   [x] **Fase 1: Fundação & Setup** (Semana 1)
    -   Setup Laravel 12 + Vue 3, Auth Social, Layout.

-   [ ] **Fase 2: Integração & Ferramentas** (Semana 2-4)
    -   [ ] **Conexão Engine Python** (Prioridade).
    -   [ ] **Módulo de API** (Tokens e Docs).
    -   [ ] **Suporte & Ajuda** (Central de Ajuda).

-   [ ] **Fase 3: Core SaaS & Financeiro** (Semana 5-8)
    -   *Marco: Definição de Gateways em 29/01*
    -   [x] Internacionalização (I18n).
    -   [ ] **Módulo Financeiro**:
        -   [ ] Tela de Assinaturas (Subscriptions).
        -   [ ] Tela de Faturas (Invoices).
        -   [ ] Detalhes de Pagamento (Cartões).
        -   [ ] Integração Gateway (Stripe/MercadoPago).
    -   [ ] **Painel Administrativo**.

-   [ ] **Fase 4: Estabilização & QA** (27/03 a 24/04)
    -   Testes de carga, debug e ajustes finais pré-entrega.

---
**Observação**: Cronograma alinhado com Pablo Murad.
