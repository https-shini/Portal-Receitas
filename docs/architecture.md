# Arquitetura Clean adotada

## Camadas

- **Domain**: contratos e exceções de negócio (`src/Domain`).
- **Application**: casos de uso de autenticação, cadastro, perfil e receitas (`src/Application`).
- **Infrastructure**: acesso a dados via PDO e repositórios (`src/Infrastructure`).
- **Presentation**: controllers HTTP, sessão e views (`src/Presentation`).

## Estrutura principal

- `public/`: único docroot — pontos de entrada HTTP e assets estáticos (`public/assets/`).
- `public/api/`: endpoints JSON de autenticação (`register`, `login`, `logout`, `me`) consumidos via `fetch` pela tela de acesso (padrão de referência: AuthService).
- `config/bootstrap.php`: composição de dependências (lê `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` do ambiente).
- `src/`: núcleo arquitetural por camada.
- `tests/`: testes PHPUnit dos casos de uso e da conexão.

## Regra de dependência

A direção de dependência segue:

`Presentation -> Application -> Domain`

`Infrastructure` implementa contratos do `Domain` e é instanciada no bootstrap.
