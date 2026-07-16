<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex">
        <title>HomeMadeGourmet — Serviço indisponível</title>
        <!-- Página autocontida: sem dependências externas, funciona mesmo com o banco fora -->
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
                background: #FAF7F2;
                background-image: radial-gradient(60rem 40rem at 85% -10%, #FFE4C7 0%, transparent 60%),
                                  linear-gradient(180deg, #FAF7F2 0%, #F6EFE6 100%);
                color: #26160A;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            @media (prefers-color-scheme: dark) {
                body {
                    background: #17110C;
                    background-image: radial-gradient(60rem 40rem at 85% -10%, rgba(194,65,12,.22) 0%, transparent 60%),
                                      linear-gradient(180deg, #17110C 0%, #100B07 100%);
                    color: #F5EDE4;
                }
                .box { background: rgba(43,31,21,.8) !important; border-color: rgba(255,235,214,.16) !important; }
                p { color: #CBB8A6 !important; }
            }
            .box {
                text-align: center;
                padding: 3rem 2.5rem;
                max-width: 26rem;
                background: rgba(255,255,255,.78);
                border: 1px solid rgba(255,255,255,.65);
                border-radius: 1.5rem;
                box-shadow: 0 24px 56px rgba(38,22,10,.18);
                backdrop-filter: saturate(160%) blur(18px);
            }
            .icone { font-size: 2.75rem; margin-bottom: .75rem; }
            h1 { font-size: 1.5rem; letter-spacing: -.015em; margin-bottom: .5rem; }
            p { color: #5C4A3A; line-height: 1.55; }
        </style>
    </head>
    <body>
        <main class="box" role="main">
            <div class="icone" aria-hidden="true">🍳</div>
            <h1>HomeMadeGourmet</h1>
            <p>O serviço está temporariamente indisponível.<br>Tente novamente em alguns instantes.</p>
        </main>
    </body>
</html>
