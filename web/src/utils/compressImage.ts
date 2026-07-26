/**
 * Comprime uma imagem no cliente (WebP) antes do upload — reduz custo de
 * Storage/banda e melhora o LCP. Ver §6.8 do guia.
 */
export async function compressImage(file: File, maxW = 1280, quality = 0.8): Promise<Blob> {
  const bitmap = await createImageBitmap(file);
  const scale = Math.min(1, maxW / bitmap.width);
  const width = Math.round(bitmap.width * scale);
  const height = Math.round(bitmap.height * scale);

  const canvas = new OffscreenCanvas(width, height);
  const ctx = canvas.getContext('2d');
  if (!ctx) throw new Error('Canvas 2D indisponível');
  ctx.drawImage(bitmap, 0, 0, width, height);

  return canvas.convertToBlob({ type: 'image/webp', quality });
}
