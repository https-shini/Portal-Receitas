import { useParams } from 'react-router-dom';
import { useRecipe, useRatingStats } from '../hooks/useRecipes';
import { useFavorite } from '../hooks/useFavorite';
import { StarRating } from '../components/StarRating';
import { CommentThread } from '../components/CommentThread';
import { storageService } from '../services/storage.service';

interface IngredientJoin {
  quantity: string | null;
  unit: string | null;
  ingredient: { name: string } | null;
}

export default function RecipeView() {
  const { slug = '' } = useParams();
  const { data: recipe, isLoading, isError, error } = useRecipe(slug);
  const stats = useRatingStats(recipe?.id);
  const fav = useFavorite(recipe?.id ?? 0);

  if (isLoading) return <p>Carregando…</p>;
  if (isError) return <p className="error">{(error as Error).message}</p>;
  if (!recipe) return <p>Receita não encontrada.</p>;

  const img = storageService.publicUrl(recipe.image_path);
  const ingredients = (recipe.ingredients ?? []) as IngredientJoin[];

  return (
    <article className="recipe">
      <h1>{recipe.title}</h1>
      <StarRating value={stats.data?.average ?? 0} count={stats.data?.total ?? 0} />
      {fav.canFavorite && (
        <button type="button" onClick={fav.toggle} disabled={fav.pending}>
          {fav.isFavorite ? '♥ Favoritada' : '♡ Favoritar'}
        </button>
      )}

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
