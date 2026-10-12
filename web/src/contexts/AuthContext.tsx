import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import type { Session, User } from '@supabase/supabase-js';
import { supabase } from '../supabase/client';

interface AuthContextValue {
  session: Session | null;
  user: User | null;
  loading: boolean;
}

const AuthContext = createContext<AuthContextValue>({ session: null, user: null, loading: true });

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let ativo = true;

    // O .catch é essencial: sem ele, uma falha em getSession() (rede fora no
    // primeiro carregamento, token guardado corrompido, storage bloqueado)
    // deixa `loading` em true para sempre — e a app inteira fica presa no
    // "Carregando…", inclusive as rotas protegidas do RequireAuth.
    supabase.auth
      .getSession()
      .then(({ data }) => {
        if (ativo) setSession(data.session);
      })
      .catch(() => {
        if (ativo) setSession(null); // segue como visitante
      })
      .finally(() => {
        if (ativo) setLoading(false);
      });

    const { data: sub } = supabase.auth.onAuthStateChange((_event, s) => {
      if (!ativo) return;
      setSession(s);
      setLoading(false);
    });

    return () => {
      ativo = false;
      sub.subscription.unsubscribe();
    };
  }, []);

  return (
    <AuthContext.Provider value={{ session, user: session?.user ?? null, loading }}>
      {children}
    </AuthContext.Provider>
  );
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth() {
  return useContext(AuthContext);
}
