---
name: Idioma Português Brasileiro Obrigatório
description: Garante que toda a comunicação, artefatos e código gerados pelo agente sejam estritamente em Português Brasileiro.
---

# Visão Geral
Esta skill impõe o uso do Português Brasileiro (pt-BR) como o idioma padrão e obrigatório para todas as interações do agente com o usuário. Isso se aplica a respostas no chat, criação de arquivos de documentação (artefatos) e, conforme preferência do usuário, até mesmo na nomenclatura de código.

# Regras Obrigatórias

## 1. Comunicação no Chat
- Todas as respostas, explicações e "pensamentos" (thinking) devem ser escritos em **Português Brasileiro**.
- Evite misturar inglês no meio das frases, a menos que sejam termos técnicos universais (ex: framework, deploy, commit) que não tenham tradução natural.

## 2. Artefatos e Documentação
Todos os arquivos de controle e planejamento gerados pelo agente devem ser escritos integralmente em Português. Isso inclui, mas não se limita a:
- **implementation_plan.md**: Títulos, seções ("User Review Required" -> "Revisão do Usuário Necessária", "Proposed Changes" -> "Alterações Propostas", etc.) e descrições.
- **task.md**: Todos os itens da lista de tarefas.
- **walkthrough.md**: Descrições de testes e logs de alterações.
- **README.md**: Documentação do projeto.

## 3. Código e Nomenclatura
Conforme regra global do usuário, a nomenclatura no código deve seguir o português:
- **Variáveis**: `$usuario`, `$pedido`, `calcularTotal()`.
- **Comentários**: Sempre em português.
- **Nomes de Arquivos**: Se não forem arquivos de framework que exigem padrão inglês (como alguns do Laravel), prefira nomes em português (ex: `RelatorioController.php` é aceitável pois mistura o conceito técnico, mas `User` deve ser `Usuario` se for um model personalizado criar preferencialmente em PT se o framework permitir sem atritos graves).

## 4. Estrutura de Artefatos (Templates Traduzidos)

Ao criar os artefatos padrão, traduza os cabeçalhos sugeridos pelo sistema:

### Implementation Plan
```markdown
# [Descrição do Objetivo]
## Revisão do Usuário Necessária
## Alterações Propostas
### [Nome do Componente]
#### [MODIFICAR] [arquivo]
## Plano de Verificação
### Testes Automatizados
### Verificação Manual
```

### Walkthrough
```markdown
# Walkthrough - [Título]
## Alterações Realizadas
## O que foi testado
## Resultados da Validação
```
