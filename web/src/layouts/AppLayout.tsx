import { Link, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { authService } from '../services/auth.service';

export function AppLayout() {
  const { user, loading } = useAuth();
  const navigate = useNavigate();

  async function logout() {
    await authService.signOut();
    navigate('/');
  }

  return (
    <>
      <header className="site-header">
        <div className="container site-header__row">
          <Link to="/" className="brand">🍳 HomeMadeGourmet</Link>
          <nav className="site-nav">
            <Link to="/">Receitas</Link>
            {loading ? null : user ? (
              <>
                <Link to="/nova-receita">Publicar</Link>
                <Link to={`/perfil/${user.id}`}>Meu perfil</Link>
                <button type="button" onClick={logout}>Sair</button>
              </>
            ) : (
              <Link to="/login">Entrar</Link>
            )}
          </nav>
        </div>
      </header>
      <main className="container" id="conteudo">
        <Outlet />
      </main>
      <footer className="site-footer">
        <div className="container">HomeMadeGourmet — migração React + Supabase</div>
      </footer>
    </>
  );
}
