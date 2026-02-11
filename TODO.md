# Roadmap: Clone Pro-Sitemaps (Laravel + Vue)

## 1. O Motor (Crawler & Configurações)
Baseado em: `/config-crawler`, `/config-custom`, `/crawler-bot`
- [ ] **Configuração do Robô**
    - [ ] Definir User-Agent customizado (Para simular Googlebot ou nosso próprio bot).
    - [ ] Configurar atraso entre requisições (Throttle) para não derrubar servidores.
    - [ ] Lista de IPs permitidos/bloqueados.
- [ ] **Filtros de URL**
    - [ ] Inclusão/Exclusão de pastas específicas.
    - [ ] Ignorar parâmetros de URL (ex: `session_id`).

## 2. Ferramentas de Diagnóstico (O Diferencial)
Baseado em: `/sitemap-broken`, `/sitemap-extlinks`, `/sitemap-structure`
- [ ] **Verificador de Links Quebrados (Broken Links)**
    - [ ] Identificar erros 404 e 500.
    - [ ] Relatório de onde o link quebrado foi encontrado.
- [ ] **Análise de Links Externos**
    - [ ] Listar todos os links que saem do site (Outbound links).
- [ ] **Visualização de Estrutura**
    - [ ] Mostrar a hierarquia do site (Pastas/Categorias) em árvore.

## 3. Gestão e Exportação
Baseado em: `/sitemap-downloads`, `/sitemap-submit`, `/sitemap-api`
- [ ] **Downloads**
    - [ ] XML Gzipado (`.xml.gz`).
    - [ ] Arquivos de texto (`.txt`).
    - [ ] HTML Sitemap para humanos.
- [ ] **Integrações**
    - [ ] API: Endpoint para clientes gerarem sitemaps via código.
    - [ ] WordPress: Planejar um plugin simples que conecta com nossa API.
- [ ] **Auto-Submit**
    - [ ] Botão para pingar Google e Bing automaticamente.

## 4. Painel do Usuário (SaaS)
Baseado em: `/account-subs`, `/account-invoice`, `/dashboard`
- [ ] **Assinaturas**
    - [ ] Integração com Gateway de Pagamento (Stripe/Asaas).
    - [ ] Diferenciar contas Free (até 500 pgs) vs Pro (ilimitado).
- [ ] **Histórico**
    - [ ] Logs de crawls passados (`/sitemap-log`).