import { supabase } from '../supabase/client';
import { compressImage } from '../utils/compressImage';

const BUCKET = 'recipe-images';

export const storageService = {
  /** Comprime e envia a foto; retorna o PATH (não a URL) para gravar no banco. */
  async uploadRecipeImage(userId: string, file: File): Promise<string> {
    const webp = await compressImage(file);
    const path = `${userId}/${crypto.randomUUID()}.webp`;
    const { error } = await supabase.storage
      .from(BUCKET)
      .upload(path, webp, { cacheControl: '3600', upsert: false, contentType: 'image/webp' });
    if (error) throw error;
    return path;
  },

  publicUrl(path: string | null): string | undefined {
    if (!path) return undefined;
    return supabase.storage.from(BUCKET).getPublicUrl(path).data.publicUrl;
  },
};
