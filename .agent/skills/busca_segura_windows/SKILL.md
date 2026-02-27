---
name: busca_segura_windows
description: "Regras para buscas no Windows 11 priorizando MCP e evitando travamentos de PTY."
version: 1.1.0
---

# Skill: Busca Segura Windows (Anti-Freeze)

## Contexto
Esta skill deve ser utilizada SEMPRE que houver necessidade de localizar arquivos, trechos de código ou listar diretórios no Windows 11, substituindo comandos de shell perigosos.

## Regras de Execução

### 1. Localização de Arquivos (Substitui `find` e `ls -R`)
- **Ação:** NUNCA execute `find` ou `ls` no terminal.
- **Ferramenta:** Utilize `find_by_name`.
- **Parâmetros:** Passe apenas o nome ou parte do nome e o diretório base relativo.

### 2. Busca de Conteúdo/Texto (Substitui `grep` e `findstr`)
- **Ação:** Proibido o uso de `grep` via `run_command`.
- **Ferramenta:** Utilize `grep_search`.
- **Configuração:**
    - `include`: Use para filtrar extensões (ex: `*.php`, `*.vue`).
    - `pattern`: O termo de busca.

### 3. Listagem de Estrutura (Substitui `ls` e `dir`)
- **Ação:** Evite despejar listagens gigantescas no terminal.
- **Ferramenta:** - Use `list_dir` para uma visão rápida do nível atual.
    - Para projetos Laravel, use `mcp_laravel-files_list_directory` para manter a compatibilidade com a estrutura do framework.

### 4. Leitura de Arquivos (Substitui `cat` e `type`)
- **Ação:** Nunca use `cat` para arquivos grandes.
- **Ferramenta:** `view_file`.
- **Segurança:** Se o arquivo for muito extenso, use a leitura por blocos (offset/limit) se disponível na ferramenta, ou peça para o usuário confirmar antes de ler arquivos > 500 linhas.

## Fluxo de Recuperação
Se por algum motivo um comando de busca "congelar":
1. Identifique o processo órfão (provavelmente o binário da ferramenta MCP).
2. Não tente repetir o comando imediatamente.
3. Utilize o protocolo de limpeza definido no GEMINI.md (Item 6.e).