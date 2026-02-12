# Skill: Busca Segura no Windows 11

Esta skill define as regras obrigatórias para realizar buscas de texto (grep/findstr) neste projeto, evitando o travamento do terminal Windows.

## 1. Tratamento de Comandos de Terminal
- **Proibição de Escapes:** Nunca utilize barras invertidas para escapar aspas (`\"`) em comandos de terminal. Isso causa travamento infinito no Windows 11.
- **Uso de Aspas Simples:** Para buscar termos que contenham aspas duplas (como v-model), envolva o padrão inteiro em aspas simples.
    - **Correto:** `grep -n 'v-model="form.n_documento"' arquivo.vue`
    - **Incorreto:** `grep -n "v-model=\"form.n_documento\"" arquivo.vue`

## 2. Fallback de Erro
- Se um comando de busca (`grep`) retornar "Running..." por mais de 5 segundos sem saída, você deve cancelar a operação imediatamente.
- Em caso de travamento ou erro de sintaxe, utilize a ferramenta de leitura de arquivos para ler o conteúdo completo do arquivo e localizar o termo visualmente.

## 3. Idioma e Localização
- Todas as mensagens de erro, logs de busca e "Thoughts" sobre a busca devem ser mantidos em português.
- Caso precise criar um script temporário de busca, utilize nomes em português (ex: `busca_termo.ps1`).