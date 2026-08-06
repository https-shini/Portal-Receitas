import { useState } from 'react';
import { reportsService } from '../services/reports.service';
import { useAuth } from '../contexts/AuthContext';

interface Props {
  targetType: 'recipe' | 'comment';
  targetId: number;
}

/** Botão de denúncia com motivo. Só aparece para usuários autenticados. */
export function ReportButton({ targetType, targetId }: Props) {
  const { user } = useAuth();
  const [open, setOpen] = useState(false);
  const [reason, setReason] = useState('');
  const [sent, setSent] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!user) return null;
  if (sent) return <span className="report report--sent">Denúncia enviada. Obrigado.</span>;

  async function submit() {
    if (!reason.trim() || !user) return;
    try {
      await reportsService.create(user.id, targetType, targetId, reason.trim());
      setSent(true);
    } catch (e) {
      setError((e as Error).message);
    }
  }

  return (
    <div className="report">
      {open ? (
        <div className="report__box">
          <input
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="Motivo da denúncia"
            aria-label="Motivo da denúncia"
          />
          <button type="button" onClick={submit}>Enviar</button>
          <button type="button" onClick={() => setOpen(false)}>Cancelar</button>
          {error && <p className="error">{error}</p>}
        </div>
      ) : (
        <button type="button" className="report__trigger" onClick={() => setOpen(true)}>
          Denunciar
        </button>
      )}
    </div>
  );
}
