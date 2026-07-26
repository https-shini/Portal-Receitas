import { useState, type FormEvent } from 'react';
import { useComments } from '../hooks/useComments';
import { useAuth } from '../contexts/AuthContext';

export function CommentThread({ recipeId }: { recipeId: number }) {
  const { user } = useAuth();
  const { comments, loading, add, remove, canComment } = useComments(recipeId);
  const [body, setBody] = useState('');

  function submit(e: FormEvent) {
    e.preventDefault();
    const text = body.trim();
    if (!text) return;
    add.mutate(text, { onSuccess: () => setBody('') });
  }

  return (
    <section className="comments" aria-label="Comentários">
      <h2>Comentários</h2>

      {loading ? (
        <p>Carregando…</p>
      ) : (
        <ul className="comments__list">
          {comments.map((c) => (
            <li key={c.id} className="comment">
              <strong>{c.author?.display_name ?? 'Chef'}</strong>
              <span className="comment__date">{new Date(c.created_at).toLocaleString('pt-BR')}</span>
              <p>{c.body}</p>
              {user?.id === c.author_id && (
                <button type="button" onClick={() => remove.mutate(c.id)}>Excluir</button>
              )}
            </li>
          ))}
          {comments.length === 0 && <li>Seja o primeiro a comentar.</li>}
        </ul>
      )}

      {canComment ? (
        <form onSubmit={submit} className="comments__form">
          <textarea
            value={body}
            onChange={(e) => setBody(e.target.value)}
            placeholder="Escreva um comentário…"
            maxLength={2000}
            required
          />
          <button type="submit" disabled={add.isPending}>Comentar</button>
          {add.isError && <p className="error">{(add.error as Error).message}</p>}
        </form>
      ) : (
        <p>Entre para comentar.</p>
      )}
    </section>
  );
}
