# Guia de Melhorias — HomeMadeGourmet (Portal-Receitas)

> Este guia foi feito depois de eu revisar o repositório de verdade — não é
> uma lista genérica de "boas práticas". Vários itens que uma auditoria
> costuma cobrar (Política de Privacidade, exclusão de conta, CSP, CSRF,
> rate limiting, CI) **já estão implementados** no projeto. Este guia foca
> no que realmente falta, na ordem em que vale a pena fazer.

---

## 0. O que já está pronto (não repita)

Confirmado lendo o código, não só a documentação:

| Item | Onde está |
|---|---|
| Política de Privacidade + Termos de Uso, linkados no rodapé e no cadastro | `privacy.php`, `terms.php` |
| Exclusão de conta pelo titular | `DeleteUserAccountUseCase`, `profile.php` (`.danger-zone`) |
| CSP, X-Frame-Options, HSTS, Referrer-Policy, Permissions-Policy | `docker/security-headers.conf` |
| Token CSRF nos formulários de auth/perfil | `AuthController`, `ProfileController`, `SessionManager` |
| Rate limiting de login | `FileRateLimiter`, `RateLimiterInterface`, `AuthController` |
| CI com lint PHP/JS + PHPUnit + build das imagens Docker | `.github/workflows/ci.yml` |
| Seeds com e-mails RFC 2606 (`demo1@example.com`) | `DB_Receitas.sql` |
| Embeds forçados para `youtube-nocookie.com` + lazy load | `index.php` |
| Procedure de expurgo da trilha de auditoria (12 meses) | `DB_Receitas.sql` (`sp_expurgar_auditoria`) |
| Registro de tratamento, LIA e runbook de incidentes | `docs/privacidade/` |
| Header/footer globais, consistentes em todas as páginas, com JS de scroll-state e fechamento acessível do menu | `partials/header.php`, `partials/footer.php`, `assets/js/header.js` |

## Já implementado nesta sessão (código incluído no patch anexo)

Três itens abaixo estavam listados como pendentes na auditoria e eu já os
resolvi — estão no patch `melhorias-portal-receitas.patch` que acompanha
este guia:

1. **Cache-Control para assets estáticos** — `docker/security-headers.conf`
   ganhou regras de cache (1h para CSS/JS, 30 dias para imagens/fontes),
   usando só `mod_headers` (já habilitado), sem tocar nos Dockerfiles.
2. **`composer audit` no CI** — nova etapa em `.github/workflows/ci.yml`
   que falha o build se alguma dependência tiver vulnerabilidade conhecida.
3. **Dados estruturados `Recipe` (JSON-LD)** — `index.php` agora emite um
   `ItemList` de `Recipe` com os campos que já são renderizados no card
   (nome, imagem, categoria). Só usei o que já é visível na página, de
   propósito — o conteúdo do modal (ingredientes/preparo) só existe no DOM
   depois do clique, e descrevê-lo como visível no JSON-LD seria
   "cloaking" aos olhos do Google.

---

## 1. Prioridade alta (próximas 1–2 semanas)

### 1.1 Agendar de fato o expurgo da auditoria

A procedure `sp_expurgar_auditoria(365)` existe, mas hoje só roda se
alguém a chamar manualmente. Falta o agendamento.

**Como fazer** (MySQL 8 — habilite o *event scheduler* uma vez):

```sql
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS ev_expurgo_auditoria
ON SCHEDULE EVERY 1 MONTH
STARTS (CURRENT_DATE + INTERVAL 1 DAY)
DO
    CALL sp_expurgar_auditoria(365);
```

Se o provedor não permitir `event_scheduler` (comum em planos free), use
um cron job simples fora do banco:

```bash
# crontab do servidor de aplicação — dia 1 de cada mês, 03h
0 3 1 * * mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  -e "CALL sp_expurgar_auditoria(365);"
```

**Critério de pronto:** rodar `SHOW EVENTS;` (ou checar o log do cron) e
confirmar execução no mês seguinte.

### 1.2 Acessibilidade automatizada no CI (axe-core)

O projeto já segue WCAG 2.2 AA na prática (landmarks, foco gerenciado,
`prefers-reduced-motion`), mas nada impede uma regressão futura.

**Como fazer:**

```bash
npm install --save-dev @axe-core/cli
```

Nova etapa no `.github/workflows/ci.yml` (depois do build/subida do
container em modo de teste):

```yaml
      - name: Acessibilidade (axe-core)
        run: |
          npx @axe-core/cli http://localhost:8080/ \
            --exit --tags wcag2a,wcag2aa
```

Isso exige subir a aplicação no job de CI antes de rodar o axe (via
`docker compose up -d` com o `docker-compose.yml` já existente). Vale a
pena rodar em pelo menos 3 URLs: `index.php`, `login.php`, `termos.php`.

**Critério de pronto:** o job falha se qualquer página introduzir uma
violação WCAG 2.2 A/AA nova.

---

## 2. Prioridade média (próximo mês)

### 2.1 Cache de catálogo (APCu)

`§7.2` da auditoria já sinalizava isso: sem cache de aplicação, toda
requisição em `index.php` sem filtro bate no banco pra montar a lista
completa de receitas — que muda pouco.

**Como fazer**, sem tocar no contrato do `RecipeRepositoryInterface`:
crie um decorator que envolve o repositório real.

