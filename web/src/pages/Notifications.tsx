import { useEffect } from 'react';
import { useNotifications } from '../hooks/useNotifications';

const LABEL: Record<string, string> = {
  new_follower: 'Você tem um novo seguidor',
  new_comment: 'Novo comentário na sua receita',
  new_rating: 'Sua receita recebeu uma avaliação',
};

export default function Notifications() {
  const { notifications, markAllRead } = useNotifications();

  // Ao abrir a página, marca tudo como lido.
  useEffect(() => {
    markAllRead();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <section>
      <h1>Notificações</h1>
      <ul className="notif-list">
        {notifications.map((n) => (
          <li key={n.id} className={n.read ? 'notif' : 'notif notif--unread'}>
            <span>{LABEL[n.type] ?? n.type}</span>
            <time>{new Date(n.created_at).toLocaleString('pt-BR')}</time>
          </li>
        ))}
        {notifications.length === 0 && <li>Nada por aqui ainda.</li>}
      </ul>
    </section>
  );
}
