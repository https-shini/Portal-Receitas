# LIA — Avaliação de Legítimo Interesse (LGPD, art. 7º, IX e art. 10)

**Operações avaliadas:** trilha de auditoria de contas (`auditoria_usuario`) e logs de autenticação. · **Versão:** 1.0 (15/07/2026)

**1. Finalidade (interesse legítimo):** segurança da informação — detectar acesso indevido, apurar incidentes e prestar contas de alterações em contas (quem/quando/o quê), conforme art. 46.
**2. Necessidade:** dados mínimos para a finalidade — identificador, e-mail, tipo de ação e data; **o hash de senha nunca é registrado**; IPs não são persistidos na auditoria.
**3. Balanceamento:** expectativa razoável do titular (registros de segurança são prática padrão); impacto baixo (dados já fornecidos pelo titular, sem categorias sensíveis); acesso restrito ao operador do banco (papéis de menor privilégio).
**4. Salvaguardas:** retenção limitada a 12 meses com expurgo programado; anonimização da conta não apaga a trilha retroativamente, mas o vínculo expira com o expurgo; transparência na Política de Privacidade (§2 e §5).
**Conclusão:** o legítimo interesse é adequado como base legal para as operações 3 e 4 do registro de tratamento, mantidas as salvaguardas acima.
