# Portal de Receitas — Frontend (React + Supabase)

Implementação da migração descrita em
[`../docs/migracao-react-supabase-n8n.md`](../docs/migracao-react-supabase-n8n.md).
Este diretório é **aditivo**: convive com o app PHP legado (raiz do repositório)
durante a migração (*strangler fig*).

## Stack

React 18 · TypeScript · Vite · TanStack Query v5 · React Router v6 ·
`@supabase/supabase-js` v2.

## O que já está implementado (fundação)

- **Banco** (`../supabase/migrations`): schema completo, índices, RLS em todas as
  tabelas, triggers (perfil no cadastro, `tsvector`, rate limit), RPC de média.
- **Auth**: cadastro/login e-mail+senha, OAuth Google/GitHub, guard de rota.
- **Catálogo**: listagem pública com busca (full-text) e infinite scroll.
- **Detalhe da receita**: ingredientes, média de avaliações, favoritar (otimista),
  comentários **em tempo real** (Realtime).
- **Publicação**: upload de foto com compressão WebP + criação da receita.
- **Perfil**: receitas por autor.
- **Edge Function** (`../supabase/functions/generate-tags`): tags via IA.

## Pré-requisitos

- Node ≥ 20, npm.
- Um projeto no [Supabase](https://supabase.com) (free tier serve).
- (Opcional) [Supabase CLI](https://supabase.com/docs/guides/cli) para aplicar as
  migrations e rodar localmente.

## Configuração

```bash
cd web
cp .env.example .env.local        # preencha VITE_SUPABASE_URL e VITE_SUPABASE_ANON_KEY
npm install
```

Aplique o schema no seu projeto Supabase (uma das opções):

```bash
# via CLI (recomendado)
supabase link --project-ref <SEU_REF>
supabase db push
psql "$SUPABASE_DB_URL" -f supabase/seed.sql   # dados de referência
# ou: cole o conteúdo de supabase/migrations/*.sql no SQL Editor do dashboard
```

Crie os buckets de Storage `recipe-images` e `avatars` (públicos para leitura) no
dashboard, e habilite os provedores OAuth desejados em Authentication → Providers.

## Rodar

```bash
npm run dev          # http://localhost:5173
npm run typecheck    # checagem de tipos
npm run build        # build de produção (dist/)
```

## Deploy (Vercel)

Importe o repositório na Vercel apontando **Root Directory = `web`**. Framework
Vite é detectado. Configure as variáveis `VITE_SUPABASE_URL` e
`VITE_SUPABASE_ANON_KEY`. O `vercel.json` já faz o fallback SPA.

## Próximas etapas (roadmap §6.21)

Seguidores, notificações (sino), moderação de denúncias, avaliações por estrela
interativas, workflows n8n (onboarding, moderação, distribuição) e monitoramento
(Sentry). Ver o checklist completo em §6.22 do guia.
