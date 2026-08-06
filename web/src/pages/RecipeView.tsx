import { useParams } from 'react-router-dom';
import { useRecipe } from '../hooks/useRecipes';
import { useRating } from '../hooks/useRating';
import { useFavorite } from '../hooks/useFavorite';
import { StarRating } from '../components/StarRating';
import { CommentThread } from '../components/CommentThread';
import { ReportButton } from '../components/ReportButton';
import { storageService } from '../services/storage.service';

interface IngredientJoin {
  quantity: string | null;
  unit: string | null;
  ingredient: { name: string } | null;
}

export default function RecipeView() {
  const { slug = '' } = useParams();
  const { data: recipe, isLoading, isError, error } = useRecipe(slug);
  const rating = useRating(recipe?.id ?? 0);
  const fav = useFavorite(recipe?.id ?? 0);

  if (isLoading) return <p>Carregando…</p>;
  if (isError) return <p className="error">{(error as Error).message}</p>;
  if (!recipe) return <p>Receita não encontrada.</p>;

  const img = storageService.publicUrl(recipe.image_path);
  const ingredients = (recipe.ingredients ?? []) as IngredientJoin[];

  return (
    <article className="recipe">
      <h1>{recipe.title}</h1>
      <div className="recipe__actions">
        <StarRating
          value={rating.canRate ? rating.userScore || rating.average : rating.average}
          count={rating.total}
          onRate={rating.canRate ? rating.rate : undefined}
        />
        {fav.canFavorite && (
          <button type="button" onClick={fav.toggle} disabled={fav.pending}>
            {fav.isFavorite ? '♥ Favoritada' : '♡ Favoritar'}
          </button>
        )}
        <ReportButton targetType="recipe" targetId={recipe.id} />
      </div>

      {img && <img src={img} alt={recipe.title} className="recipe__image" />}
      {recipe.description && <p>{recipe.description}</p>}

      <h2>Ingredientes</h2>
      <ul>
        {ingredients.map((i, idx) => (
          <li key={idx}>
            {[i.quantity, i.unit, i.ingredient?.name].filter(Boolean).join(' ')}
          </li>
        ))}
      </ul>

      <CommentThread recipeId={recipe.id} />
    </article>
  );
}
