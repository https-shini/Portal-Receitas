-- ─────────────────────────────────────────────────────────────
-- 20260101000100_auth_and_rls.sql
-- Criação de perfil no cadastro, rate limit e Row Level Security.
-- Ver docs/migracao-react-supabase-n8n.md §6.7 e §6.11.
-- ─────────────────────────────────────────────────────────────

-- Cria o perfil público automaticamente ao criar um auth.users.
create or replace function public.handle_new_user() returns trigger
language plpgsql security definer set search_path = public as $$
begin
  insert into public.users (id, display_name)
  values (new.id, coalesce(new.raw_user_meta_data->>'display_name', 'Chef'))
  on conflict (id) do nothing;
  return new;
end $$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute function public.handle_new_user();

-- Rate limit de comentários: máx. 5/min por autor.
create or replace function public.rate_limit_comments() returns trigger
language plpgsql as $$
declare recent int;
begin
  select count(*) into recent from public.comments
   where author_id = new.author_id and created_at > now() - interval '1 minute';
  if recent >= 5 then
    raise exception 'rate_limit: muitos comentários, aguarde um instante';
  end if;
  return new;
end $$;

drop trigger if exists trg_rl_comments on public.comments;
create trigger trg_rl_comments before insert on public.comments
  for each row execute function public.rate_limit_comments();

-- ── Habilita RLS ─────────────────────────────────────────────
alter table public.users              enable row level security;
alter table public.recipes            enable row level security;
alter table public.recipe_ingredients enable row level security;
alter table public.ingredients        enable row level security;
alter table public.categories         enable row level security;
alter table public.comments           enable row level security;
alter table public.favorites          enable row level security;
alter table public.ratings            enable row level security;
alter table public.follows            enable row level security;
alter table public.notifications      enable row level security;
alter table public.reports            enable row level security;

-- USERS: perfil público para leitura; só o dono edita.
drop policy if exists "users: leitura pública" on public.users;
create policy "users: leitura pública" on public.users for select using (true);
drop policy if exists "users: dono edita" on public.users;
create policy "users: dono edita"      on public.users for update using (id = auth.uid()) with check (id = auth.uid());

-- CATEGORIES / INGREDIENTS: leitura livre; escrita só autenticado.
drop policy if exists "categories: leitura" on public.categories;
create policy "categories: leitura"   on public.categories for select using (true);
drop policy if exists "ingredients: leitura" on public.ingredients;
create policy "ingredients: leitura"  on public.ingredients for select using (true);
drop policy if exists "ingredients: inserir" on public.ingredients;
create policy "ingredients: inserir"  on public.ingredients for insert to authenticated with check (true);

-- RECIPES: leitura pública de publicadas ou próprias; dono gerencia.
drop policy if exists "recipes: ler publicadas ou próprias" on public.recipes;
create policy "recipes: ler publicadas ou próprias" on public.recipes for select
  using (status = 'published' or author_id = auth.uid());
drop policy if exists "recipes: autor insere" on public.recipes;
create policy "recipes: autor insere"  on public.recipes for insert to authenticated with check (author_id = auth.uid());
drop policy if exists "recipes: autor edita" on public.recipes;
create policy "recipes: autor edita"   on public.recipes for update using (author_id = auth.uid()) with check (author_id = auth.uid());
drop policy if exists "recipes: autor apaga" on public.recipes;
create policy "recipes: autor apaga"   on public.recipes for delete using (author_id = auth.uid());

-- RECIPE_INGREDIENTS: leitura livre; só o autor da receita escreve.
drop policy if exists "ri: leitura" on public.recipe_ingredients;
create policy "ri: leitura" on public.recipe_ingredients for select using (true);
drop policy if exists "ri: autor da receita escreve" on public.recipe_ingredients;
create policy "ri: autor da receita escreve" on public.recipe_ingredients for all
  using (exists (select 1 from public.recipes r where r.id = recipe_id and r.author_id = auth.uid()))
  with check (exists (select 1 from public.recipes r where r.id = recipe_id and r.author_id = auth.uid()));

-- COMMENTS: leitura autenticada; autor gerencia o próprio.
drop policy if exists "comments: leitura autenticada" on public.comments;
create policy "comments: leitura autenticada" on public.comments for select to authenticated using (true);
drop policy if exists "comments: inserir como si" on public.comments;
create policy "comments: inserir como si"     on public.comments for insert to authenticated with check (author_id = auth.uid());
drop policy if exists "comments: apagar o próprio" on public.comments;
create policy "comments: apagar o próprio"    on public.comments for delete using (author_id = auth.uid());

-- FAVORITES / RATINGS: cada um só mexe nos seus.
drop policy if exists "favorites: dono" on public.favorites;
create policy "favorites: dono" on public.favorites for all using (user_id = auth.uid()) with check (user_id = auth.uid());
drop policy if exists "ratings: dono" on public.ratings;
create policy "ratings: dono"   on public.ratings   for all using (user_id = auth.uid()) with check (user_id = auth.uid());
drop policy if exists "ratings: leitura" on public.ratings;
create policy "ratings: leitura" on public.ratings  for select using (true);

-- FOLLOWS: leitura pública; só o próprio seguidor cria/apaga.
drop policy if exists "follows: leitura" on public.follows;
create policy "follows: leitura" on public.follows for select using (true);
drop policy if exists "follows: seguir" on public.follows;
create policy "follows: seguir"  on public.follows for insert to authenticated with check (follower_id = auth.uid());
drop policy if exists "follows: deixar" on public.follows;
create policy "follows: deixar"  on public.follows for delete using (follower_id = auth.uid());

-- NOTIFICATIONS: só o dono lê e marca como lida.
drop policy if exists "notif: dono lê" on public.notifications;
create policy "notif: dono lê"    on public.notifications for select using (user_id = auth.uid());
drop policy if exists "notif: dono marca" on public.notifications;
create policy "notif: dono marca" on public.notifications for update using (user_id = auth.uid()) with check (user_id = auth.uid());

-- REPORTS: repórter cria e vê as suas; moderador vê tudo (claim no JWT).
drop policy if exists "reports: denunciar" on public.reports;
create policy "reports: denunciar" on public.reports for insert to authenticated with check (reporter_id = auth.uid());
drop policy if exists "reports: repórter ou moderador lê" on public.reports;
create policy "reports: repórter ou moderador lê" on public.reports for select using (
  reporter_id = auth.uid()
  or coalesce((auth.jwt() -> 'app_metadata' ->> 'role'), '') = 'moderator'
);

-- Realtime: publica as tabelas que a UI observa ao vivo.
-- Idempotente e autossuficiente: `alter publication ... add table` falha se a
-- publicação não existir OU se a tabela já estiver publicada, e qualquer um
-- dos dois abortaria a migration DEPOIS de habilitar o RLS — deixando tabelas
-- com RLS ligado e sem as policies seguintes.
do $$
begin
  if not exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    create publication supabase_realtime;
  end if;
end $$;

do $$
declare tabela text;
begin
  foreach tabela in array array['comments', 'notifications', 'reports'] loop
    if not exists (
      select 1 from pg_publication_tables
       where pubname = 'supabase_realtime'
         and schemaname = 'public'
         and tablename = tabela
    ) then
      execute format('alter publication supabase_realtime add table public.%I', tabela);
    end if;
  end loop;
end $$;
