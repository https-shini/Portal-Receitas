import { useFollow } from '../hooks/useFollow';

export function FollowButton({ targetUserId }: { targetUserId: string }) {
  const { isFollowing, followerCount, canFollow, toggle, pending } = useFollow(targetUserId);
  return (
    <div className="follow">
      <span className="follow__count">{followerCount} seguidor{followerCount === 1 ? '' : 'es'}</span>
      {canFollow && (
        <button type="button" onClick={toggle} disabled={pending}>
          {isFollowing ? 'Deixar de seguir' : 'Seguir'}
        </button>
      )}
    </div>
  );
}
