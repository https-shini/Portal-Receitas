// Tipos do banco. Em produção, gere automaticamente com:
//   npm run gen:types   (supabase gen types typescript --local)
// Esta versão é escrita à mão e espelha supabase/migrations/*.sql.

export type Json = string | number | boolean | null | { [k: string]: Json } | Json[];

export type Difficulty = 'facil' | 'medio' | 'dificil';
export type RecipeStatus = 'draft' | 'published';

export interface Database {
  public: {
    Tables: {
      users: {
        Row: { id: string; display_name: string; avatar_path: string | null; bio: string | null; created_at: string };
        Insert: { id: string; display_name: string; avatar_path?: string | null; bio?: string | null };
        Update: { display_name?: string; avatar_path?: string | null; bio?: string | null };
        Relationships: [];
      };
      categories: {
        Row: { id: number; name: string; slug: string };
        Insert: { name: string; slug: string };
        Update: { name?: string; slug?: string };
        Relationships: [];
      };
      recipes: {
        Row: {
          id: number; author_id: string; category_id: number | null; title: string; slug: string;
          description: string | null; image_path: string | null; prep_minutes: number | null;
          difficulty: Difficulty; status: RecipeStatus; tags: Json; created_at: string; updated_at: string;
        };
        Insert: {
          author_id: string; category_id?: number | null; title: string; slug: string;
          description?: string | null; image_path?: string | null; prep_minutes?: number | null;
          difficulty?: Difficulty; status?: RecipeStatus; tags?: Json;
        };
        Update: Partial<Database['public']['Tables']['recipes']['Insert']>;
        Relationships: [];
      };
      ingredients: {
        Row: { id: number; name: string };
        Insert: { name: string };
        Update: { name?: string };
        Relationships: [];
      };
      recipe_ingredients: {
        Row: { recipe_id: number; ingredient_id: number; quantity: string | null; unit: string | null };
        Insert: { recipe_id: number; ingredient_id: number; quantity?: string | null; unit?: string | null };
        Update: { quantity?: string | null; unit?: string | null };
        Relationships: [];
      };
      ratings: {
        Row: { id: number; user_id: string; recipe_id: number; score: number; created_at: string };
        Insert: { user_id: string; recipe_id: number; score: number };
        Update: { score?: number };
        Relationships: [];
      };
      comments: {
        Row: { id: number; author_id: string; recipe_id: number; parent_id: number | null; body: string; created_at: string };
        Insert: { author_id: string; recipe_id: number; parent_id?: number | null; body: string };
        Update: { body?: string };
        Relationships: [];
      };
      favorites: {
        Row: { user_id: string; recipe_id: number; created_at: string };
        Insert: { user_id: string; recipe_id: number };
        Update: never;
        Relationships: [];
      };
      follows: {
        Row: { follower_id: string; followee_id: string; created_at: string };
        Insert: { follower_id: string; followee_id: string };
        Update: never;
        Relationships: [];
      };
      notifications: {
        Row: { id: number; user_id: string; type: string; payload: Json; read: boolean; created_at: string };
        Insert: { user_id: string; type: string; payload?: Json };
        Update: { read?: boolean };
        Relationships: [];
      };
      reports: {
        Row: { id: number; reporter_id: string; target_type: 'recipe' | 'comment'; target_id: number; reason: string; status: string; created_at: string };
        Insert: { reporter_id: string; target_type: 'recipe' | 'comment'; target_id: number; reason: string };
        Update: { status?: string };
        Relationships: [];
      };
    };
    Views: Record<string, never>;
    Functions: {
      recipe_rating_stats: {
        Args: { p_recipe_id: number };
        Returns: { average: number; total: number }[];
      };
    };
    Enums: { recipe_difficulty: Difficulty; recipe_status: RecipeStatus };
    CompositeTypes: Record<string, never>;
  };
}
