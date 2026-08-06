import { describe, it, expect } from 'vitest';
import { slugify, uniqueSlug } from './slugify';

describe('slugify', () => {
  it('remove acentos e normaliza', () => {
    expect(slugify('Macarrão à Carbonara')).toBe('macarrao-a-carbonara');
  });

  it('colapsa separadores e apara hifens', () => {
    expect(slugify('  Bolo   de___Cenoura!! ')).toBe('bolo-de-cenoura');
  });

  it('usa fallback quando o resultado ficaria vazio', () => {
    expect(slugify('!!!')).toBe('receita');
  });

  it('uniqueSlug preserva a base e adiciona sufixo', () => {
    const s = uniqueSlug('Brigadeiro');
    expect(s.startsWith('brigadeiro-')).toBe(true);
    expect(s.length).toBeGreaterThan('brigadeiro-'.length);
  });
});
