<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link de ativação para sua conta {{ $appName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #9ac2d9;
            /* Azul claro exato da imagem */
            padding: 20px;
            text-align: center;
        }

        .header img {
            max-height: 40px;
        }

        .header h1 {
            color: #c84033;
            /* Vermelho do logo PRO da imagem */
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            font-family: Arial, sans-serif;
            letter-spacing: -0.5px;
        }

        .header h1 span {
            color: #3b3b3b;
            font-weight: 700;
        }

        .content {
            padding: 40px 30px;
            background-color: #f9f9f9;
            /* Fundo cinza claro igual imagem */
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .text {
            font-size: 14px;
            color: #444;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .password-box {
            background-color: #fff;
            padding: 10px 15px;
            border-left: 4px solid #c0392b;
            margin: 15px 0;
            font-size: 14px;
        }

        .btn-container {
            margin: 30px 0;
        }

        .btn {
            display: inline-block;
            background-color: #00aced;
            /* Azul do botão */
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            font-weight: bold;
            border-radius: 3px;
            font-size: 14px;
            text-transform: uppercase;
            text-align: center;
        }

        .link-text {
            font-size: 11px;
            color: #00aced;
            word-break: break-all;
            margin-top: 15px;
        }

        .footer {
            padding: 20px 30px;
            background-color: #f9f9f9;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
        }

        .footer a {
            color: #00aced;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Topo Azul -->
        <div class="header" style="border-top-left-radius: 4px; border-top-right-radius: 4px; padding: 25px 20px;">
            <!-- Nome da Aplicação Dinâmico -->
            <h1 style="margin: 0; color: #3b3b3b;">{{ $appName }}</h1>
        </div>

        <!-- Corpo -->
        <div class="content">
            <div class="title">{{ $appName }}</div>

            <div class="text" style="font-weight: bold;">
                Sua conta foi criada.
            </div>

            <div class="text">
                Olá{{ $user->name ? ' ' . $user->name : '' }},
            </div>

            <div class="text">
                Por favor, clique no link abaixo para confirmar seu endereço de e-mail e ativar sua conta.
            </div>

            <div class="btn-container">
                <a href="{{ $activationUrl }}" class="btn">Ative Sua Conta</a>
            </div>

            <div class="link-text">
                <a href="{{ $activationUrl }}" style="color: #00aced;">{{ $activationUrl }}</a>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="footer">
            O endereço de e-mail da sua conta é: <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
        </div>
    </div>
</body>

</html>
