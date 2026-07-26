import { useState } from 'react';
import { useRecipesFeed } from '../hooks/useRecipes';
import { RecipeCard } from '../components/RecipeCard';

export default function Home() {
  const [search, setSearch] = useState('');
  const { data, fetchNextPage, hasNextPage, isFetching, isError, error } = useRecipesFeed(search);
  const recipes = data?.pages.flat() ?? [];

  return (
    <section>
      <h1>O que vamos cozinhar hoje?</h1>
      <form
        role="search"
        onSubmit={(e) => e.preventDefault()}
        className="search-bar"
      >
        <input
          type="search"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Busque por nome ou ingrediente…"
          aria-label="Buscar receitas"
        />
      </form>

      {isError && <p className="error">{(error as Error).message}</p>}

      <ul className="recipe-grid">
        {recipes.map((r) => (
          <RecipeCard key={r.id} recipe={r} />
        ))}
      </ul>

      {recipes.length === 0 && !isFetching && <p>Nenhuma receita encontrada.</p>}

      {hasNextPage && (
        <button type="button" onClick={() => fetchNextPage()} disabled={isFetching}>
          {isFetching ? 'Carregando…' : 'Carregar mais'}
        </button>
      )}
    </section>
  );
}
