// ─────────────────────────────────────────────────────────────
// Edge Function: recipe-stats
// Média + contagem de avaliações de uma receita (agregação confiável).
// Ver docs/migracao-react-supabase-n8n.md §6.10.
// ─────────────────────────────────────────────────────────────
import { createClient } from 'jsr:@supabase/supabase-js@2';

const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: cors });
  try {
    const url = new URL(req.url);
    const recipeId = Number(url.searchParams.get('recipeId'));
    if (!recipeId) return json({ error: 'recipeId obrigatório' }, 400);

    const admin = createClient(
      Deno.env.get('SUPABASE_URL')!,
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
    );
    const { data, error } = await admin.rpc('recipe_rating_stats', { p_recipe_id: recipeId });
    if (error) return json({ error: error.message }, 500);
    return json(data?.[0] ?? { average: 0, total: 0 });
  } catch (e) {
    return json({ error: String(e) }, 500);
  }
});

function json(b: unknown, status = 200) {
  return new Response(JSON.stringify(b), { status, headers: { ...cors, 'Content-Type': 'application/json' } });
}
