/** Gera um slug URL-safe a partir de um título (acentos → ascii). */
export function slugify(input: string): string {
  const base = input
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
  return base || 'receita';
}

/** Slug único adicionando um sufixo curto aleatório (evita colisão de UNIQUE). */
export function uniqueSlug(input: string): string {
  return `${slugify(input)}-${Math.random().toString(36).slice(2, 7)}`;
}
