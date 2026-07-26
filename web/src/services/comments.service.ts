import { supabase } from '../supabase/client';
import type { CommentWithAuthor } from '../types';

export const commentsService = {
  async listByRecipe(recipeId: number): Promise<CommentWithAuthor[]> {
    const { data, error } = await supabase
      .from('comments')
      .select('*, author:users(display_name, avatar_path)')
      .eq('recipe_id', recipeId)
      .order('created_at', { ascending: true });
    if (error) throw error;
    return (data ?? []) as unknown as CommentWithAuthor[];
  },

  async add(authorId: string, recipeId: number, body: string, parentId: number | null = null) {
    const { data, error } = await supabase
      .from('comments')
      .insert({ author_id: authorId, recipe_id: recipeId, body, parent_id: parentId })
      .select('*, author:users(display_name, avatar_path)')
      .single();
    if (error) throw error;
    return data as unknown as CommentWithAuthor;
  },

  async remove(commentId: number) {
    const { error } = await supabase.from('comments').delete().eq('id', commentId);
    if (error) throw error;
  },
};
