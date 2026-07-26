import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { recipesService } from '../services/recipes.service';
import { RecipeCard } from '../components/RecipeCard';

export default function Profile() {
  const { id = '' } = useParams();
  const { data, isLoading } = useQuery({
    queryKey: ['recipes', 'by-author', id],
    queryFn: () => recipesService.listByAuthor(id),
    enabled: Boolean(id),
  });

  return (
    <section>
      <h1>Receitas do chef</h1>
      {isLoading ? (
        <p>Carregando…</p>
      ) : (
        <ul className="recipe-grid">
          {(data ?? []).map((r) => (
            <RecipeCard key={r.id} recipe={r} />
          ))}
          {(data ?? []).length === 0 && <p>Nenhuma receita ainda.</p>}
        </ul>
      )}
    </section>
  );
}
