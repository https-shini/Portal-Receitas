// ─────────────────────────────────────────────────────────────
// Edge Function: moderate-comment
// Classifica um texto pela Moderation API da OpenAI antes de exibir.
// Ver docs/migracao-react-supabase-n8n.md §6.10.
// ─────────────────────────────────────────────────────────────
const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: cors });
  try {
    const { body } = await req.json();
    if (typeof body !== 'string' || !body.trim()) {
      return json({ allowed: false, error: 'texto vazio' }, 400);
    }
    const res = await fetch('https://api.openai.com/v1/moderations', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${Deno.env.get('OPENAI_API_KEY')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ input: body }),
    }).then((r) => r.json());

    const result = res.results?.[0];
    return json({ allowed: !result?.flagged, categories: result?.categories ?? {} });
  } catch (e) {
    return json({ allowed: true, error: String(e) }, 200); // fail-open: não bloqueia por erro de infra
  }
});

function json(b: unknown, status = 200) {
  return new Response(JSON.stringify(b), { status, headers: { ...cors, 'Content-Type': 'application/json' } });
}
