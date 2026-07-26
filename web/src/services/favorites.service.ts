import { supabase } from '../supabase/client';

export const favoritesService = {
  async isFavorite(userId: string, recipeId: number): Promise<boolean> {
    const { count, error } = await supabase
      .from('favorites')
      .select('*', { count: 'exact', head: true })
      .eq('user_id', userId)
      .eq('recipe_id', recipeId);
    if (error) throw error;
    return (count ?? 0) > 0;
  },

  async add(userId: string, recipeId: number) {
    const { error } = await supabase.from('favorites').insert({ user_id: userId, recipe_id: recipeId });
    if (error) throw error;
  },

  async remove(userId: string, recipeId: number) {
    const { error } = await supabase
      .from('favorites')
      .delete()
      .eq('user_id', userId)
      .eq('recipe_id', recipeId);
    if (error) throw error;
  },
};
