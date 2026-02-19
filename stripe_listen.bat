@echo off
echo --- Iniciando Listener do Stripe ---
echo Certifique-se que o stripe.exe esta na mesma pasta ou no PATH.
echo Redirecionando para: localhost:8000/stripe/webhook
echo.
REM Se estiver usando Laragon com dominio virtual (ex: sitemap.test), edite a linha abaixo.
stripe.exe listen --forward-to localhost:8000/stripe/webhook
pause
