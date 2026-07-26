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
create policy "users: leitura pública" on public.users for select using (true);
create policy "users: dono edita"      on public.users for update using (id = auth.uid()) with check (id = auth.uid());

-- CATEGORIES / INGREDIENTS: leitura livre; escrita só autenticado.
create policy "categories: leitura"   on public.categories for select using (true);
create policy "ingredients: leitura"  on public.ingredients for select using (true);
create policy "ingredients: inserir"  on public.ingredients for insert to authenticated with check (true);

-- RECIPES: leitura pública de publicadas ou próprias; dono gerencia.
create policy "recipes: ler publicadas ou próprias" on public.recipes for select
  using (status = 'published' or author_id = auth.uid());
create policy "recipes: autor insere"  on public.recipes for insert to authenticated with check (author_id = auth.uid());
create policy "recipes: autor edita"   on public.recipes for update using (author_id = auth.uid()) with check (author_id = auth.uid());
create policy "recipes: autor apaga"   on public.recipes for delete using (author_id = auth.uid());

-- RECIPE_INGREDIENTS: leitura livre; só o autor da receita escreve.
create policy "ri: leitura" on public.recipe_ingredients for select using (true);
create policy "ri: autor da receita escreve" on public.recipe_ingredients for all
  using (exists (select 1 from public.recipes r where r.id = recipe_id and r.author_id = auth.uid()))
  with check (exists (select 1 from public.recipes r where r.id = recipe_id and r.author_id = auth.uid()));

-- COMMENTS: leitura autenticada; autor gerencia o próprio.
create policy "comments: leitura autenticada" on public.comments for select to authenticated using (true);
create policy "comments: inserir como si"     on public.comments for insert to authenticated with check (author_id = auth.uid());
create policy "comments: apagar o próprio"    on public.comments for delete using (author_id = auth.uid());

-- FAVORITES / RATINGS: cada um só mexe nos seus.
create policy "favorites: dono" on public.favorites for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "ratings: dono"   on public.ratings   for all using (user_id = auth.uid()) with check (user_id = auth.uid());
create policy "ratings: leitura" on public.ratings  for select using (true);

-- FOLLOWS: leitura pública; só o próprio seguidor cria/apaga.
create policy "follows: leitura" on public.follows for select using (true);
create policy "follows: seguir"  on public.follows for insert to authenticated with check (follower_id = auth.uid());
create policy "follows: deixar"  on public.follows for delete using (follower_id = auth.uid());

-- NOTIFICATIONS: só o dono lê e marca como lida.
create policy "notif: dono lê"    on public.notifications for select using (user_id = auth.uid());
create policy "notif: dono marca" on public.notifications for update using (user_id = auth.uid()) with check (user_id = auth.uid());

-- REPORTS: repórter cria e vê as suas; moderador vê tudo (claim no JWT).
create policy "reports: denunciar" on public.reports for insert to authenticated with check (reporter_id = auth.uid());
create policy "reports: repórter ou moderador lê" on public.reports for select using (
  reporter_id = auth.uid()
  or coalesce((auth.jwt() -> 'app_metadata' ->> 'role'), '') = 'moderator'
);

-- Realtime: publica as tabelas que a UI observa ao vivo.
alter publication supabase_realtime add table public.comments;
alter publication supabase_realtime add table public.notifications;
alter publication supabase_realtime add table public.reports;
