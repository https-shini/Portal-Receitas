import { createClient } from '@supabase/supabase-js';
import type { Database } from './database.types';

const url = import.meta.env.VITE_SUPABASE_URL;
const anonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

if (!url || !anonKey) {
  // Falha cedo e clara em vez de erros obscuros de rede depois.
  throw new Error(
    'Config faltando: defina VITE_SUPABASE_URL e VITE_SUPABASE_ANON_KEY em web/.env.local',
  );
}

// Client tipado, único para toda a app. Usa a chave ANÔNIMA (pública);
// a service_role JAMAIS vai para o frontend.
export const supabase = createClient<Database>(url, anonKey, {
  auth: { persistSession: true, autoRefreshToken: true, detectSessionInUrl: true },
});
