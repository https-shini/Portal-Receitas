import { useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationsService } from '../services/notifications.service';
import { supabase } from '../supabase/client';
import { useAuth } from '../contexts/AuthContext';

/** Lista + contador não-lidas, atualizados ao vivo (Realtime). */
export function useNotifications() {
  const { user } = useAuth();
  const qc = useQueryClient();

  const list = useQuery({
    queryKey: ['notifications', user?.id],
    queryFn: () => notificationsService.list(user!.id),
    enabled: Boolean(user),
  });

  const unread = useQuery({
    queryKey: ['notifications', 'unread', user?.id],
    queryFn: () => notificationsService.unreadCount(user!.id),
    enabled: Boolean(user),
  });

  useEffect(() => {
    if (!user) return;
    const channel = supabase
      .channel(`notif:${user.id}`)
      .on(
        'postgres_changes',
        { event: 'INSERT', schema: 'public', table: 'notifications', filter: `user_id=eq.${user.id}` },
        () => {
          qc.invalidateQueries({ queryKey: ['notifications', user.id] });
          qc.invalidateQueries({ queryKey: ['notifications', 'unread', user.id] });
        },
      )
      .subscribe();
    return () => {
      supabase.removeChannel(channel);
    };
  }, [user, qc]);

  const markAllRead = useMutation({
    mutationFn: () => notificationsService.markAllRead(user!.id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['notifications', user?.id] });
      qc.invalidateQueries({ queryKey: ['notifications', 'unread', user?.id] });
    },
  });

  return {
    notifications: list.data ?? [],
    unreadCount: unread.data ?? 0,
    markAllRead: () => markAllRead.mutate(),
  };
}
