import { Link } from 'react-router-dom';
import { useNotifications } from '../hooks/useNotifications';

/** Sino com contador de não-lidas, atualizado ao vivo. */
export function NotificationBell() {
  const { unreadCount } = useNotifications();
  return (
    <Link to="/notificacoes" className="notif-bell" aria-label={`Notificações (${unreadCount} não lidas)`}>
      🔔
      {unreadCount > 0 && <span className="notif-bell__badge">{unreadCount > 9 ? '9+' : unreadCount}</span>}
    </Link>
  );
}
