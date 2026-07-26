import { useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { commentsService } from '../services/comments.service';
import { supabase } from '../supabase/client';
import { useAuth } from '../contexts/AuthContext';
import type { CommentWithAuthor } from '../types';

export function useComments(recipeId: number) {
  const { user } = useAuth();
  const qc = useQueryClient();
  const key = ['comments', recipeId];

  const list = useQuery({
    queryKey: key,
    queryFn: () => commentsService.listByRecipe(recipeId),
  });

  // Realtime: novos comentários aparecem sem refresh (§6.9).
  useEffect(() => {
    const channel = supabase
      .channel(`comments:recipe:${recipeId}`)
      .on(
        'postgres_changes',
        { event: 'INSERT', schema: 'public', table: 'comments', filter: `recipe_id=eq.${recipeId}` },
        () => qc.invalidateQueries({ queryKey: key }),
      )
      .subscribe();
    return () => {
      supabase.removeChannel(channel);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [recipeId]);

  const add = useMutation({
    mutationFn: (body: string) => commentsService.add(user!.id, recipeId, body),
    onSuccess: (created) => {
      qc.setQueryData<CommentWithAuthor[]>(key, (old = []) => [...old, created]);
    },
  });

  const remove = useMutation({
    mutationFn: (id: number) => commentsService.remove(id),
    onSuccess: (_r, id) => {
      qc.setQueryData<CommentWithAuthor[]>(key, (old = []) => old.filter((c) => c.id !== id));
    },
  });

  return { comments: list.data ?? [], loading: list.isLoading, add, remove, canComment: Boolean(user) };
}
