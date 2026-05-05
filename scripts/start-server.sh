#!/bin/bash

# =============================================================================
# start-server.sh — Script de inicialização do Genmap em produção/homologação
#
# Uso:
#   chmod +x scripts/start-server.sh
#   ./scripts/start-server.sh
#
# O script sobe os processos em background usando tmux (instale se não tiver:
#   sudo apt install tmux)
#
# Para verificar os processos rodando:
#   tmux ls
#
# Para entrar em uma sessão (ex: "fila"):
#   tmux attach -t fila
#
# Para parar tudo:
#   ./scripts/stop-server.sh  (ou: tmux kill-server)
# =============================================================================

set -e

# Diretório raiz do Laravel (ajuste se necessário)
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo ""
echo "=== Genmap — Inicializando serviços ==="
echo "Diretório: $APP_DIR"
echo ""

# Função auxiliar: mata sessão tmux se existir, depois cria nova
start_tmux() {
    local SESSION=$1
    local CMD=$2
    local LABEL=$3

    tmux kill-session -t "$SESSION" 2>/dev/null || true
    tmux new-session -d -s "$SESSION" -c "$APP_DIR" "bash -c '$CMD; echo; echo \"Processo encerrado. Pressione ENTER para sair.\"; read'"
    echo "  ✓ $LABEL  →  tmux attach -t $SESSION"
}

# -------------------------------------
# 1. Fila de Jobs (Queue Worker)
# -------------------------------------
start_tmux "fila" "php artisan queue:work --sleep=3 --tries=3 --backoff=10" "Queue Worker"

# -------------------------------------
# 2. Scheduler (rotinas periódicas)
# -------------------------------------
start_tmux "scheduler" "php artisan schedule:work" "Scheduler"

# -------------------------------------
# 3. Reverb (WebSocket)
# -------------------------------------
start_tmux "reverb" "php artisan reverb:start --host=0.0.0.0 --port=8080" "Reverb WebSocket"

# -------------------------------------
# 4. Stripe Webhook Listener
# -------------------------------------
start_tmux "stripe" "stripe listen --forward-to http://sitemap.test/stripe/webhook" "Stripe Listener"

echo ""
echo "==================================================="
echo "  Serviços iniciados! Verifique com: tmux ls"
echo ""
echo "  Processos ativos:"
tmux ls 2>/dev/null || echo "  (nenhum)"
echo "==================================================="
echo ""

# -------------------------------------
# Verificação de saúde (opcional)
# -------------------------------------
echo "Verificando conexão com a API Python..."
HEALTH_URL=$(grep -m1 '^SITEMAP_API_URL=' "$APP_DIR/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | tr -d ' ')
if [ -z "$HEALTH_URL" ]; then
    HEALTH_URL=$(grep -m1 '^SITEMAP_GENERATOR_BASE_URL=' "$APP_DIR/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | tr -d ' ')
fi
HEALTH_URL="${HEALTH_URL%/}"

if [ -n "$HEALTH_URL" ]; then
     HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$HEALTH_URL" 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS" = "200" ]; then
        echo "  ✓ API Python respondendo em $HEALTH_URL"
    else
        echo "  ✗ API Python NÃO está respondendo em $HEALTH_URL (status: $HTTP_STATUS)"
        echo "    Verifique se o crawler está rodando!"
    fi
fi

echo ""
echo "Pronto! O sistema está operacional."
echo ""
