# Operacao do Sistema

Este documento resume os pontos operacionais que precisam estar ativos para o sistema funcionar corretamente em producao e em homologacao.

## 1. Scheduler do Laravel

O sistema depende do scheduler para executar rotinas periodicas. Hoje existem pelo menos estas tarefas:

- `php artisan assinaturas:reconciliar-planos`
- `php artisan projetos:agendar-rastreamentos`

Em ambiente persistente, mantenha um destes modos ativos:

- `php artisan schedule:work`
- ou um cron chamando `php artisan schedule:run` a cada minuto

Sem isso:

- planos vencidos podem continuar com dados desatualizados
- recrawls automaticos por frequencia nao serao disparados

## 2. Fila de jobs

O sistema despacha jobs em background, como o processamento de artefatos apos a conclusao do crawler.

Se o `QUEUE_CONNECTION` nao estiver em `sync`, mantenha um worker ativo:

- `php artisan queue:work`

Sem isso:

- artefatos podem nao ser importados
- paginas, links e contagens podem demorar ou nao aparecer no painel

## 3. Webhook interno da API Python

A API Python notifica a conclusao de jobs em:

- `POST /api/internal/webhook/job-completed`

Esse webhook:

- fecha o job localmente
- atualiza `last_crawled_at`
- limpa `current_crawler_job_id`
- recalcula `next_scheduled_crawl_at`
- dispara o processamento de artefatos

Requisitos:

- `services.sitemap_generator.internal_secret` igual nos dois sistemas
- Laravel acessivel pela API Python

Sem isso:

- jobs podem ficar presos como `running`
- proximos agendamentos podem nao ser recalculados

## 4. API Python de crawler

O Laravel depende da API Python para:

- criar jobs
- consultar status
- cancelar jobs
- listar e baixar artefatos

Verificacoes minimas:

- `services.sitemap_generator.base_url` configurado
- endpoint `/api/v1/health` respondendo
- token interno valido

## 5. Migracoes e schema

Sempre que houver mudanca estrutural:

- rode `php artisan migrate --force`

O projeto atualiza o schema dump automaticamente. Isso precisa acompanhar os commits quando a estrutura do banco mudar.

## 6. Frontend

Sempre que houver alteracao na interface:

- rode `npm run build`

Se a aplicacao estiver servindo assets antigos, faca limpeza de cache do navegador e confira o manifesto em `public/build`.

## 7. Rotina minima de verificacao apos deploy

Checklist objetivo:

- `php artisan migrate --force`
- `php artisan schedule:list`
- scheduler ativo com `schedule:work` ou cron equivalente
- worker de fila ativo se a fila nao for `sync`
- `npm run build` ou deploy dos assets gerados
- endpoint `/api/v1/health` da API Python respondendo
- webhook interno alcancavel entre Python e Laravel
