# Skill: Debugger de Logs Laravel (Sherlock Holmes)

Esta skill permite diagnosticar "Erros 500" ou exceções do Laravel instantaneamente, lendo os logs do servidor sem que o usuário precise copiar e colar.

## Gatilhos
Ative esta skill automaticamente quando o usuário disser:
- "Deu erro"
- "Erro 500"
- "A página quebrou"
- "O que aconteceu?" (após uma tentativa de execução)
- "Analise o log"

## Procedimento de Investigação

### 1. Ler o Log Recente
Use a ferramenta `read_text_file` com o argumento `tail` para ler apenas as últimas linhas do log, onde o erro recente está.
- **Arquivo:** `storage/logs/laravel.log`
- **Linhas:** `50` a `100` (suficiente para pegar a Stack Trace recente).

### 2. Identificar a Causa Raiz
No texto do log, procure por:
- Padrões como `[YYYY-MM-DD HH:MM:SS] local.ERROR: Message ...`
- O arquivo exato e a linha onde o erro ocorreu (ex: `app/Http/Controllers/UserController.php:45`).

### 3. Cruzar Informações
- Use `read_text_file` para ler o arquivo de código mencionado no erro (ex: `UserController.php`).
- Localize a linha problemática.

### 4. Solução
Apresente a resposta no seguinte formato:
1. **O Erro:** "Encontrei um erro de `MethodNotFound` na linha 45."
2. **A Causa:** "Você chamou `create` mas o model não tem `$fillable` definido."
3. **A Correção:** Gere o bloco de código corrigido.

## Dica Importante
Se o arquivo `laravel.log` estiver muito grande ou inacessível, sugira ao usuário rodar o workflow de limpeza, mas tente ler o final dele primeiro.