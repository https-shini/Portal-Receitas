// ─────────────────────────────────────────────────────────────
// Edge Function: generate-tags
// Gera tags para uma receita via IA e grava em recipes.tags.
// Ver docs/migracao-react-supabase-n8n.md §6.10.
//
// Deploy: supabase functions deploy generate-tags
// Segredos: supabase secrets set OPENAI_API_KEY=sk-...
// ─────────────────────────────────────────────────────────────
import { createClient } from 'jsr:@supabase/supabase-js@2';

const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: cors });

  try {
    const { recipeId } = await req.json();
    if (!recipeId) return json({ error: 'recipeId obrigatório' }, 400);

    const admin = createClient(
      Deno.env.get('SUPABASE_URL')!,
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
    );

    const { data: recipe } = await admin
      .from('recipes').select('title, description').eq('id', recipeId).single();
    if (!recipe) return json({ error: 'receita não encontrada' }, 404);

    const ai = await fetch('https://api.openai.com/v1/chat/completions', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${Deno.env.get('OPENAI_API_KEY')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        model: 'gpt-4o-mini',
        response_format: { type: 'json_object' },
        messages: [{
          role: 'user',
          content:
            `Gere um JSON {"tags": string[]} com 5 tags curtas (1-2 palavras) ` +
            `para a receita "${recipe.title}". Descrição: ${recipe.description ?? ''}`,
        }],
      }),
    }).then((r) => r.json());

    const parsed = JSON.parse(ai.choices?.[0]?.message?.content ?? '{"tags":[]}');
    const tags: string[] = Array.isArray(parsed.tags) ? parsed.tags.slice(0, 5) : [];

    await admin.from('recipes').update({ tags }).eq('id', recipeId);
    return json({ tags });
  } catch (e) {
    return json({ error: String(e) }, 500);
  }
});

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...cors, 'Content-Type': 'application/json' },
  });
}
