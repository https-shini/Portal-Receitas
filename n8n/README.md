# Workflows n8n — Portal de Receitas

Exportações dos 3 fluxos descritos em
[`../docs/migracao-react-supabase-n8n.md`](../docs/migracao-react-supabase-n8n.md) §6.14.
Importe cada `.json` em **n8n → Workflows → Import from File**.

| Arquivo | Gatilho | O que faz |
|---|---|---|
| `onboarding.json` | Webhook `user.created` | E-mail de boas-vindas → CRM → Discord → Analytics |
| `moderation.json` | Webhook `reports.insert` | Classifica (IA) → ação em casos graves → Slack → Trello |
| `recipe-distribution.json` | Webhook `recipe.published` | Notifica seguidores → Telegram → SEO |

## Conectando ao Supabase

Crie no n8n uma **credential HTTP Header Auth** com
`apikey: <SERVICE_ROLE_KEY>` e `Authorization: Bearer <SERVICE_ROLE_KEY>`,
e aponte os nós HTTP para `https://<ref>.supabase.co/rest/v1/...`.

## Disparando pelo banco (Database Webhooks)

No dashboard Supabase → Database → Webhooks, crie um webhook por tabela
(`auth.users`, `reports`, `recipes`) apontando para a URL de produção do n8n
(`https://<n8n>/webhook/<path>`). Alternativa: `pg_net` + trigger SQL.

> Os JSON aqui são **esqueletos funcionais**: os nós externos (SendGrid, Discord,
> Slack, Trello, Telegram) exigem que você configure as respectivas credentials
> no seu n8n. Ajuste URLs/campos conforme suas contas.
