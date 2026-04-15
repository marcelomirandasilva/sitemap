# Fila Priorizada de Implementacao

Este documento registra a fila atual de desenvolvimento acordada para aproximar o sistema do posicionamento comercial e tecnico definido para o GenMap.

## Ordem de execucao

1. Pagina publica de changelog
Status: concluido
Objetivo: publicar historico de versoes e mudancas relevantes do produto em uma rota publica.

2. Pagina publica de status
Status: concluido
Objetivo: expor transparência operacional com saude dos servicos principais e historico basico de incidentes.

3. Relatorios de saude de SEO
Status: concluido
Objetivo: concluir e expor ao usuario final os relatorios tecnicos de SEO ja iniciados no codigo.
Observacao: o nucleo existente foi conectado a tela do projeto e ao fluxo de atualizacao de status do rastreamento.

4. Entrega de URL publica fixa hospedada pelo GenMap
Status: pendente
Objetivo: fornecer uma URL estavel do sitemap publicada pela propria plataforma, sem depender de hospedagem no dominio do cliente.

5. Pendencias de seguranca da API Python e hash de API keys
Status: pendente
Objetivo:
- armazenar API keys com hash no Laravel
- validar chaves com comparacao segura na API Python
- remover ou proteger o endpoint legado de autenticacao por bypass
- revisar o endurecimento do fluxo interno entre Laravel e API Python

6. Crawl Delta
Status: pendente
Objetivo: reprocessar apenas paginas novas ou alteradas entre execucoes, com fallback controlado para crawl completo.

7. Itens de roadmap maiores
Status: pendente
Objetivo: tratar os itens estrategicos de maior porte em epicos separados.
Itens principais mapeados atualmente:
- IndexNow
- plugin WordPress
- integracoes com VTEX e Nuvemshop
- app movel de monitoramento
- white-label enterprise
- sugestoes por IA
- marketplace de integracoes
- sitemap visual interativo
- preparacao para ISO 27001

## Ordem pratica recomendada

Pensando em custo, dependencia tecnica e risco de producao, a sequencia recomendada de execucao e:

1. Pagina publica de changelog
2. Pagina publica de status
3. Relatorios de saude de SEO
4. Pendencias de seguranca da API Python e hash de API keys
5. Entrega de URL publica fixa hospedada pelo GenMap
6. Crawl Delta
7. Itens de roadmap maiores

## Regra de atualizacao

Sempre que um item avancar, este documento deve refletir:

- status atual: pendente, parcial, em andamento ou concluido
- observacoes tecnicas relevantes
- dependencia que impacte o proximo item
