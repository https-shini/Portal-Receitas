# Política de Retenção e Descarte (LGPD, arts. 15–16)

**Versão:** 1.0 (15/07/2026)

| Dado | Retenção | Descarte |
|---|---|---|
| Conta de usuário | Enquanto a conta existir | Anonimização irreversível a pedido do titular (perfil → Excluir conta) ou por inatividade prolongada (avaliar em produção) |
| Trilha de auditoria (`auditoria_usuario`) | 12 meses | `CALL sp_expurgar_auditoria(365);` — agendar via EVENT do MySQL ou cron mensal |
| Logs de autenticação/servidor | 90 dias | rotação de logs da plataforma |
| Rate limiting (IP+e-mail) | 60 segundos (janela) | sobrescrita automática; arquivos temporários |
| Backups (ambientes persistentes) | 30 dias | expiração do ciclo de backup; backups herdam esta política |

**Descarte seguro:** anonimização usa substituição irreversível (sem tabela de/para); expurgo usa DELETE definitivo. Em ambiente free (efêmero) o descarte ocorre por design a cada reinício.
