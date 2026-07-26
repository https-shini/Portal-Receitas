-- ─────────────────────────────────────────────────────────────
-- 20260101000000_init_schema.sql
-- Schema do Portal de Receitas em PostgreSQL (Supabase).
-- Ver docs/migracao-react-supabase-n8n.md §6.5.
-- ─────────────────────────────────────────────────────────────

-- Perfil público (1:1 com auth.users). Não guarda senha/e-mail sensível.
create table if not exists public.users (
  id           uuid primary key references auth.users (id) on delete cascade,
  display_name text not null check (char_length(display_name) between 2 and 60),
  avatar_path  text,
  bio          text check (char_length(bio) <= 280),
  created_at   timestamptz not null default now()
);

create table if not exists public.categories (
  id   bigint generated always as identity primary key,
  name text not null unique,
  slug text not null unique
);

do $$ begin
  create type recipe_difficulty as enum ('facil', 'medio', 'dificil');
exception when duplicate_object then null; end $$;

do $$ begin
  create type recipe_status as enum ('draft', 'published');
exception when duplicate_object then null; end $$;

create table if not exists public.recipes (
  id           bigint generated always as identity primary key,
  author_id    uuid not null references public.users (id) on delete cascade,
  category_id  bigint references public.categories (id) on delete set null,
  title        text not null check (char_length(title) between 3 and 140),
  slug         text not null unique,
  description  text,
  image_path   text,
  prep_minutes int check (prep_minutes >= 0),
  difficulty   recipe_difficulty not null default 'medio',
  status       recipe_status     not null default 'draft',
  tags         jsonb not null default '[]',
  search_doc   tsvector,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now()
);

create table if not exists public.ingredients (
  id   bigint generated always as identity primary key,
  name text not null unique
);

create table if not exists public.recipe_ingredients (
  recipe_id     bigint not null references public.recipes (id) on delete cascade,
  ingredient_id bigint not null references public.ingredients (id) on delete restrict,
  quantity      text,
  unit          text,
  primary key (recipe_id, ingredient_id)
);

create table if not exists public.ratings (
  id         bigint generated always as identity primary key,
  user_id    uuid   not null references public.users (id) on delete cascade,
  recipe_id  bigint not null references public.recipes (id) on delete cascade,
  score      smallint not null check (score between 1 and 5),
  created_at timestamptz not null default now(),
  unique (user_id, recipe_id)
);

create table if not exists public.comments (
  id         bigint generated always as identity primary key,
  author_id  uuid   not null references public.users (id) on delete cascade,
  recipe_id  bigint not null references public.recipes (id) on delete cascade,
  parent_id  bigint references public.comments (id) on delete cascade,
  body       text not null check (char_length(body) between 1 and 2000),
  created_at timestamptz not null default now()
);

create table if not exists public.favorites (
  user_id    uuid   not null references public.users (id) on delete cascade,
  recipe_id  bigint not null references public.recipes (id) on delete cascade,
  created_at timestamptz not null default now(),
  primary key (user_id, recipe_id)
);

create table if not exists public.follows (
  follower_id uuid not null references public.users (id) on delete cascade,
  followee_id uuid not null references public.users (id) on delete cascade,
  created_at  timestamptz not null default now(),
  primary key (follower_id, followee_id),
  check (follower_id <> followee_id)
);

create table if not exists public.notifications (
  id         bigint generated always as identity primary key,
  user_id    uuid not null references public.users (id) on delete cascade,
  type       text not null,
  payload    jsonb not null default '{}',
  read       boolean not null default false,
  created_at timestamptz not null default now()
);

create table if not exists public.reports (
  id          bigint generated always as identity primary key,
  reporter_id uuid not null references public.users (id) on delete cascade,
  target_type text not null check (target_type in ('recipe','comment')),
  target_id   bigint not null,
  reason      text not null,
  status      text not null default 'open' check (status in ('open','reviewing','closed')),
  created_at  timestamptz not null default now()
);

-- ── Índices ──────────────────────────────────────────────────
create index if not exists idx_recipes_author   on public.recipes (author_id);
create index if not exists idx_recipes_category on public.recipes (category_id);
create index if not exists idx_recipes_published on public.recipes (created_at desc) where status = 'published';
create index if not exists idx_recipes_search on public.recipes using gin (search_doc);
create index if not exists idx_comments_recipe on public.comments (recipe_id, created_at);
create index if not exists idx_ratings_recipe  on public.ratings (recipe_id);
create index if not exists idx_follows_followee on public.follows (followee_id);
create index if not exists idx_notif_user_unread on public.notifications (user_id) where read = false;

-- ── Busca (tsvector) via trigger ─────────────────────────────
create or replace function public.recipes_tsvector_update() returns trigger
language plpgsql as $$
begin
  new.search_doc :=
    setweight(to_tsvector('portuguese', coalesce(new.title,'')), 'A') ||
    setweight(to_tsvector('portuguese', coalesce(new.description,'')), 'B');
  new.updated_at := now();
  return new;
end $$;

drop trigger if exists trg_recipes_tsvector on public.recipes;
create trigger trg_recipes_tsvector
  before insert or update of title, description on public.recipes
  for each row execute function public.recipes_tsvector_update();

-- ── Agregado de avaliações (usado pela UI) ───────────────────
create or replace function public.recipe_rating_stats(p_recipe_id bigint)
returns table (average numeric, total bigint)
language sql stable as $$
  select coalesce(round(avg(score), 2), 0)::numeric, count(*)::bigint
  from public.ratings where recipe_id = p_recipe_id;
$$;
