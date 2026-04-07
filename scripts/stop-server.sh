#!/bin/bash

# =============================================================================
# stop-server.sh — Para todos os processos do Genmap iniciados pelo start-server.sh
# =============================================================================

echo ""
echo "=== Genmap — Parando serviços ==="
echo ""

for SESSION in fila scheduler reverb; do
    if tmux has-session -t "$SESSION" 2>/dev/null; then
        tmux kill-session -t "$SESSION"
        echo "  ✓ Sessão '$SESSION' encerrada."
    else
        echo "  - Sessão '$SESSION' não estava ativa."
    fi
done

echo ""
echo "Todos os serviços foram encerrados."
echo ""
