---
description: Limpeza completa de caches do Laravel e otimização
---

Este workflow executa uma limpeza completa de todos os caches do Laravel e recria os arquivos de otimização. Útil após alterações em `.env`, configurações ou rotas.

# Passos

1. Limpar cache de configuração
// turbo
2. Executar `php artisan config:clear`

3. Limpar cache de rotas
// turbo
4. Executar `php artisan route:clear`

5. Limpar cache de views
// turbo
6. Executar `php artisan view:clear`

7. Recriar cache de configuração (otimização)
// turbo
8. Executar `php artisan config:cache`

9. Recriar cache de rotas (otimização)
// turbo
10. Executar `php artisan route:cache`

11. Opcional: Limpar cache geral da aplicação
// turbo
12. Executar `php artisan cache:clear`
