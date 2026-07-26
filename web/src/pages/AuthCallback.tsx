import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

/** Destino do redirect de OAuth / verificação de e-mail. */
export default function AuthCallback() {
  const { loading, session } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!loading) navigate(session ? '/' : '/login', { replace: true });
  }, [loading, session, navigate]);

  return <p>Concluindo login…</p>;
}
