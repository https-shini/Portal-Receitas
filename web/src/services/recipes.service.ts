import { supabase } from '../supabase/client';
import { uniqueSlug } from '../utils/slugify';
import type { RecipeCardModel, RatingStats, RecipeDetail } from '../types';

const PAGE_SIZE = 12;

export interface NewRecipeInput {
  title: string;
  description: string;
  categoryId: number | null;
  prepMinutes: number | null;
  difficulty: 'facil' | 'medio' | 'dificil';
  imagePath: string | null;
  ingredients: { ingredientId: number; quantity?: string; unit?: string }[];
}

export const recipesService = {
  async listPublished(page = 0, search = ''): Promise<RecipeCardModel[]> {
    const from = page * PAGE_SIZE;
    let query = supabase
      .from('recipes')
      .select('id, title, slug, image_path, difficulty, author:users(display_name)')
      .eq('status', 'published')
      .order('created_at', { ascending: false })
      .range(from, from + PAGE_SIZE - 1);

    if (search.trim()) {
      // textSearch usa o índice GIN da coluna search_doc.
      query = query.textSearch('search_doc', search.trim(), { type: 'websearch', config: 'portuguese' });
    }

    const { data, error } = await query;
    if (error) throw error;
    return (data ?? []) as unknown as RecipeCardModel[];
  },

  async getBySlug(slug: string): Promise<RecipeDetail> {
    const { data, error } = await supabase
      .from('recipes')
      .select(
        '*, author:users(display_name, avatar_path), category:categories(name, slug), ' +
          'ingredients:recipe_ingredients(quantity, unit, ingredient:ingredients(name))',
      )
      .eq('slug', slug)
      .single();
    if (error) throw error;
    return data as unknown as RecipeDetail;
  },

  async ratingStats(recipeId: number): Promise<RatingStats> {
    const { data, error } = await supabase.rpc('recipe_rating_stats', { p_recipe_id: recipeId });
    if (error) throw error;
    return data?.[0] ?? { average: 0, total: 0 };
  },

  async create(authorId: string, input: NewRecipeInput) {
    const { data: recipe, error } = await supabase
      .from('recipes')
      .insert({
        author_id: authorId,
        title: input.title,
        slug: uniqueSlug(input.title),
        description: input.description,
        category_id: input.categoryId,
        prep_minutes: input.prepMinutes,
        difficulty: input.difficulty,
        image_path: input.imagePath,
        status: 'published',
      })
      .select('id, slug')
      .single();
    if (error) throw error;

    if (input.ingredients.length) {
      const rows = input.ingredients.map((i) => ({
        recipe_id: recipe.id,
        ingredient_id: i.ingredientId,
        quantity: i.quantity ?? null,
        unit: i.unit ?? null,
      }));
      const { error: riError } = await supabase.from('recipe_ingredients').insert(rows);
      if (riError) throw riError;
    }

    return recipe;
  },

  async listByAuthor(authorId: string): Promise<RecipeCardModel[]> {
    const { data, error } = await supabase
      .from('recipes')
      .select('id, title, slug, image_path, difficulty, author:users(display_name)')
      .eq('author_id', authorId)
      .order('created_at', { ascending: false });
    if (error) throw error;
    return (data ?? []) as unknown as RecipeCardModel[];
  },
};

export { PAGE_SIZE };
