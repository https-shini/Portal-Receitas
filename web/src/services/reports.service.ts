import { supabase } from '../supabase/client';
import type { Database } from '../supabase/database.types';

export type Report = Database['public']['Tables']['reports']['Row'];

export const reportsService = {
  async create(reporterId: string, targetType: 'recipe' | 'comment', targetId: number, reason: string) {
    const { error } = await supabase
      .from('reports')
      .insert({ reporter_id: reporterId, target_type: targetType, target_id: targetId, reason });
    if (error) throw error;
  },

  /** Fila de moderação (RLS libera só para moderador ou o próprio repórter). */
  async listOpen(): Promise<Report[]> {
    const { data, error } = await supabase
      .from('reports')
      .select('*')
      .order('created_at', { ascending: false })
      .limit(100);
    if (error) throw error;
    return data ?? [];
  },

  async setStatus(id: number, status: 'open' | 'reviewing' | 'closed') {
    const { error } = await supabase.from('reports').update({ status }).eq('id', id);
    if (error) throw error;
  },
};
