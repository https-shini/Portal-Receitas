-- ─────────────────────────────────────────────────────────────
-- seed.sql — dados de referência (categorias e ingredientes).
-- Receitas dependem de auth.users, então são criadas pela app após
-- o primeiro cadastro (ou por um seed de e2e com usuário de teste).
-- ─────────────────────────────────────────────────────────────

insert into public.categories (name, slug) values
  ('Massas', 'massas'),
  ('Carnes', 'carnes'),
  ('Doces', 'doces'),
  ('Salgados', 'salgados'),
  ('Frutos do Mar', 'frutos-do-mar'),
  ('Veganas', 'veganas'),
  ('Bebidas', 'bebidas'),
  ('Saladas', 'saladas')
on conflict (name) do nothing;

insert into public.ingredients (name) values
  ('Macarrão'), ('Bacon'), ('Ovo'), ('Queijo parmesão'), ('Alho'),
  ('Cebola'), ('Tomate'), ('Chocolate'), ('Açúcar'), ('Farinha de trigo'),
  ('Leite'), ('Manteiga'), ('Sal'), ('Pimenta-do-reino'), ('Azeite')
on conflict (name) do nothing;
