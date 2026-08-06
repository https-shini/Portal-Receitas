import { supabase } from '../supabase/client';

export const ratingsService = {
  /** Nota do usuário logado para a receita (null se ainda não avaliou). */
  async getUserScore(userId: string, recipeId: number): Promise<number | null> {
    const { data, error } = await supabase
      .from('ratings')
      .select('score')
      .eq('user_id', userId)
      .eq('recipe_id', recipeId)
      .maybeSingle();
    if (error) throw error;
    return data?.score ?? null;
  },

  /** Upsert: um voto por usuário/receita (unique constraint). */
  async rate(userId: string, recipeId: number, score: number) {
    const { error } = await supabase
      .from('ratings')
      .upsert({ user_id: userId, recipe_id: recipeId, score }, { onConflict: 'user_id,recipe_id' });
    if (error) throw error;
  },
};
