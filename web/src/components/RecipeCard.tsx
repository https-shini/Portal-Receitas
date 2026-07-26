import { Link } from 'react-router-dom';
import { storageService } from '../services/storage.service';
import type { RecipeCardModel } from '../types';

const DIFFICULTY_LABEL: Record<string, string> = { facil: 'Fácil', medio: 'Médio', dificil: 'Difícil' };

export function RecipeCard({ recipe }: { recipe: RecipeCardModel }) {
  const img = storageService.publicUrl(recipe.image_path);
  return (
    <li className="recipe-card">
      <Link to={`/receita/${recipe.slug}`}>
        <div className="recipe-card__media">
          {img ? <img src={img} alt={recipe.title} loading="lazy" width={400} height={300} /> : <div className="recipe-card__placeholder" aria-hidden />}
        </div>
        <div className="recipe-card__body">
          <h3>{recipe.title}</h3>
          <p className="recipe-card__meta">
            {DIFFICULTY_LABEL[recipe.difficulty]} · por {recipe.author?.display_name ?? 'Chef'}
          </p>
        </div>
      </Link>
    </li>
  );
}
