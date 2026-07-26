import type { Database, Difficulty, RecipeStatus } from '../supabase/database.types';

type Tables = Database['public']['Tables'];

export type UserProfile = Tables['users']['Row'];
export type Category = Tables['categories']['Row'];
export type RecipeRow = Tables['recipes']['Row'];
export type Comment = Tables['comments']['Row'];
export type { Difficulty, RecipeStatus };

/** Card de receita para o catálogo (projeção enxuta + autor). */
export interface RecipeCardModel {
  id: number;
  title: string;
  slug: string;
  image_path: string | null;
  difficulty: Difficulty;
  author: { display_name: string } | null;
}

/** Comentário com dados do autor para exibição. */
export interface CommentWithAuthor extends Comment {
  author: { display_name: string; avatar_path: string | null } | null;
}

export interface RatingStats {
  average: number;
  total: number;
}

/** Receita completa (página de detalhe) com joins resolvidos. */
export interface RecipeDetail {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  image_path: string | null;
  prep_minutes: number | null;
  difficulty: Difficulty;
  status: RecipeStatus;
  tags: unknown;
  created_at: string;
  author: { display_name: string; avatar_path: string | null } | null;
  category: { name: string; slug: string } | null;
  ingredients: { quantity: string | null; unit: string | null; ingredient: { name: string } | null }[];
}
