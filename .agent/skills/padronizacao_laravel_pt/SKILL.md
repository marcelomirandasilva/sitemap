---
name: padronizacao_laravel_pt
description: "Garante que todo o código Laravel (Controllers, Models, Migrations) siga a nomenclatura em português e as convenções de código do usuário."
version: 1.0.0
---

# Skill: Padronização Laravel em Português

Esta skill orienta a criação de componentes Laravel respeitando o idioma português do Brasil e o ambiente Windows 11/Laragon.

## 1. Regras de Nomenclatura
- **Variáveis e Funções:** Devem ser sempre em português e em `snake_case`.
    - **Correto:** `$usuario_logado`, `funcao buscar_dados()`.
    - **Incorreto:** `$currentUser`, `function getData()`.
- **Classes e Models:** Utilize PascalCase em português.
    - **Exemplo:** `class GerenciarPedido`, `model RelatorioVenda`.
- **Controllers:** Mantenha o sufixo "Controller", mas o prefixo em português.
    - **Exemplo:** `ProdutoController`.

## 2. Estrutura de Métodos (Resource)
Ao criar controllers do tipo resource, utilize os nomes de métodos padrão do Laravel para manter a compatibilidade com o roteamento, mas com comentários internos em português:
- `index`: Lista os registros.
- `create`: Formulário de criação.
- `store`: Salva o novo registro.
- `show`: Exibe um item específico.
- `edit`: Formulário de edição.
- `update`: Atualiza o registro.
- `destroy`: Exclui o registro.

## 3. Comentários e Documentação
- Todo e qualquer comentário de código ou bloco de DocBlock deve ser escrito em português claro.
- Explique o "porquê" da lógica, não apenas o "quê".

## 4. Integração Laragon/Windows 11
- **Migrations:** Ao sugerir a execução de migrations, use o comando direto: `php artisan migrate`.
- **Logs:** Se houver erro, instrua o agente a verificar o log em `storage\logs\laravel.log` usando o comando PowerShell: `Get-Content -Tail 50 storage\logs\laravel.log`.

## 5. Idioma de Saída (Chat/Thoughts)
- O raciocínio (Thought) e a explicação da implementação devem ser obrigatoriamente em português do Brasil.