---
name: gestao_laragon_local
description: "Orientações para execução de comandos PHP, Composer e MySQL dentro do ambiente Laragon no Windows 11."
version: 1.0.0
---

# Skill: Gestão Laragon Local

Esta skill garante que o agente utilize corretamente as ferramentas instaladas via Laragon, respeitando as particularidades do ambiente Windows.

## 1. Localização de Binários
- **Caminhos Fixos:** Utilize SEMPRE os caminhos absolutos definidos nas Diretrizes Globais (GEMINI.md) para `php`, `mysql` e `composer`.
- **Execução:** Garanta que os comandos usem o formato `/c/laragon/...` para evitar erros de "comando não encontrado" no MINGW64.

## 2. Banco de Dados (MySQL/MariaDB)
- **Acesso:** Utilize o `mysql` via terminal para operações rápidas. O host padrão é `127.0.0.1` ou `localhost`, geralmente com o usuário `root` e sem senha (padrão Laragon).
- **Criação de DB:** Caso uma migration falhe por falta de banco, sugira o comando: `mysql -u root -e "CREATE DATABASE nome_do_banco;"`.

## 3. Virtual Hosts e URLs
- **Domínios .test:** Lembre-se que o Laragon gera automaticamente URLs como `http://projeto.test`. Sempre que sugerir testes de rota, utilize esse padrão de domínio local.
- **Servidor:** Não utilize `php artisan serve` a menos que o Apache/Nginx do Laragon esteja desligado, para evitar conflito na porta 80.

## 4. Manipulação de Arquivos no Windows
- **Caminhos:** No terminal (`run_command`), use sempre barras normais `/` e o formato de unidade do Git Bash (ex: `/d/www/sitemap`). NUNCA use barras invertidas `\` ou `D:\` em comandos de shell.
- **Permissões:** No Windows/Laragon, problemas de permissão de escrita (Writable) costumam ser resolvidos verificando se a pasta `storage` não está como "Somente Leitura" nas propriedades do Windows.