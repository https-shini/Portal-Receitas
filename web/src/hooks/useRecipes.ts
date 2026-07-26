import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { recipesService, PAGE_SIZE } from '../services/recipes.service';

/** Feed paginado (infinite scroll) do catálogo público, com busca opcional. */
export function useRecipesFeed(search = '') {
  return useInfiniteQuery({
    queryKey: ['recipes', 'feed', search],
    queryFn: ({ pageParam }) => recipesService.listPublished(pageParam, search),
    initialPageParam: 0,
    getNextPageParam: (last, all) => (last.length === PAGE_SIZE ? all.length : undefined),
  });
}

export function useRecipe(slug: string) {
  return useQuery({
    queryKey: ['recipe', slug],
    queryFn: () => recipesService.getBySlug(slug),
    enabled: Boolean(slug),
  });
}

export function useRatingStats(recipeId: number | undefined) {
  return useQuery({
    queryKey: ['recipe-stats', recipeId],
    queryFn: () => recipesService.ratingStats(recipeId as number),
    enabled: Boolean(recipeId),
  });
}
