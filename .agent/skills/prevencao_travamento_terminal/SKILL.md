---
name: prevencao_travamento_terminal
description: "Diretrizes obrigatórias para evitar o travamento (deadlock) do terminal e comandos suspensos no ambiente Windows 11 / Laragon."
version: 1.0.0
---

# Skill: Prevenção de Travamento de Terminal (Windows 11)

Esta skill define as regras arquitetônicas obrigatórias que a Inteligência Artificial deve seguir ao executar ferramentas de linha de comando ou manipulação de arquivos no ambiente Windows do usuário, a fim de evitar o congelamento (deadlock) do canal de Pseudo-Terminal (PTY).

## 1. Proibição de Comandos de Busca Nativos via Shell
Fica terminantemente **proibido** utilizar ferramentas utilitárias nativas de Linux ou Windows através do `run_command` (terminal cru) para realizar operações de leitura, varredura ou manipulação de múltiplos arquivos. Tais comandos não emitem "End of File" corretamente se interrompidos, engasgando a sessão.
- **NUNCA USE**: `grep`, `find`, `findstr`, `ls`, `cat` ou `sed` no terminal.

## 2. Uso Obrigatório das Ferramentas da API
Para qualquer necessidade de interação com o sistema de arquivos, o agente deve priorizar 100% de uso de suas ferramentas nativas e encapsuladas na API do Model Context Protocol (MCP):
- Para buscas de conteúdo em arquivos: Usar obrigatoriamente a ferramenta `grep_search`.
- Para localização de arquivos: Usar `find_by_name`.
- Para listagem de diretórios: Usar `list_dir` ou as ferramentas do servidor `laravel-files` (ex: `mcp_laravel-files_list_directory`).
- Para modificação de código: Usar `replace_file_content` ou `multi_replace_file_content`.
- Para leitura: Usar `view_file`.

## 3. Comandos Interativos e Paginadores (Git/NPM)
Comandos essenciais como Git (diff, log) ou inicialização de processos (NPM) devem ser tratados com extremo cuidado para não abrirem paginadores de tela invisíveis (como o `less.exe` ou `vim`), que sequestram o terminal em segundo plano esperando a tecla "Q" do teclado humano.
- Ao usar `git diff` ou `git log`, adicionar a flag `--no-pager` ou definir `PAGER=cat` antes do comando. Exemplo: `PAGER=cat git diff`.
- Para limitar as saídas longas do log, usar `-n <quantidade>`. Exemplo: `git log -n 5`.

## 4. Gerenciamento de Timers
Toda execução remota customizada enviada ao sistema do usuário deve conter um `WaitMsBeforeAsync` seguro e o mais curto possível. Nunca mantenha o script esperando indefinidamente se a ação for suscetível a prompts de confirmação interativos.
