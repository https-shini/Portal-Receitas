"""Validate migrations on an isolated PostgreSQL 16 engine.

The auth schema/functions below are local fixtures for Supabase's SQL
contracts. This does not provision or validate a hosted Supabase project.
"""

from pathlib import Path
import subprocess
import time
import uuid

root = Path(__file__).resolve().parents[2]
container = "portal-pg-smoke-" + uuid.uuid4().hex[:12]


def sql(script):
    result = subprocess.run(
        ["docker", "exec", "-i", container, "psql", "-U", "postgres",
         "-v", "ON_ERROR_STOP=1", "--single-transaction", "-q"],
        input=script, text=True, capture_output=True,
    )
    if result.returncode:
        raise RuntimeError(result.stderr)
    return result.stdout


try:
    subprocess.run(
        ["docker", "run", "-d", "--name", container, "--network", "none",
         "-e", "POSTGRES_HOST_AUTH_METHOD=trust", "postgres:16-alpine",
         "-c", "wal_level=logical"], check=True, stdout=subprocess.DEVNULL,
    )
    for _ in range(60):
        ready = subprocess.run(
            ["docker", "exec", container, "pg_isready", "-h", "127.0.0.1", "-U", "postgres"],
            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        )
        if ready.returncode == 0:
            break
        time.sleep(1)
    else:
        raise RuntimeError("PostgreSQL did not start")

    sql("""
        CREATE ROLE anon;
        CREATE ROLE authenticated;
        CREATE SCHEMA auth;
        CREATE TABLE auth.users (id uuid PRIMARY KEY, raw_user_meta_data jsonb DEFAULT '{}');
        CREATE FUNCTION auth.uid() RETURNS uuid LANGUAGE sql STABLE AS $$
          SELECT NULLIF(current_setting('request.jwt.claim.sub', true), '')::uuid;
        $$;
        CREATE FUNCTION auth.jwt() RETURNS jsonb LANGUAGE sql STABLE AS $$
          SELECT COALESCE(NULLIF(current_setting('request.jwt.claims', true), ''), '{}')::jsonb;
        $$;
        GRANT USAGE ON SCHEMA auth, public TO anon, authenticated;
    """)
    migrations = sorted((root / "supabase/migrations").glob("*.sql"))
    if len(migrations) != 3:
        raise RuntimeError("Update the validation target for the new migrations")
    for run in range(3):
        for migration in migrations:
            sql(migration.read_text())
        sql((root / "supabase/seed.sql").read_text())
        print(f"PASS: migrations and seed applied, run {run + 1}")

    sql("""
        DO $$ BEGIN
          IF (SELECT count(*) FROM pg_tables WHERE schemaname='public') <> 11
            OR (SELECT count(*) FROM pg_policies WHERE schemaname='public') <> 25
            OR EXISTS (SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
                       WHERE n.nspname='public' AND c.relkind='r' AND NOT c.relrowsecurity)
            OR (SELECT count(*) FROM pg_publication_tables WHERE pubname='supabase_realtime') <> 3
            OR (SELECT count(*) FROM pg_trigger WHERE NOT tgisinternal) <> 6
            OR (SELECT count(*) FROM categories) <> 8
            OR (SELECT count(*) FROM ingredients) <> 15
          THEN RAISE EXCEPTION 'Schema/RLS/publication/seed invariant failed'; END IF;
        END $$;
        INSERT INTO auth.users(id) VALUES
          ('00000000-0000-0000-0000-000000000001'),
          ('00000000-0000-0000-0000-000000000002');
        INSERT INTO recipes(author_id,title,slug,status) VALUES
          ('00000000-0000-0000-0000-000000000001','Draft Recipe','draft-recipe','draft'),
          ('00000000-0000-0000-0000-000000000001','Public Recipe','public-recipe','published');
        GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO anon, authenticated;
        GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated;
        SET LOCAL ROLE anon;
        DO $$ BEGIN
          IF (SELECT count(*) FROM recipes) <> 1 THEN RAISE EXCEPTION 'Draft exposed to anon'; END IF;
        END $$;
    """)
    sql("""
        SELECT set_config('request.jwt.claim.sub','00000000-0000-0000-0000-000000000002',true);
        SET LOCAL ROLE authenticated;
        DO $$ BEGIN
          IF (SELECT count(*) FROM recipes) <> 1 THEN RAISE EXCEPTION 'Draft exposed to another user'; END IF;
          BEGIN
            INSERT INTO recipes(author_id,title,slug) VALUES
              ('00000000-0000-0000-0000-000000000001','Forbidden Recipe','forbidden-recipe');
            RAISE EXCEPTION 'RLS accepted another author';
          EXCEPTION WHEN insufficient_privilege THEN NULL; END;
        END $$;
        INSERT INTO comments(author_id,recipe_id,body) VALUES
          ('00000000-0000-0000-0000-000000000002',2,'Migration smoke test');
    """)
    sql("""
        DO $$ BEGIN
          IF (SELECT count(*) FROM users) <> 2
            OR NOT EXISTS (SELECT 1 FROM recipes WHERE slug='public-recipe' AND search_doc IS NOT NULL)
            OR NOT EXISTS (SELECT 1 FROM notifications
                 WHERE user_id='00000000-0000-0000-0000-000000000001' AND type='new_comment')
          THEN RAISE EXCEPTION 'Profile/search/notification trigger failed'; END IF;
        END $$;
    """)
    print("PASS: schema, RLS isolation, profiles, search, Realtime publication and notifications")
finally:
    subprocess.run(["docker", "rm", "-f", "-v", container], check=False, stdout=subprocess.DEVNULL)
