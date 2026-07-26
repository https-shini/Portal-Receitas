import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { recipesService } from '../services/recipes.service';
import { storageService } from '../services/storage.service';
import type { Difficulty } from '../types';

export default function NewRecipe() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [difficulty, setDifficulty] = useState<Difficulty>('medio');
  const [prep, setPrep] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  async function submit(e: FormEvent) {
    e.preventDefault();
    if (!user) return;
    setSaving(true);
    setError(null);
    try {
      const imagePath = file ? await storageService.uploadRecipeImage(user.id, file) : null;
      const recipe = await recipesService.create(user.id, {
        title,
        description,
        difficulty,
        prepMinutes: prep ? Number(prep) : null,
        categoryId: null,
        imagePath,
        ingredients: [],
      });
      navigate(`/receita/${recipe.slug}`);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setSaving(false);
    }
  }

  return (
    <section>
      <h1>Publicar receita</h1>
      <form onSubmit={submit} className="recipe-form">
        <label>Título<input value={title} onChange={(e) => setTitle(e.target.value)} minLength={3} required /></label>
        <label>Descrição / modo de preparo<textarea value={description} onChange={(e) => setDescription(e.target.value)} rows={6} /></label>
        <label>Dificuldade
          <select value={difficulty} onChange={(e) => setDifficulty(e.target.value as Difficulty)}>
            <option value="facil">Fácil</option>
            <option value="medio">Médio</option>
            <option value="dificil">Difícil</option>
          </select>
        </label>
        <label>Tempo de preparo (min)<input type="number" min={0} value={prep} onChange={(e) => setPrep(e.target.value)} /></label>
        <label>Foto<input type="file" accept="image/*" onChange={(e) => setFile(e.target.files?.[0] ?? null)} /></label>
        {error && <p className="error">{error}</p>}
        <button type="submit" disabled={saving}>{saving ? 'Publicando…' : 'Publicar'}</button>
      </form>
    </section>
  );
}
