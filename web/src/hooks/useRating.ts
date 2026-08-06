import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ratingsService } from '../services/ratings.service';
import { recipesService } from '../services/recipes.service';
import { useAuth } from '../contexts/AuthContext';

/** Média/contagem + nota do usuário + ação de avaliar (upsert). */
export function useRating(recipeId: number) {
  const { user } = useAuth();
  const qc = useQueryClient();

  const stats = useQuery({
    queryKey: ['recipe-stats', recipeId],
    queryFn: () => recipesService.ratingStats(recipeId),
    enabled: Boolean(recipeId),
  });

  const userScore = useQuery({
    queryKey: ['user-rating', recipeId, user?.id],
    queryFn: () => ratingsService.getUserScore(user!.id, recipeId),
    enabled: Boolean(user && recipeId),
  });

  const rate = useMutation({
    mutationFn: (score: number) => ratingsService.rate(user!.id, recipeId, score),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['recipe-stats', recipeId] });
      qc.invalidateQueries({ queryKey: ['user-rating', recipeId, user?.id] });
    },
  });

  return {
    average: stats.data?.average ?? 0,
    total: stats.data?.total ?? 0,
    userScore: userScore.data ?? 0,
    canRate: Boolean(user),
    rate: (score: number) => rate.mutate(score),
  };
}
