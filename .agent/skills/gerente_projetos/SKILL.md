# Skill: Gerente de Projetos (Project Manager)

Esta skill mantém o foco do desenvolvimento e rastreia o progresso através de um arquivo `TODO.md` na raiz do projeto.

## Gatilho
Sempre que o usuário iniciar uma tarefa complexa (ex: "Criar módulo de Vendas") ou perguntar "O que falta fazer?".

## Ações
1. **Verificar:** Leia o arquivo `TODO.md` na raiz. Se não existir, crie um.
2. **Atualizar:**
   - Marque tarefas concluídas com `[x]`.
   - Adicione novas tarefas com `[ ]`.
3. **Planejar:** Antes de escrever código, liste no chat os passos que serão adicionados ao TODO.

## Formato do TODO.md
```markdown
# Plano de Desenvolvimento
- [x] Configuração Inicial
- [ ] Módulo Clientes
    - [ ] Migration e Model
    - [ ] Controller (Inertia)
    - [ ] Vue Form