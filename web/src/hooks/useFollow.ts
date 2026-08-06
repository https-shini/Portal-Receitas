import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { followsService } from '../services/follows.service';
import { useAuth } from '../contexts/AuthContext';

/** Estado de "seguindo" + contador + toggle otimista. */
export function useFollow(targetUserId: string) {
  const { user } = useAuth();
  const qc = useQueryClient();
  const canFollow = Boolean(user) && user?.id !== targetUserId;
  const key = ['following', targetUserId, user?.id];

  const following = useQuery({
    queryKey: key,
    queryFn: () => followsService.isFollowing(user!.id, targetUserId),
    enabled: canFollow,
  });

  const count = useQuery({
    queryKey: ['follower-count', targetUserId],
    queryFn: () => followsService.followerCount(targetUserId),
    enabled: Boolean(targetUserId),
  });

  const toggle = useMutation({
    mutationFn: (isFollowing: boolean) =>
      isFollowing
        ? followsService.unfollow(user!.id, targetUserId)
        : followsService.follow(user!.id, targetUserId),
    onMutate: async (isFollowing) => {
      await qc.cancelQueries({ queryKey: key });
      const prev = qc.getQueryData<boolean>(key);
      qc.setQueryData(key, !isFollowing);
      return { prev };
    },
    onError: (_e, _v, ctx) => qc.setQueryData(key, ctx?.prev),
    onSettled: () => {
      qc.invalidateQueries({ queryKey: key });
      qc.invalidateQueries({ queryKey: ['follower-count', targetUserId] });
    },
  });

  return {
    isFollowing: following.data ?? false,
    followerCount: count.data ?? 0,
    canFollow,
    toggle: () => toggle.mutate(following.data ?? false),
    pending: toggle.isPending,
  };
}
