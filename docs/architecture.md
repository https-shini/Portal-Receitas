# Arquitetura Clean adotada

> Este é o resumo executivo. A referência técnica completa do backend
> (requisitos, diagramas, API, segurança, observabilidade, escalabilidade,
> testes, ADRs e roadmap) está em [backend.md](backend.md).

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
- `DB_Receitas.sql`: banco de dados oficial — script único e idempotente com DDL (constraints nomeadas, CHECKs, FKs), índices (incl. FULLTEXT), views, functions, procedures, triggers de auditoria, seed, controle de acesso (roles/menor privilégio), transações e autotestes; compatível com MySQL 8 e MariaDB.

## Regra de dependência

A direção de dependência segue:

`Presentation -> Application -> Domain`

`Infrastructure` implementa contratos do `Domain` e é instanciada no bootstrap.
