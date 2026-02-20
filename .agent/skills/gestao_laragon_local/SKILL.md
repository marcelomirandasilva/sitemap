---
name: gestao_laragon_local
description: "Orientações para execução de comandos PHP, Composer e MySQL dentro do ambiente Laragon no Windows 11."
version: 1.0.0
---

# Skill: Gestão Laragon Local

Esta skill garante que o agente utilize corretamente as ferramentas instaladas via Laragon, respeitando as particularidades do ambiente Windows.

## 1. Localização de Binários
- **Contexto:** Os comandos devem ser executados considerando que o PHP e o MySQL estão no PATH gerenciado pelo Laragon.
- **Verificação:** Antes de sugerir um comando `php artisan` ou `composer`, verifique se o terminal da IDE está integrado ao shell do Laragon (se disponível) para evitar conflitos de versão do PHP.

## 2. Banco de Dados (MySQL/MariaDB)
- **Acesso:** Utilize o `mysql` via terminal para operações rápidas. O host padrão é `127.0.0.1` ou `localhost`, geralmente com o usuário `root` e sem senha (padrão Laragon).
- **Criação de DB:** Caso uma migration falhe por falta de banco, sugira o comando: `mysql -u root -e "CREATE DATABASE nome_do_banco;"`.

## 3. Virtual Hosts e URLs
- **Domínios .test:** Lembre-se que o Laragon gera automaticamente URLs como `http://projeto.test`. Sempre que sugerir testes de rota, utilize esse padrão de domínio local.
- **Servidor:** Não utilize `php artisan serve` a menos que o Apache/Nginx do Laragon esteja desligado, para evitar conflito na porta 80.

## 4. Manipulação de Arquivos no Windows
- **Caminhos:** Use sempre barras invertidas `\` para caminhos de diretórios ao sugerir comandos de sistema, mas mantenha `/` dentro do código PHP/Laravel conforme as PSRs.
- **Permissões:** No Windows/Laragon, problemas de permissão de escrita (Writable) costumam ser resolvidos verificando se a pasta `storage` não está como "Somente Leitura" nas propriedades do Windows.