```php
// src/Infrastructure/Cache/CachedRecipeRepository.php
namespace App\Infrastructure\Cache;

use App\Domain\Repository\RecipeRepositoryInterface;

final class CachedRecipeRepository implements RecipeRepositoryInterface
{
    private const TTL = 300; // 5 min — catálogo muda pouco, mas não é estático

    public function __construct(
        private readonly RecipeRepositoryInterface $inner,
    ) {
    }

    public function findAll(): array
    {
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            return $this->inner->findAll();
        }

        $key = 'receitas:catalogo:v1';
        $cached = apcu_fetch($key, $ok);
        if ($ok) {
            return $cached;
        }

        $dados = $this->inner->findAll();
        apcu_store($key, $dados, self::TTL);

        return $dados;
    }

    // Métodos de busca/filtro delegam direto — só o catálogo completo
    // (a rota mais quente, sem parâmetros) vale cachear por hoje.
}
```

Ligue isso no container de DI (onde quer que `RecipeRepositoryInterface`
seja resolvida hoje), envolvendo a implementação MySQL concreta.

**Cuidado:** ao editar/cadastrar receita (se algum dia existir essa
funcionalidade), chame `apcu_delete('receitas:catalogo:v1')` para não
servir dado desatualizado.

**Critério de pronto:** medir tempo de resposta de `index.php` sem filtro,
antes/depois, com uma ferramenta simples (`curl -w "%{time_total}\n"`).

### 2.2 Testes de carga (k6)

Meta já definida na auditoria: **p95 < 300 ms**. Falta o teste em si.

```bash
npm install -g k6   # ou baixe o binário — não precisa de npm
```

```js
// tests/load/catalogo.k6.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 20,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<300'],
    },
};

export default function () {
    const res = http.get(`${__ENV.BASE_URL || 'http://localhost:8080'}/index.php`);
    check(res, { 'status 200': (r) => r.status === 200 });
    sleep(1);
}
```

Rodar: `k6 run tests/load/catalogo.k6.js`.

**Critério de pronto:** relatório do k6 mostrando `p(95)` abaixo de 300 ms
com 20 usuários virtuais simultâneos.

### 2.3 Backups testados (ambientes persistentes — VPS/compose, não o free)

O free tier é efêmero por design (já documentado), mas se o projeto
migrar pra um ambiente persistente, faltará isso.

```bash
#!/bin/sh
# docker/backup-mysql.sh — rodar via cron diário
set -e
DATA="$(date +%Y%m%d_%H%M%S)"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    | gzip > "/backups/portal-receitas_${DATA}.sql.gz"
find /backups -name "*.sql.gz" -mtime +30 -delete   # retenção de 30 dias
```

**Teste de restauração** (fazer isso pelo menos uma vez, documentado):

```bash
gunzip -c /backups/portal-receitas_20260101_030000.sql.gz \
    | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" portal_receitas_restore_test
```

**Critério de pronto:** um dump recente restaurado com sucesso num banco
de teste, com contagem de linhas batendo (`SELECT COUNT(*) FROM receita;`).

---

## 3. Prioridade contínua (sem prazo fixo, valem a pena com calma)

### 3.1 Entidades tipadas no Domain

Hoje o `Domain` tem interfaces de repositório e exceções, mas os dados
trafegam como array associativo (`$recipe['name']`, `$user['emailUsuario']`
etc.). Funciona, mas não há verificação estática nem autocomplete.

**Passo pequeno e seguro** — comece por uma entidade, sem tocar nos
repositórios ainda:

```php
// src/Domain/Entity/Recipe.php
namespace App\Domain\Entity;

final class Recipe
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $image,
        public readonly string $time,
        public readonly string $category,
    ) {
    }

    public static function fromArray(array $dados): self
    {
        return new self(
            id: (int) $dados['id'],
            name: (string) $dados['name'],
            image: (string) $dados['image'],
            time: (string) $dados['time'],
            category: (string) $dados['category'],
        );
    }
}
```

Migre view por view (`index.php` primeiro), trocando `$recipe['name']`
por `$recipe->name` conforme for confortável — não precisa ser tudo de
uma vez.

### 3.2 Sessões em Redis (só se for escalar horizontalmente)

Sessões em arquivo (`session.save_handler = files`, padrão do PHP) só
viram problema se a aplicação rodar em **mais de uma instância** atrás de
um load balancer sem sticky sessions. Não implemente isso antes de
precisar — é complexidade real por um problema que talvez nunca apareça
num TCC. Quando precisar: `predis/predis` + `session_set_save_handler`
customizado, ou o handler nativo do Redis (`redis.so` + `session.save_handler = redis`).

### 3.3 Branch protection na `main`

No GitHub: **Settings → Branches → Branch protection rules** → exigir PR
+ checks do CI verdes antes de merge. Cinco minutos, zero código, reduz
o risco de regressão por commit direto na `main`.

### 3.4 Testes de contrato (OpenAPI)

Se a API (`public/api/`) crescer para ser consumida por outro cliente
além do próprio frontend, vale documentar com OpenAPI (`openapi.yaml`) e
validar a resposta real contra o schema no CI (`schemathesis` ou
`dredd`). Antes disso, é esforço sem retorno — a API hoje só serve o
próprio frontend.

---

## Como aplicar as três melhorias já prontas

O patch `melhorias-portal-receitas.patch` (anexado) contém:

- `docker/security-headers.conf` — Cache-Control de assets
- `.github/workflows/ci.yml` — `composer audit`
- `src/Presentation/View/index.php` — JSON-LD de receitas
- as refatorações de header/footer feitas nas sessões anteriores
  (`partials/header.php`, `partials/footer.php`, `assets/css/layout.css`,
  `assets/js/header.js`, remoção do CSS duplicado em `pages/home.css`)

No seu clone local do projeto:

```bash
git apply --check melhorias-portal-receitas.patch   # valida antes de aplicar
git apply melhorias-portal-receitas.patch
```

Se `--check` reclamar de conflito (por exemplo, se você já tiver editado
algum desses arquivos), abra o patch e aplique manualmente os trechos
relevantes — ele é só texto unificado (`diff -u`).
