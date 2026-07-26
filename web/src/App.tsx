import { lazy, Suspense } from 'react';
import { createBrowserRouter, RouterProvider, Navigate, Outlet } from 'react-router-dom';
import { ErrorBoundary } from 'react-error-boundary';
import { AppLayout } from './layouts/AppLayout';
import { useAuth } from './contexts/AuthContext';

const Home = lazy(() => import('./pages/Home'));
const RecipeView = lazy(() => import('./pages/RecipeView'));
const Login = lazy(() => import('./pages/Login'));
const Register = lazy(() => import('./pages/Register'));
const NewRecipe = lazy(() => import('./pages/NewRecipe'));
const Profile = lazy(() => import('./pages/Profile'));
const AuthCallback = lazy(() => import('./pages/AuthCallback'));

function RequireAuth() {
  const { session, loading } = useAuth();
  if (loading) return <p>Carregando…</p>;
  return session ? <Outlet /> : <Navigate to="/login" replace />;
}

const router = createBrowserRouter([
  {
    element: <AppLayout />,
    children: [
      { path: '/', element: <Home /> },
      { path: '/receita/:slug', element: <RecipeView /> },
      { path: '/login', element: <Login /> },
      { path: '/cadastro', element: <Register /> },
      { path: '/auth/callback', element: <AuthCallback /> },
      { path: '/perfil/:id', element: <Profile /> },
      {
        element: <RequireAuth />,
        children: [{ path: '/nova-receita', element: <NewRecipe /> }],
      },
      { path: '*', element: <Navigate to="/" replace /> },
    ],
  },
]);

export default function App() {
  return (
    <ErrorBoundary fallback={<p style={{ padding: 24 }}>Algo deu errado. Recarregue a página.</p>}>
      <Suspense fallback={<p style={{ padding: 24 }}>Carregando…</p>}>
        <RouterProvider router={router} />
      </Suspense>
    </ErrorBoundary>
  );
}
