# Runbook de Resposta a Incidentes de Segurança/Privacidade (LGPD, art. 48)

**Versão:** 1.0 (15/07/2026) · **Responsável:** Guilherme Cruz · guilhermedesouzacruz80@gmail.com

**1. Detecção** — sinais: healthcheck falhando, pico de 401/429 nos logs (`docker compose logs web | grep "\[auth\]"`), alerta da plataforma, relato de usuário.
**2. Contenção (primeira hora)** — se comprometimento de credenciais/infra: colocar o serviço em manutenção (suspender na Render), revogar/trocar segredos (`DB_PASS`, contas do banco), preservar logs (exportar antes de reiniciar).
**3. Avaliação** — o que foi acessado? Há dado pessoal envolvido (consultar registro de tratamento)? Qual o risco ao titular (dano relevante = e-mails expostos, hashes exfiltrados)?
**4. Erradicação e recuperação** — corrigir a causa (patch/config), redeploy de imagem limpa, reimportar seed/backup íntegro, forçar novo login de todos (invalidar sessões).
**5. Comunicação** — risco relevante a titulares: comunicar ANPD e titulares em prazo razoável (art. 48), informando dados afetados, medidas tomadas e recomendações (ex.: trocar senha reutilizada).
**6. Pós-incidente** — post-mortem em `docs/privacidade/incidentes/AAAA-MM-DD.md`: linha do tempo, causa raiz, ações preventivas; atualizar auditoria de conformidade.

**Contatos:** ANPD — https://www.gov.br/anpd · Plataforma (Render) — dashboard/status.
