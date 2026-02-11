# Relatório de Diagnóstico: Integração Laravel <-> Python API

## Contexto do Ambiente
- **Client (Laravel):** Rodando em Docker (Sail).
- **Server (Python API):** Rodando em Docker.
- **Comunicação:** Via `http://host.docker.internal:8000` (Docker Gateway).

## 1. Problema de Rede (Resolvido)
Inicialmente, recebíamos `cURL error 52: Empty reply from server`.
- **Causa:** O servidor Uvicorn estava rodando com bind padrão (`127.0.0.1`), rejeitando conexões externas vindas do Docker Gateway.
- **Solução Aplicada:** Solicitado alteração no start command para `--host 0.0.0.0`.
- **Status:** Resolvido. O Laravel agora alcança a API (latência ~12ms).

## 2. Problema Atual: Autenticação (401 Unauthorized)
Ao tentar autenticar no endpoint `/api/auth/token`:
- **Request:** POST com JSON `{"username": "admin", "password": "admin123"}`.
- **Response:** `401 Unauthorized`.

### Stack Trace do Erro (Lado Python)
O log do servidor Python mostra que o erro **não é credencial errada**, mas sim um **crash de biblioteca** durante a verificação da senha:

```text
Traceback (most recent call last):
  File ".../passlib/handlers/bcrypt.py", line 620, in _load_backend_mixin
    version = _bcrypt.__about__.__version__
AttributeError: module 'bcrypt' has no attribute '__about__'
```

### Diagnóstico Técnico
Há uma incompatibilidade de versões entre as bibliotecas `passlib` e `bcrypt` instaladas no ambiente Python.
- A biblioteca `bcrypt` lançou a versão **4.0.0+** que removeu o atributo `__about__`.
- A biblioteca `passlib` (instável ou legada) tenta acessar esse atributo e quebra.
- Como o erro ocorre dentro do bloco de verificação de senha, a API captura a exceção e retorna `401 Unauthorized` genericamente.

## Ação Necessária (Correção Python)
É necessário ajustar o `requirements.txt` ou `Dockerfile` do projeto Python para garantir compatibilidade.

**Opção A (Recomendada - Downgrade bcrypt):**
Forçar uma versão do bcrypt compatível com passlib:
```bash
pip install "bcrypt==3.2.0"
```

**Opção B (Upgrade Passlib - se disponível):**
Verificar se há uma versão do `passlib` que suporte bcrypt 4.0, mas o downgrade do bcrypt é a solução mais estável conhecida.
