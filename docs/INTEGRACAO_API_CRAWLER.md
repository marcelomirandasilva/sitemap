# Análise de Integração (Crawler System x API)

Esta documentação detalha o contrato de comunicação entre o sistema principal em **Laravel (SitemapGeneratorService)** e o motor de rastreamento em **Python (FastAPI)**. A análise confirmou que o tráfego de dados está consistente e respeitando os schemas de ambos os lados.

## 1. O que o Laravel (Sistema) envia
Quando um novo rastreamento é iniciado via `SitemapGeneratorService::startJob()`, o Laravel faz um `POST` para `http://{fastapi}:8000/api/v1/sitemaps`.

**Headers de Autenticação / Contexto:**
- `X-Internal-Token`: Chave secreta fechada entre os dois sistemas (`services.sitemap_generator.internal_secret`).
- `X-User-Id`: ID numérico do usuário solicitante.
- `X-Project-Id`: ID numérico do projeto associado (opcional).

**Payload JSON Base:**
```json
{
    "start_urls": ["https://exemplo.com.br"],
    "max_depth": 5,
    "max_pages": 500,
    "include_images": true,
    "include_videos": false,
    "delay_between_requests": 1.0,
    "max_concurrent_requests": 10,
    "massive_processing": true,
    "output_directory": "sitemaps/projects/1"
}
```

---

## 2. O que a API Python (FastAPI) espera receber
O arquivo `requests.py` (`SitemapGenerationRequest`) do motor Python possui validações rigorosas do Pydantic que casam perfeitamente com os dados acima.

**Validações Impostas pela API:**
- `start_urls`: Lista obrigatória de domínios válidos validados pela biblioteca externa `validators`.
- `max_depth`: Aceita entre `1` e `10`.
- `max_pages`: Aceita entre `1` e `100.000`. Cobre perfeitamente o seu Free (500) até planos Enterprise.
- `delay_between_requests`: Float restrito entre `0.1` e `10.0` segundos.
- `max_concurrent_requests`: Limitado entre `1` e `100` threads simultâneas.
- **Parâmetros Opcionais Padrões:** A API assume `True` para `include_news`, `include_mobile` e `compress_output` se eles não forem passados. Como o Laravel envia explicitamente `massive_processing` como `true`, a engine utilizará o modo cooperativo e escalável projetado anteriormente.

> [!SUCCESS]
> **Compatibilidade Total:** Não há quebras aqui. Os dados que o Laravel envia (convertidos via Cast `$projeto->check_images` boolean ou floats) estão nos limites exatos do payload Pydantic da API.

---

## 3. O que o Sistema Laravel espera receber de retorno
### A. Retorno de Criação do Job
Após disparar o POST de criação, o Laravel aguarda que a API Python devolva um Header `201 Created` contendo minimamente a chave `job_id`. O retorno mapeado pelo FastAPI (`JobCreatedResponse`) garante o envio dinâmico do UUID (`job_id`), seguido de `status`, `message` e estimativa de duração. O Laravel acata isso e atrela o UUID à tabela temporária do BD.

### B. Retorno de Status (`/api/v1/sitemaps/{job_id}`)
Quando a UI ou o backend solicitam a progressão do Crawler ou seu encerramento, o Laravel espera mapeamentos em duas ramificações.

O Schema Python (`JobStatusResponse`) envia:
```json
{
    "job_id": "abc...",
    "status": "completed",
    "progress": 100.0,
    "phase": "done",
    "result": { ... },
    "artifacts": [ ... ]
}
```

O `SitemapGeneratorService` possui um **parser inteligente retro-compatível** (`checkStatus()`). Se a API enviar um array moderno com `artifacts`, ele o lê diretamente apontando links de download e tamanho (`size_bytes`). Caso a API envie o formato de resposta legado do Uvicorn com `result: { main_sitemap_path: ... }`, ele constrói o array mapeando `xml` por `xml`.

### C. Retorno do Arquivo/ZIP
Para visualização e download de logs brutos, o Laravel tentará procurar os arquivos no disco (`file_exists`) graças ao mapeamento do volume Docker. Se não encontrar, ele implementa um graceful fallback e aciona a API via `GET /api/v1/sitemaps/{job_id}/artifacts/{filename}` que devolve os bytes binários sob os headers internos de proteção.

---

### Diagnóstico Final 🎯
O fluxo entre `PHP` e `Python` está **perfeitamente enxuto e seguro**.
O uso combinado do `X-Internal-Token` excluiu a necessidade de dependências complexas de JWT como as que causavam timeout anteriormente.
As barreiras impostas e os castings em `(int)` e `(float)` do Laravel blindaram a API de receber "sujeira" do painel de controle.
