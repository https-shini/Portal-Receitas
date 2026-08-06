import { supabase } from '../supabase/client';

export const followsService = {
  async isFollowing(followerId: string, followeeId: string): Promise<boolean> {
    const { count, error } = await supabase
      .from('follows')
      .select('*', { count: 'exact', head: true })
      .eq('follower_id', followerId)
      .eq('followee_id', followeeId);
    if (error) throw error;
    return (count ?? 0) > 0;
  },

  async follow(followerId: string, followeeId: string) {
    const { error } = await supabase.from('follows').insert({ follower_id: followerId, followee_id: followeeId });
    if (error) throw error;
  },

  async unfollow(followerId: string, followeeId: string) {
    const { error } = await supabase
      .from('follows')
      .delete()
      .eq('follower_id', followerId)
      .eq('followee_id', followeeId);
    if (error) throw error;
  },

  async followerCount(userId: string): Promise<number> {
    const { count, error } = await supabase
      .from('follows')
      .select('*', { count: 'exact', head: true })
      .eq('followee_id', userId);
    if (error) throw error;
    return count ?? 0;
  },
};
