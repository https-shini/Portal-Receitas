import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { reportsService } from '../services/reports.service';

/**
 * Fila de moderação. A RLS só retorna denúncias para moderadores (ou as do
 * próprio repórter); um usuário comum verá a lista vazia. O acesso "de
 * verdade" é definido pela claim app_metadata.role = 'moderator' no JWT.
 */
export default function Moderation() {
  const qc = useQueryClient();
  const { data, isLoading } = useQuery({ queryKey: ['reports'], queryFn: () => reportsService.listOpen() });

  const setStatus = useMutation({
    mutationFn: ({ id, status }: { id: number; status: 'reviewing' | 'closed' }) =>
      reportsService.setStatus(id, status),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['reports'] }),
  });

  return (
    <section>
      <h1>Moderação de denúncias</h1>
      {isLoading ? (
        <p>Carregando…</p>
      ) : (
        <table className="mod-table">
          <thead>
            <tr><th>Alvo</th><th>Motivo</th><th>Status</th><th>Ações</th></tr>
          </thead>
          <tbody>
            {(data ?? []).map((r) => (
              <tr key={r.id}>
                <td>{r.target_type} #{r.target_id}</td>
                <td>{r.reason}</td>
                <td>{r.status}</td>
                <td>
                  <button type="button" onClick={() => setStatus.mutate({ id: r.id, status: 'reviewing' })}>Em análise</button>
                  <button type="button" onClick={() => setStatus.mutate({ id: r.id, status: 'closed' })}>Fechar</button>
                </td>
              </tr>
            ))}
            {(data ?? []).length === 0 && <tr><td colSpan={4}>Sem denúncias abertas.</td></tr>}
          </tbody>
        </table>
      )}
    </section>
  );
}
