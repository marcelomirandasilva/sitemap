---
name: git_commit_expert
description: "Gera mensagens de commit padronizadas e descritivas para o GitHub seguindo o padrão Conventional Commits."
version: 1.0.0
---

# Skill: Gerador de Commits Semânticos

Esta skill define as regras para criar mensagens de commit claras, em português, e estruturadas para o repositório GitHub do projeto.

## 1. Estrutura da Mensagem
- **Formato:** `<tipo>(<escopo>): <descrição curta em minúsculas>`
- **Tipos Permitidos:**
    - `feat`: Nova funcionalidade (ex: novo crawler, nova rota Laravel).
    - `fix`: Correção de bug (ex: erro de escape no Windows, erro no Vue).
    - `docs`: Alterações na documentação ou arquivos de skill.
    - `style`: Formatação, aspas faltando, semi-colons (sem alteração de lógica).
    - `refactor`: Mudança no código que não corrige erro nem adiciona feature.
    - `chore`: Atualização de dependências ou configurações de build/Docker.

## 2. Regras de Conteúdo
- **Idioma:** O título e o corpo do commit devem ser em **português**.
- **Corpo do Commit:** Se a mudança for complexa, adicione uma linha em branco e explique o "porquê" da mudança em tópicos (bullet points).
- **Rodapé:** Liste eventuais Breaking Changes ou referências a Issues (ex: `Ref: #123`).

## 3. Exemplo de Saída Esperada
- `feat(crawler): implementa geração de sitemap em segundo plano com Laravel Queue`
- `fix(windows): ajusta escape de aspas em comandos grep para evitar travamento`

## 4. Gatilho de Execução
- Sempre que eu solicitar "crie um commit" ou "descreva o que fizemos", analise o `git diff` atual e aplique estas regras.