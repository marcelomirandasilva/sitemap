@echo off
title Stripe Webhook Listener
echo Iniciando ouvinte de webhooks locais do Stripe...
cd /d D:\www\sitemap
stripe listen --forward-to http://sitemap.test/stripe/webhook
pause
