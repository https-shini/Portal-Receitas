import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { authService } from '../services/auth.service';

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    const { error } = await authService.signIn(email, password);
    setLoading(false);
    if (error) setError('E-mail ou senha incorretos.');
    else navigate('/');
  }

  return (
    <section className="auth">
      <h1>Entrar</h1>
      <form onSubmit={submit}>
        <label>E-mail<input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required /></label>
        <label>Senha<input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required /></label>
        {error && <p className="error">{error}</p>}
        <button type="submit" disabled={loading}>{loading ? 'Entrando…' : 'Entrar'}</button>
      </form>

      <div className="auth__oauth">
        <button type="button" onClick={() => authService.signInWithGoogle()}>Continuar com Google</button>
        <button type="button" onClick={() => authService.signInWithGitHub()}>Continuar com GitHub</button>
      </div>

      <p>Não tem conta? <Link to="/cadastro">Cadastre-se</Link></p>
    </section>
  );
}
