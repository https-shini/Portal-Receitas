-- ─────────────────────────────────────────────────────────────
-- 20260101000200_notifications_triggers.sql
-- Geração automática de notificações e helper de papel moderador.
-- Ver docs/migracao-react-supabase-n8n.md §6.14 (fluxos) e §6.9 (Realtime).
-- ─────────────────────────────────────────────────────────────

-- Novo seguidor → notifica quem foi seguido.
create or replace function public.notify_new_follower() returns trigger
language plpgsql security definer set search_path = public as $$
begin
  insert into public.notifications (user_id, type, payload)
  values (new.followee_id, 'new_follower', jsonb_build_object('follower_id', new.follower_id));
  return new;
end $$;

drop trigger if exists trg_notify_follower on public.follows;
create trigger trg_notify_follower after insert on public.follows
  for each row execute function public.notify_new_follower();

-- Novo comentário → notifica o autor da receita (exceto se comentar a própria).
create or replace function public.notify_new_comment() returns trigger
language plpgsql security definer set search_path = public as $$
declare recipe_author uuid; recipe_title text;
begin
  select author_id, title into recipe_author, recipe_title
    from public.recipes where id = new.recipe_id;
  if recipe_author is not null and recipe_author <> new.author_id then
    insert into public.notifications (user_id, type, payload)
    values (recipe_author, 'new_comment',
      jsonb_build_object('recipe_id', new.recipe_id, 'title', recipe_title, 'comment_id', new.id));
  end if;
  return new;
end $$;

drop trigger if exists trg_notify_comment on public.comments;
create trigger trg_notify_comment after insert on public.comments
  for each row execute function public.notify_new_comment();

-- Nova avaliação → notifica o autor da receita (exceto autoavaliação).
create or replace function public.notify_new_rating() returns trigger
language plpgsql security definer set search_path = public as $$
declare recipe_author uuid;
begin
  select author_id into recipe_author from public.recipes where id = new.recipe_id;
  if recipe_author is not null and recipe_author <> new.user_id then
    insert into public.notifications (user_id, type, payload)
    values (recipe_author, 'new_rating',
      jsonb_build_object('recipe_id', new.recipe_id, 'score', new.score));
  end if;
  return new;
end $$;

drop trigger if exists trg_notify_rating on public.ratings;
create trigger trg_notify_rating after insert on public.ratings
  for each row execute function public.notify_new_rating();

-- Helper: o usuário atual é moderador? (claim app_metadata.role no JWT)
create or replace function public.is_moderator() returns boolean
language sql stable as $$
  select coalesce((auth.jwt() -> 'app_metadata' ->> 'role'), '') = 'moderator';
$$;

-- Moderador pode atualizar o status das denúncias.
create policy "reports: moderador atualiza" on public.reports for update
  using (public.is_moderator()) with check (public.is_moderator());
