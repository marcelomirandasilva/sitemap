---
name: busca_segura_windows
description: "Regras obrigatórias para buscas de texto (grep/findstr) no Windows 11 para evitar travamentos de terminal e erros de escape."
version: 1.0.0
---

# Skill: Busca Segura no Windows 11

Esta skill define as diretrizes de execução de comandos de busca para garantir a estabilidade do ambiente Windows 11.

## 1. Tratamento de Comandos de Terminal
- **Proibição de Escapes:** É terminantemente proibido o uso de barras invertidas para escapar aspas (`\"`). No terminal do Windows 11, isso gera um loop de processo.
- **Uso de Aspas Simples:** Sempre envolva o padrão de busca em aspas simples ao procurar termos que contenham aspas duplas.
    - **Exemplo:** `grep -n 'v-model="form.n_documento"' arquivo.vue`

## 2. Protocolo de Timeout e Fallback
- **Monitoramento:** Se o comando de busca retornar o status "Running..." por mais de 5 segundos, interrompa o processo (SIGINT/Cancel).
- **Recuperação:** Caso o `grep` ou `findstr` falhe ou trave, utilize a função de leitura direta (`read_file`) e realize o parsing do texto em memória.

## 3. Idioma de Operação
- **Pensamentos (Thoughts):** Devem ser gerados em português.
- **Saídas:** Mensagens de erro e logs devem ser apresentados em português.
- **Scripts Temporários:** Devem seguir o padrão nominal em português (ex: `busca_termo.ps1`).