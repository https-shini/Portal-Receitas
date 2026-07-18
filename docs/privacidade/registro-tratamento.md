# Registro das Operações de Tratamento (LGPD, art. 37)

**Controlador/Responsável:** Guilherme Cruz · receitasdelicia498@gmail.com
**Sistema:** Portal Receitas · HomeMadeGourmet · **Versão do registro:** 1.0 (15/07/2026)

| # | Operação | Dados | Titulares | Finalidade | Base legal | Retenção | Compartilhamento |
|---|----------|-------|-----------|------------|------------|----------|------------------|
| 1 | Cadastro e manutenção de conta | nome, e-mail, hash de senha, categoria favorita | usuários do portal | autenticação e personalização | Execução de contrato (art. 7º, V) | enquanto a conta existir; eliminação por anonimização a pedido | nenhum |
| 2 | Autenticação (login) | e-mail; IP (efêmero, p/ rate limiting) | usuários | acesso à conta; prevenção a força bruta | Execução de contrato; legítimo interesse (segurança) | IP: janela de 60s em arquivo temporário | nenhum |
| 3 | Trilha de auditoria de contas | id, e-mail, ação, data, flag de troca de senha | usuários | segurança e prestação de contas | Legítimo interesse (art. 7º, IX) — ver LIA | 12 meses (expurgo: `sp_expurgar_auditoria(365)`) | nenhum |
| 4 | Logs de autenticação | e-mail (sanitizado), resultado | usuários | detecção de abuso | Legítimo interesse | 90 dias (rotação da plataforma) | operador de hospedagem (Render) |
| 5 | Exibição de vídeo (opcional, por clique) | IP, user-agent | visitantes que clicam em "Carregar vídeo" | exibir o vídeo da receita | Consentimento por ação (carregamento sob demanda) | conforme política do Google | Google/YouTube (youtube-nocookie.com) |

**Observações:** fontes e ícones são self-hosted (nenhum dado vai a CDNs); dados sensíveis não são tratados; não há decisões automatizadas; ambiente de demonstração reinicia dados periodicamente (informado no cadastro e na Política de Privacidade).

*Atualizar este registro a cada nova operação de tratamento (ex.: favoritos/avaliações do roadmap).*
