<img width=100% src="https://capsule-render.vercel.app/api?type=waving&color=gradient&height=120&section=header" alt="Header Wave"/>

<div align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Orbitron&weight=700&size=32&duration=3000&pause=1000&color=FF6B6B&center=true&vCenter=true&width=900&lines=Sua+Jornada+Culinária+Personalizada;Descubra+Receitas+e+Crie+Sua+História;Homemade+Gourmet" alt="Título Dinâmico" />
</div>

<div align="center">

[![Status](https://img.shields.io/badge/Status-Concluído-success?style=for-the-badge)](https://github.com)
[![License](https://img.shields.io/badge/Licença-MIT-yellow?style=for-the-badge)](LICENSE)
[![Year](https://img.shields.io/badge/Ano-2022-blue?style=for-the-badge)](https://github.com)

</div>

---

## 🍳 Sobre o Projeto

**Homemade Gourmet** é um portal de receitas desenvolvido como TCC (ETEC de Vila Formosa, 2022) e posteriormente consolidado com Clean Architecture, deploy via Docker e segurança de senhas com bcrypt.

O que o site faz hoje:

- **Catálogo de receitas** com foto, tempo de preparo, porções, calorias e categoria.
- **Detalhe de cada receita** com vídeo do YouTube incorporado, lista de ingredientes e modo de preparo passo a passo.
- **Busca por ingrediente** e **filtro por categoria** (Frutos do Mar, Massas, Veganas, Salgados, Doces, Carnes).
- **Cadastro e login de usuários** com senha protegida por `password_hash`/`password_verify` (bcrypt).
- **Perfil do usuário** com edição de nome, e-mail e senha.
- **Logout** com encerramento de sessão.

## 🗺️ Roadmap (não implementado)

Funcionalidades idealizadas na concepção original do TCC que **ainda não existem** no código e ficam como evolução futura:

- Engine de recomendação inteligente por preferências.
- Calculadora de calorias em tempo real por porção.
- Sistema de favoritos e avaliações de receitas.
- Receitas com contexto histórico e comunidade de usuários.

---

## 🛠️ Tecnologias Utilizadas

<div align="center">

| Camada | Tecnologia |
|--------|-----------|
| **Interface** | HTML5, CSS3, JavaScript |
| **Backend** | PHP 8.2 (Clean Architecture, PSR-4, Composer) |
| **Dados** | MySQL 8 (PDO + prepared statements) |
| **Deploy** | Docker + Docker Compose (php:8.2-apache) |
| **Testes** | PHPUnit |

</div>

---

## 📦 Como Rodar

### 🐳 Com Docker (recomendado)

```bash
git clone https://github.com/https-shini/Portal-Receitas.git
cd Portal-Receitas
cp .env.example .env          # opcional: ajuste WEB_PORT e DB_PASS
docker compose up --build -d
```

Acesse **http://localhost:8080**. O seed (`DB_Receitas.sql`) é importado automaticamente na primeira subida. Detalhes em [DEPLOY.md](DEPLOY.md).

**Usuários de demonstração** (do seed):

| E-mail | Senha |
|--------|-------|
| `kk.123@gmail.com` | `123456` |
| `tectutors.123@gmail.com` | `271821` |

### 💻 Com XAMPP (desenvolvimento local)

1. Clone o projeto e rode `composer install`.
2. Importe `DB_Receitas.sql` no phpMyAdmin (cria o banco `tcc_receitas`).
3. Aponte o docroot do Apache para a pasta `public/` do projeto.
4. Sem variáveis de ambiente, a conexão usa os padrões `localhost` / `root` / senha vazia / banco `tcc_receitas`.

A configuração do banco é feita por variáveis de ambiente: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.

---

## 📁 Estrutura do Projeto

```
Portal-Receitas/
├─ public/                        ← Único docroot (entrypoints HTTP)
│  ├─ index.php                   ← Home / listagem e busca de receitas
│  ├─ login.php                   ← Autenticação
│  ├─ register.php                ← Cadastro
│  ├─ profile.php                 ← Perfil do usuário
│  └─ assets/                     ← CSS, JS, imagens e endpoints auxiliares
├─ src/
│  ├─ Domain/                     ← Contratos de repositório e exceções de negócio
│  ├─ Application/                ← Casos de uso (registro, login, perfil, receitas)
│  ├─ Infrastructure/             ← PDO, repositórios MySQL
│  └─ Presentation/               ← Controllers, sessão e views
├─ config/bootstrap.php           ← Composição de dependências
├─ tests/                         ← Testes PHPUnit
├─ DB_Receitas.sql                ← Seed do banco (schema + dados)
├─ Dockerfile / docker-compose.yml
├─ DEPLOY.md                      ← Guia de deploy
└─ CHANGELOG.md                   ← Histórico da consolidação
```

Mais detalhes da arquitetura em [docs/architecture.md](docs/architecture.md).

---

## 🧪 Testes

```bash
composer install
composer test
```

Os testes cobrem os casos de uso de cadastro (hash bcrypt, e-mail duplicado, validação), autenticação (credenciais corretas/erradas, hash do seed) e edição de perfil, além do fluxo de conexão PDO (o teste de integração roda quando `TEST_DB_HOST` está definido).

---

## 👥 Desenvolvedores do Projeto

### Instituição Acadêmica

- **Escola:** ETEC de Vila Formosa
- **Curso:** Técnico em Desenvolvimento de Sistemas
- **Programa:** Integrado ao NOVOTEC
- **Ano:** 2022

### Equipe de Desenvolvimento

<div align="center">

| Nome | Função |
|------|--------|
| Cassiano Reis de Jesus | Desenvolvedor |
| Guilherme de Souza Cruz | Desenvolvedor |
| Henrriky Jhonny de Oliveira | Desenvolvedor |
| João Vitor Santos de Matos | Desenvolvedor |
| Nicolas de Abreu Alves | Desenvolvedor |
| Rodrigo Mazucato Lopes de Souza | Desenvolvedor |
| Sabrina Maia Quirino | Desenvolvedora |

**Orientadores:** Prof. Márcio Bergamin e Prof. Sérgio Muniz

</div>

---

## 🐛 Troubleshooting

- **Conexão ao banco recusada (Docker):** aguarde o healthcheck do serviço `db` ficar `healthy` (`docker compose ps`); o `web` só inicia depois dele.
- **Conexão ao banco recusada (XAMPP):** confira se o MySQL está ativo e se o banco `tcc_receitas` foi importado. Os padrões de conexão são `localhost`/`root`/senha vazia; para outros valores, defina `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- **Seed não reimporta:** o MySQL só importa `DB_Receitas.sql` com o volume vazio. Rode `docker compose down -v` e suba de novo.
- **Página em branco:** verifique os logs (`docker compose logs -f web` ou `xampp/apache/logs/error.log`).

---

<img width=100% src="https://capsule-render.vercel.app/api?type=waving&color=gradient&height=120&section=footer" alt="Footer Wave"/>
