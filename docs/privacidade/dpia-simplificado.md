# DPIA Simplificado — Relatório de Impacto (LGPD, art. 38)

**Sistema:** Portal Receitas · **Versão:** 1.0 (15/07/2026)

**1. Descrição do tratamento:** conta de usuário (4 campos), autenticação por sessão, auditoria de alterações; catálogo público sem dados pessoais.
**2. Necessidade/proporcionalidade:** coleta mínima comprovada (nenhum campo além da função); sem dados sensíveis, sem menores como público-alvo específico, sem decisões automatizadas — **risco intrínseco baixo**.
**3. Riscos aos titulares e mitigações:**
| Risco | Mitigação |
|---|---|
| Vazamento de credenciais | hash bcrypt + CHECK no banco; TLS; menor privilégio |
| Acesso indevido à conta | rate limiting, sessão HttpOnly/SameSite, regeneração de id, mensagens anti-enumeração |
| Uso além da finalidade | minimização; registro de tratamento; sem compartilhamento comercial |
| Exposição por terceiros | fontes/ícones self-hosted; vídeo só com clique (youtube-nocookie) |
| Perda de dados (demo) | informado no produto e na política; seed restaura o catálogo |
**4. Parecer:** tratamento de baixo risco; aprovado condicionado à manutenção das salvaguardas e à revisão deste DPIA a cada mudança no escopo de dados.
