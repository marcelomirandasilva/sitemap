<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        body { margin: 0; padding: 0; background: #eef6fb; color: #162433; font-family: Arial, Helvetica, sans-serif; }
        .container { max-width: 640px; margin: 0 auto; padding: 32px 18px; }
        .card { overflow: hidden; border: 1px solid #d8e7ef; border-radius: 18px; background: #ffffff; box-shadow: 0 18px 50px rgba(21, 74, 103, 0.12); }
        .header { padding: 26px 32px; background: linear-gradient(135deg, #dff1f7 0%, #ffffff 58%, #f7eee6 100%); border-bottom: 1px solid #d8e7ef; }
        .brand { display: inline-flex; align-items: center; gap: 14px; text-decoration: none; color: #162433; }
        .brand img { width: 48px; height: auto; vertical-align: middle; }
        .brand-name { display: block; font-size: 22px; font-weight: 800; line-height: 1; letter-spacing: -0.02em; }
        .brand-subtitle { display: block; margin-top: 5px; color: #5d7182; font-size: 11px; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; }
        .content { padding: 34px 32px 30px; }
        h1 { margin: 0 0 14px; color: #14324a; font-size: 24px; line-height: 1.25; }
        p { margin: 0 0 16px; color: #3b4d5c; font-size: 15px; line-height: 1.65; }
        .list { margin: 20px 0; padding: 0; list-style: none; }
        .list li { margin: 0 0 10px; padding: 12px 14px; border-left: 4px solid #2aa8c7; border-radius: 10px; background: #f3f9fc; color: #2f4354; font-size: 14px; }
        .button-wrap { margin: 28px 0 8px; }
        .button { display: inline-block; border-radius: 10px; background: #0f568f; color: #ffffff !important; font-size: 13px; font-weight: 800; letter-spacing: 0.04em; padding: 13px 22px; text-decoration: none; text-transform: uppercase; }
        .footer { padding: 20px 32px 28px; background: #f7fbfd; border-top: 1px solid #e4eef4; color: #718392; font-size: 12px; line-height: 1.55; }
        .footer a { color: #0f568f; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <a class="brand" href="{{ config('app.url') }}">
                    <img src="{{ url('/logo.png') }}" alt="{{ $appName }}">
                    <span>
                        <span class="brand-name">{{ $appName }}</span>
                        <span class="brand-subtitle">Gerador de Sitemaps Empresarial</span>
                    </span>
                </a>
            </div>

            <div class="content">
                <h1>{{ $titulo }}</h1>

                @if($mensagem)
                    <p>{{ $mensagem }}</p>
                @endif

                @if(!empty($linhas))
                    <ul class="list">
                        @foreach($linhas as $linha)
                            <li>{{ $linha }}</li>
                        @endforeach
                    </ul>
                @endif

                @if($acaoTexto && $acaoUrl)
                    <div class="button-wrap">
                        <a href="{{ $acaoUrl }}" class="button">{{ $acaoTexto }}</a>
                    </div>
                @endif
            </div>

            <div class="footer">
                {{ $rodape ?? 'Este e-mail foi enviado pelo GenMap porque sua conta ou um projeto associado gerou uma atualizacao importante.' }}
                <br>
                <a href="{{ route('preferences.edit') }}">Gerenciar preferencias de notificacao</a>
            </div>
        </div>
    </div>
</body>
</html>
