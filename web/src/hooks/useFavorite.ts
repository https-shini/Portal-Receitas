import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { favoritesService } from '../services/favorites.service';
import { useAuth } from '../contexts/AuthContext';

/** Estado + toggle otimista de favorito para uma receita. */
export function useFavorite(recipeId: number) {
  const { user } = useAuth();
  const qc = useQueryClient();
  const key = ['favorite', recipeId, user?.id];

  const query = useQuery({
    queryKey: key,
    queryFn: () => favoritesService.isFavorite(user!.id, recipeId),
    enabled: Boolean(user),
  });

  const mutation = useMutation({
    mutationFn: (isFav: boolean) =>
      isFav ? favoritesService.remove(user!.id, recipeId) : favoritesService.add(user!.id, recipeId),
    onMutate: async (isFav) => {
      await qc.cancelQueries({ queryKey: key });
      const prev = qc.getQueryData<boolean>(key);
      qc.setQueryData(key, !isFav);
      return { prev };
    },
    onError: (_e, _v, ctx) => qc.setQueryData(key, ctx?.prev),
    onSettled: () => qc.invalidateQueries({ queryKey: key }),
  });

  return {
    isFavorite: query.data ?? false,
    canFavorite: Boolean(user),
    toggle: () => mutation.mutate(query.data ?? false),
    pending: mutation.isPending,
  };
}
