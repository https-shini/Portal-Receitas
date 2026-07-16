# Auditoria Técnica de Conformidade — LGPD e ISO/IEC 25010

**Projeto auditado:** Portal Receitas · HomeMadeGourmet (repositório `https-shini/Portal-Receitas`)
**Versão auditada:** `main` v2.1 (commit da série 2.x — Clean Architecture PHP 8.2, frontend Design System, banco oficial único)
**Tipo de documento:** Relatório oficial de auditoria técnica de conformidade
**Normas de referência primárias:** Lei nº 13.709/2018 (LGPD) · ISO/IEC 25010:2011
**Referências complementares:** OWASP Top 10 / ASVS · ISO/IEC 27001/27701 · NIST CSF · CIS Controls

---

## 1. Resumo Executivo

O Portal Receitas é uma aplicação web de catálogo de receitas com contas de usuário, originada de um TCC (ETEC de Vila Formosa, 2022) e consolidada em 2026 sobre Clean Architecture. A auditoria examinou **100% do código-fonte, banco de dados, infraestrutura como código, documentação e processos** visíveis no repositório.

**Conclusão geral:** o projeto apresenta **maturidade técnica alta para seu porte** — segurança de credenciais, minimização de dados, controle de acesso ao banco e qualidade de código estão acima do usual em projetos acadêmicos e atendem a boa parte dos requisitos técnicos da LGPD (art. 46–49) e das características da ISO/IEC 25010. As lacunas concentram-se na **dimensão documental/organizacional da LGPD** (transparência, direitos do titular, gestão de incidentes) — esperado, dado que o projeto nunca passou por um ciclo formal de privacidade.

| Dimensão | Situação | Síntese |
|---|---|---|
| LGPD — medidas técnicas de segurança (art. 46) | 🟢 Amplamente aderente | bcrypt, prepared statements, sessão endurecida, menor privilégio, auditoria |
| LGPD — transparência e direitos do titular (arts. 9, 18) | 🔴 Não aderente | sem Política de Privacidade, sem exclusão de conta, compartilhamento com terceiros não informado |
| LGPD — governança e prestação de contas (art. 50) | 🟡 Parcial | documentação técnica exemplar, mas sem registro de tratamento, DPIA ou plano de incidentes |
| ISO/IEC 25010 | 🟢 3,9/5 (média ponderada) | destaque em manutenibilidade, portabilidade e usabilidade; lacunas em confiabilidade operacional |

**Riscos críticos/altos identificados:** 4 (NC-01, NC-02, NC-03, R-07). Nenhum exige reescrita — todos são endereçáveis com o plano de adequação da seção 14, estimado em três ondas de esforço incremental.

---

## 2. Objetivos da Auditoria

1. Avaliar a aderência do tratamento de dados pessoais à LGPD;
2. Avaliar a qualidade do produto segundo as 8 características da ISO/IEC 25010;
3. Identificar riscos técnicos, legais e operacionais com base em evidências;
4. Avaliar governança, processos e maturidade do projeto;
5. Propor plano de adequação priorizado por risco × esforço.

## 3. Escopo da Análise

**Incluído (analisado integralmente):** código-fonte PHP (`src/`, `public/`, `config/`, `tests/`), frontend (views, CSS, JS), banco de dados (`DB_Receitas.sql` — schema, rotinas, triggers, DCL, seed), APIs (`public/api/`), infraestrutura como código (`Dockerfile`, `docker/`, `docker-compose.yml`, `render.yaml`), documentação (`README`, `DEPLOY`, `CHANGELOG`, `docs/`), histórico de mudanças (PRs #1–#6) e processos de build/teste/deploy.

**Fora do escopo / não validável a partir do repositório:** configuração efetiva do ambiente de produção na Render (TLS, retenção de logs da plataforma, contratos de subprocessamento), tráfego real, dados reais de titulares e comportamento sob carga em produção. Essas limitações estão explicitamente marcadas como **[NÃO VALIDADO]** ao longo do relatório.

## 4. Metodologia

- **Análise estática integral** do repositório (leitura de 100% dos arquivos de código e configuração);
- **Análise dinâmica** em ambiente reproduzido: execução via `docker compose`, exercício dos fluxos por HTTP e navegador real (login, cadastro, busca, perfil, logout), execução da suíte PHPUnit (20 testes) e dos autotestes de implantação do banco (seção 17 do `DB_Receitas.sql`);
- **Rastreamento de fluxo de dados** do formulário ao banco, logs e terceiros;
- **Mapeamento normativo** de cada constatação ao dispositivo LGPD e à (sub)característica ISO/IEC 25010 correspondente;
- **Classificação de risco** por matriz probabilidade × impacto (Baixo/Médio/Alto/Crítico).

Cada constatação cita o componente-evidência. Conclusões sem evidência não foram emitidas.

## 5. Visão Geral da Arquitetura

Descrita em profundidade em [backend.md](backend.md) e [frontend.md](frontend.md). Síntese para fins de auditoria:

- **Aplicação:** PHP 8.2, Clean Architecture (Domain/Application/Infrastructure/Presentation), zero dependências de produção; MPA server-rendered + API JSON de autenticação; sessão em cookie HttpOnly.
- **Dados:** MySQL 8/MariaDB, script único idempotente com constraints nomeadas, triggers de auditoria, RBAC de banco e autotestes.
- **Infraestrutura:** Docker (3 imagens), deploy na Render (modo free: all-in-one efêmero) ou compose/VPS (persistente).
- **Terceiros em runtime:** Google Fonts, CDN Icons8 (Line Awesome), YouTube (iframes de vídeo por receita).

---

## 6. Avaliação de Conformidade com a LGPD

### 6.1 Inventário e classificação de dados pessoais

**Fato observado** — dados tratados pelo sistema (fontes: `DB_Receitas.sql` §5.2/§5.4; `AuthController`; `SessionManager`):

| Dado | Local | Classificação | Ciclo de vida observado |
|---|---|---|---|
| Nome do usuário | `usuario.nomeUsuario`; sessão; auditoria implícita | Pessoal | coleta no cadastro → exibição no perfil → sem expiração |
| E-mail | `usuario.emailUsuario`; sessão; `auditoria_usuario.emailUsuario`; logs (`error_log`, sanitizado) | Pessoal (identificador direto) | coleta → autenticação/exibição → replicado em auditoria e logs → sem expiração |
| Senha | `usuario.senhaUsuario` | Credencial | recebida em claro via HTTPS → hash bcrypt imediato (`RegisterUserUseCase`) → nunca exibida/logada; CHECK do banco impede persistência de valor curto em claro |
| Categoria favorita | `usuario.idCategoriaFK` | Pessoal (preferência) | coleta no cadastro → não utilizada em decisões automatizadas |
| Endereço IP do visitante | logs do Apache; **enviado a Google/Icons8/YouTube** ao carregar fontes/ícones/vídeos | Pessoal (jurisprudência consolidada) | não controlado pela aplicação |
| Metadados de auditoria | `auditoria_usuario` (ação, data, executor, flag de troca de senha) | Pessoal associado | gerados por trigger → **sem política de retenção** |

**Fatos positivos:** não há dados sensíveis (art. 5º, II) nem dados de menores tratados de forma diferenciada; **minimização exemplar** — o cadastro coleta apenas 4 campos, todos necessários à funcionalidade (art. 6º, III ✅); a view `vw_usuario_publico` e a trilha de auditoria excluem deliberadamente o hash de senha.

**Não conformidade:** não existe **Registro das Operações de Tratamento** (art. 37) formalizado — este inventário, produzido pela auditoria, deve ser incorporado como documento vivo (→ NC-05).

### 6.2 Bases legais e finalidade

**Fato:** nenhuma base legal está documentada. **Análise da auditoria** (recomendação de enquadramento):

| Tratamento | Base legal recomendada | Observação |
|---|---|---|
| Conta de usuário (nome, e-mail, senha, categoria) | Execução de contrato (art. 7º, V) | finalidade: autenticação e personalização — adequada e necessária |
| Trilha de auditoria de contas | Legítimo interesse (art. 7º, IX) — segurança | requer **LIA** documentada (→ NC-05) |
| Logs de autenticação | Legítimo interesse — prevenção a fraude (art. 46) | reter por prazo definido |
| Compartilhamento de IP com Google/Icons8/YouTube | **Sem base definida hoje** | requer transparência + minimização (→ NC-03) |

### 6.3 Direitos do titular (art. 18)

| Direito | Situação | Evidência |
|---|---|---|
| Confirmação e acesso (I, II) | 🟡 Parcial | o titular vê nome/e-mail no perfil e via `GET /api/me.php`; não vê categoria nem trilha de auditoria |
| Correção (III) | 🟢 Atendido | edição de nome/e-mail/senha no perfil (`UpdateUserProfileUseCase`) |
| Anonimização/bloqueio/**eliminação** (IV, VI) | 🔴 **Não atendido** | **não existe exclusão de conta**: nenhum fluxo na aplicação executa DELETE em `usuario` (verificado por busca no código) e o usuário de banco da aplicação tem DELETE **negado** por design (`DB_Receitas.sql` §15) — a eliminação exige hoje intervenção manual do administrador (→ NC-02) |
| Portabilidade (V) | 🔴 Não atendido | sem exportação de dados (mitigante: são 4 campos) |
| Informação sobre compartilhamento (VII) | 🔴 Não atendido | terceiros (Google, Icons8, YouTube) não são informados ao titular (→ NC-03) |
| Revogação de consentimento (IX) | ○ N/A | tratamento não se baseia em consentimento (base contratual) — correto desde que documentado |

### 6.4 Segurança da informação (arts. 46–49)

| Controle | Situação | Evidência |
|---|---|---|
| Criptografia de credenciais | 🟢 | bcrypt `PASSWORD_DEFAULT` + `password_verify`; CHECK `ck_usuario_senha_hash` (≥60 chars) como defesa em profundidade |
| Criptografia em trânsito | 🟢/🟡 | HTTPS provido pela Render [NÃO VALIDADO em produção]; cookie `Secure` condicionado a HTTPS (`SessionManager::isHttps`, ciente de `X-Forwarded-Proto`); TLS opcional ao banco (`DB_SSL_CA`) |
| Criptografia em repouso | 🔴 | sem TDE/criptografia de volume (padrão MySQL/MariaDB) (→ R-06) |
| Injeção (SQLi) | 🟢 | 100% prepared statements, `EMULATE_PREPARES=false`; zero concatenação de entrada (verificado em `Pdo*Repository`) |
| XSS | 🟢 | saída escapada com `htmlspecialchars`/`json_encode`; JS usa `textContent` |
| Gestão de sessões | 🟢 | `HttpOnly`+`SameSite=Lax`(+`Secure`), `session_regenerate_id` pós-login (anti-fixation) |
| CSRF | 🟡 | `SameSite=Lax` + métodos restritos (405); sem token dedicado (→ R-08) |
| Enumeração de contas | 🟢 | mensagem 401 única para e-mail/senha incorretos (`AuthController::login`) |
| Força bruta | 🔴 | **sem rate limiting** no login/cadastro (→ R-07) |
| Privilégio mínimo / segregação | 🟢 | papéis `papel_leitura`/`papel_aplicacao`; usuário de aplicação sem DELETE/DDL; auditoria via trigger com DEFINER |
| Auditoria de alterações | 🟢 | triggers registram INSERT/UPDATE/DELETE de contas com autor e flag de troca de senha, sem expor hash |
| Log injection | 🟢 | sanitização de `\r\n` antes do log (`AuthController::sanitizeLog`) |
| Cabeçalhos de proteção (CSP, X-Frame-Options) | 🔴 | ausentes (→ R-08) |
| Vazamento por mensagens de erro | 🟢 | `display_errors` off; PDOException → 503 amigável sem stack trace |
| Segredos no repositório | 🟡 | sem credenciais de produção; porém senhas demo em claro documentadas no seed são aceitáveis **apenas** se os e-mails forem fictícios (→ NC-04) |

### 6.5 Incidentes e continuidade

**Fatos:** health check de vivacidade (`/healthz.php`) e degradação graciosa (503) existem; logs de autenticação existem. **Não existem:** monitoramento com alertas, processo documentado de resposta a incidentes, canal de comunicação a titulares/ANPD (art. 48), estratégia de backup no deploy free (dados efêmeros por design — documentado em `render.yaml`/`DEPLOY.md`, porém **não comunicado ao titular** dentro do produto). (→ NC-06, R-10, R-12)

### 6.6 Documentação obrigatória

| Documento | Existe? |
|---|---|
| Política de Privacidade | 🔴 Não (→ NC-01) |
| Termos de Uso | 🔴 Não (→ NC-01) |
| Registro de tratamento (art. 37) | 🔴 Não — base produzida nesta auditoria (§6.1) |
| DPIA | 🔴 Não — porte pequeno: DPIA simplificado é suficiente |
| LIA (auditoria/logs) | 🔴 Não |
| Política de retenção/descarte | 🔴 Não (→ R-11) |
| Encarregado (DPO) identificado | 🔴 Não — para projeto acadêmico, indicar responsável e canal de contato |

### 6.7 Princípios (art. 6º) e Privacy by Design/Default

| Princípio | Avaliação |
|---|---|
| Finalidade / Adequação / Necessidade | 🟢 dados mínimos, uso restrito à função |
| Livre acesso | 🟡 perfil + `/api/me`; falta visão completa |
| Qualidade dos dados | 🟢 edição pelo titular; validações; UNIQUE de e-mail |
| Transparência | 🔴 principal lacuna (NC-01/NC-03) |
| Segurança / Prevenção | 🟢 controles do §6.4; prevenção parcial (falta rate limiting/monitoramento) |
| Não discriminação | 🟢 nenhum tratamento discriminatório observado |
| Responsabilização | 🟡 evidências técnicas fortes (auditoria em banco, CHANGELOG), governança documental ausente |

**Privacy by Design:** parcialmente incorporado de fato — minimização, hash imediato, auditoria sem dados excessivos, menor privilégio. **Privacy by Default:** 🟢 nenhum dado é público por padrão; nenhum opt-out necessário pois não há tratamentos opcionais.

---

## 7. Avaliação de Conformidade com a ISO/IEC 25010

Escala de maturidade: 1 (inexistente) – 5 (exemplar para o porte). Evidências citadas por subcaracterística.

### 7.1 Adequação Funcional — **4,5**
| Sub | Nota | Evidência |
|---|---|---|
| Completude | 5 | RF01–RF09 (backend.md §1.2) todos implementados e testados E2E |
| Correção | 4 | 20 testes unit/integração + autotestes do banco; limitação conhecida documentada (quebra de passos por ".") |
| Pertinência | 5 | nenhuma função além do requisito; roadmap separado do implementado |

### 7.2 Eficiência de Desempenho — **3,5**
| Sub | Nota | Evidência |
|---|---|---|
| Comportamento temporal | 4 | consultas indexadas; 0 iframes no load (36 sob demanda); assets leves sem framework |
| Utilização de recursos | 4 | imagem free ajustada a 512 MB (buffer pool 64M) |
| Capacidade | 2 | **sem testes de carga** [NÃO VALIDADO]; sem cache de aplicação (roadmap definido) |

### 7.3 Compatibilidade — **4,0**
Interoperabilidade: API JSON com contrato documentado (4). Coexistência: MySQL 8 **e** MariaDB validados na mesma base de código (5). Navegadores: recursos com fallback documentado (frontend.md §18); matriz real além do Chromium [NÃO VALIDADO] (3).

### 7.4 Usabilidade — **4,5**
Evidências: heurísticas de Nielsen aplicadas e documentadas (frontend.md §7); estados vazios projetados; prevenção de erro (Salvar pós-Editar; confirmação de senha; medidor de força); acessibilidade WCAG 2.2 AA implementada (teclado, foco gerenciado, landmarks, contraste recalculado, `prefers-reduced-motion`); mobile 390px validado. Lacuna: auditoria automatizada de a11y (axe) ainda não roda em CI.

### 7.5 Confiabilidade — **3,0**
| Sub | Nota | Evidência |
|---|---|---|
| Maturidade | 4 | suíte de testes; validação E2E a cada PR (registrada nos PRs) |
| Disponibilidade | 3 | healthcheck + restart da plataforma; free tier hiberna (documentado) |
| Tolerância a falhas | 4 | degradação 503; ordem de subida por healthcheck |
| Recuperabilidade | 1 | **sem backups/restore testado** (free: efêmero por design) (→ R-10) |

### 7.6 Segurança — **3,5**
Confidencialidade 4 (hash, HttpOnly, menor privilégio); Integridade 5 (constraints, FKs, triggers, prepared statements); Não repúdio/Responsabilização 4 (auditoria com autor via `CURRENT_USER()`); Autenticidade 4 (sessão endurecida). Rebaixam a nota: ausência de rate limiting (R-07), de CSP (R-08) e de gestão contínua de vulnerabilidades (R-09).

### 7.7 Manutenibilidade — **4,5**
Modularidade 5 (camadas com regra de dependência; SRP evidente); Reusabilidade 4 (contratos de Domain; DS de componentes no front); Analisabilidade 5 (documentação interna reconstruída, docs oficiais, CHANGELOG); Modificabilidade 4; Testabilidade 4 (fakes in-memory; ponto fraco: cobertura formal não medida).

### 7.8 Portabilidade — **5,0**
Adaptabilidade 5 (12-factor; env-only; porta dinâmica); Instalabilidade 5 (um comando: compose ou blueprint; seed automático; validado em MySQL e MariaDB); Substituibilidade 4 (repositórios trocáveis por interface).

**Média ponderada geral: ≈ 3,9/5.**

---

## 8. Avaliação de Governança e Qualidade

| Prática | Situação | Evidência |
|---|---|---|
| Gestão de mudanças | 🟢 | trunk-based com PRs descritivos (#1–#6), rebase-merge, histórico linear |
| Gestão de configuração | 🟢 | tudo como código (IaC), `.env.example`, `composer.lock` versionado |
| Rastreabilidade | 🟢 | requisito → classe (backend.md §1.2); CHANGELOG semântico com racional |
| Revisão de código | 🟡 | PRs existem, mas aprovação formal de revisor não é exigida [processo de 1 mantenedor] |
| CI | 🔴 | **não há pipeline automatizado** — testes rodam manualmente a cada entrega (→ R-13) |
| CD | 🟢 | auto-deploy Render a partir da `main` com healthcheck |
| Estratégia de testes | 🟡 | pirâmide definida (backend.md §13); faltam contrato e carga |
| Observabilidade | 🟡 | health + logs; sem métricas/alertas (→ R-09) |
| Documentação técnica/funcional | 🟢 | backend.md, frontend.md, architecture.md, DEPLOY.md, README, documentação interna reconstruída |
| Métricas/KPIs | 🔴 | não definidos — propostos na seção 17 |

## 9. Avaliação de Segurança (consolidada)

Coberta em §6.4 sob a ótica LGPD e §7.6 sob a ótica ISO. Mapeamento OWASP Top 10 completo em [backend.md §8]. Achados exclusivos desta auditoria: **NC-03** (vazamento de IP a terceiros sem transparência), **NC-04** (e-mails de aparência real no seed público) e **R-11** (retenção indefinida da trilha de auditoria).

---

## 10. Avaliação de Riscos

Matriz probabilidade × impacto. Formato: cada risco/não conformidade traz descrição, evidência, causa, impactos, classificação, dispositivo relacionado e recomendação.

### Não conformidades (LGPD)

**NC-01 — Ausência de Política de Privacidade e Termos de Uso** · **Risco: CRÍTICO (legal)**
Evidência: nenhuma view/rota/documento de privacidade no repositório. Causa: projeto nunca passou por ciclo de privacidade. Impacto legal: viola transparência (art. 6º, VI; art. 9º); impede informar compartilhamentos e retenção. Probabilidade de questionamento: média; severidade: alta. Recomendação: publicar página de Política de Privacidade + Termos linkados no cadastro (checkbox de ciência — não é consentimento; a base é contratual) e no rodapé. Prioridade: **P0**.

**NC-02 — Titular não consegue eliminar a própria conta (art. 18, VI)** · **Risco: ALTO**
Evidência: ausência de qualquer fluxo de DELETE em `usuario` na aplicação; `portal_app` tem DELETE negado (`DB_Receitas.sql` §15 — verificado também em teste dinâmico). Causa: escopo original do TCC. Impacto: direito do titular inexequível sem operador. Recomendação: endpoint autenticado `POST /api/delete-account.php` + confirmação na UI do perfil; conceder DELETE pontual em `usuario` ao papel da aplicação **ou** procedure dedicada (`sp_excluir_conta`) mantendo menor privilégio; trigger de auditoria já registra a exclusão. Prioridade: **P0**.

**NC-03 — Compartilhamento de dados (IP) com terceiros sem transparência** · **Risco: ALTO**
Evidência: views carregam `fonts.googleapis.com`, `fonts.gstatic.com`, `maxst.icons8.com`; receitas embutem iframes `youtube.com` (coluna `link`). Todo visitante tem IP/user-agent enviados a esses domínios. Impacto legal: art. 18, VII + transparência; jurisprudência europeia análoga (Google Fonts) reforça o risco. Recomendação combinada: (a) declarar na Política de Privacidade; (b) **self-host de fontes/ícones** (já é roadmap do frontend — elimina 2 terceiros); (c) trocar embeds para **`youtube-nocookie.com`** e/ou fachada de clique (carrega o player só após ação do usuário). Prioridade: **P1**.

**NC-04 — E-mails de aparência real com senhas documentadas no seed público** · **Risco: MÉDIO**
Evidência: `DB_Receitas.sql` semeia `kk.123@gmail.com` e `tectutors.123@gmail.com` com senhas em claro em comentário. Se forem endereços reais de terceiros, constitui exposição de dado pessoal em repositório público (e reutilização de senha potencial). Recomendação: substituir por domínio reservado (`demo1@example.com`, `demo2@example.com` — RFC 2606) mantendo os hashes/senhas demo. Prioridade: **P1**.

**NC-05 — Ausência de registro de tratamento, LIA e DPIA simplificado (arts. 37, 7º IX)** · **Risco: MÉDIO**
Recomendação: incorporar o §6.1 como `docs/privacidade/registro-tratamento.md` + LIA de auditoria/logs (1 página cada). Prioridade: **P2**.

**NC-06 — Sem processo de resposta a incidentes (art. 48)** · **Risco: MÉDIO**
Recomendação: runbook de 1 página (detecção → contenção → avaliação de dano → comunicação a titulares/ANPD quando aplicável → post-mortem) + alerta mínimo (falha de healthcheck e pico de 401). Prioridade: **P2**.

### Riscos técnicos

| ID | Risco | Evidência | Prob. | Impacto | Nível | Recomendação | Prio |
|----|-------|-----------|-------|---------|-------|--------------|------|
| R-07 | Força bruta no login (sem rate limiting) | endpoints de auth sem limitação; logs registram mas não bloqueiam | Alta | Médio | **ALTO** | limitar tentativas por IP/conta (tabela de tentativas ou proteção da plataforma); já é item nº 1 do roadmap backend | P1 |
| R-08 | Ausência de CSP/X-Frame-Options/anti-CSRF token | headers não configurados no Apache/entrypoints | Média | Médio | **MÉDIO** | headers no vhost (CSP permitindo apenas YouTube/fonts em uso), `X-Frame-Options: DENY`, token CSRF no form do perfil | P1 |
| R-06 | Sem criptografia em repouso | volumes MySQL sem TDE | Baixa | Médio | MÉDIO | criptografia de disco no provedor quando houver dados reais; documentar aceite enquanto demo | P3 |
| R-09 | Sem monitoramento/alertas/gestão de vulnerabilidades | nenhuma métrica/alerta; sem processo de atualização de imagens base | Média | Médio | MÉDIO | alertas da plataforma + rebuild mensal das imagens (patch de base) + `composer audit` no CI | P2 |
| R-10 | Sem backup/restore (ambientes persistentes) | compose/VPS sem rotina de dump | Média | Alto | **ALTO** (produção persistente) / aceito (demo free) | `mysqldump` diário + teste de restore documentado | P2 |
| R-11 | Retenção indefinida de dados pessoais em auditoria/logs | `auditoria_usuario` sem expurgo; logs de plataforma [NÃO VALIDADO] | Média | Baixo | MÉDIO | política de retenção (ex.: auditoria 12 meses; logs 90 dias) + evento de expurgo agendado | P2 |
| R-12 | Perda silenciosa de dados no free tier | dados efêmeros por design (render.yaml) sem aviso ao titular no produto | Alta (no free) | Baixo | MÉDIO | aviso na tela de cadastro do ambiente demo ("dados de demonstração, apagados periodicamente") | P1 |
| R-13 | Regressões por ausência de CI | testes executados manualmente | Média | Médio | MÉDIO | GitHub Actions: lint + phpunit + build das imagens (proposta pronta em backend.md §14.2) | P1 |
| R-14 | Dependência de 1 mantenedor / revisão não obrigatória | histórico de PRs | Média | Baixo | BAIXO | branch protection na `main` (PR obrigatório + checks verdes) | P3 |

---

## 11. Não Conformidades Identificadas (inventário)

NC-01 a NC-06 (seção 10) — 2 críticas/altas de natureza legal (NC-01, NC-02), 1 alta técnico-legal (NC-03), 3 médias documentais (NC-04, NC-05, NC-06).

## 12. Oportunidades de Melhoria

Além das correções: cache de catálogo (APCu) e headers de cache de assets (desempenho); testes de contrato via OpenAPI; testes de carga k6 com metas (p95 < 300 ms); axe-core no E2E; JSON-LD `Recipe` (SEO); entidades tipadas no Domain; sessões em Redis para escala horizontal — todos já priorizados nos roadmaps de [backend.md §18] e [frontend.md §22], que esta auditoria referenda.

## 13. Recomendações Técnicas (consolidadas por prioridade)

**P0 (imediato):** Política de Privacidade + Termos (NC-01) · exclusão de conta pelo titular (NC-02).
**P1 (curto prazo):** rate limiting (R-07) · transparência/minimização de terceiros: self-host de fontes/ícones + youtube-nocookie (NC-03) · seed com e-mails RFC 2606 (NC-04) · headers CSP/XFO + token CSRF (R-08) · aviso de ambiente demo (R-12) · CI (R-13).
**P2 (médio prazo):** registro de tratamento + LIA + DPIA simplificado (NC-05) · runbook de incidentes + alertas (NC-06, R-09) · política de retenção + expurgo (R-11) · backups testados p/ ambientes persistentes (R-10).
**P3 (contínuo):** criptografia em repouso quando houver dados reais (R-06) · branch protection (R-14) · demais itens dos roadmaps técnicos.

## 14. Plano de Adequação

| Onda | Conteúdo | Resultado esperado |
|------|----------|--------------------|
| **Onda 1 — Direitos e transparência** (P0) | Política de Privacidade + Termos publicados e linkados; endpoint e UI de exclusão de conta (com procedure dedicada preservando menor privilégio) | LGPD: transparência e art. 18 exequíveis — elimina os 2 riscos legais críticos |
| **Onda 2 — Endurecimento e prevenção** (P1) | rate limiting; CSP/XFO; token CSRF; self-host fontes/ícones; youtube-nocookie; seed example.com; aviso demo; GitHub Actions | Segurança preventiva completa (OWASP), terceiros minimizados, qualidade contínua |
| **Onda 3 — Governança de privacidade** (P2/P3) | registro de tratamento, LIA, DPIA simplificado, runbook de incidentes, retenção/expurgo, backups testados, alertas | Prestação de contas (art. 50) demonstrável; confiabilidade operacional ≥ 4 |

## 15. Roadmaps de Implementação

- **LGPD:** Onda 1 → 2 → 3 acima (ordem obrigatória: direitos antes de governança).
- **ISO/IEC 25010:** Confiabilidade 3→4 (backups+alertas, Onda 3); Segurança 3,5→4,5 (Onda 2); Desempenho 3,5→4 (cache+carga, roadmap backend); demais características mantidas por CI/branch protection.
- **Arquitetural:** permanece o roadmap oficial de backend.md §18 e frontend.md §22 — esta auditoria não identificou necessidade de mudança estrutural.

## 16. Checklists de Conformidade

### LGPD (estado atual → alvo pós-plano)
- [ ] Política de Privacidade publicada → ✅ Onda 1
- [ ] Termos de Uso publicados → ✅ Onda 1
- [ ] Exclusão de conta pelo titular → ✅ Onda 1
- [x] Correção/atualização pelo titular
- [x] Minimização de dados na coleta
- [x] Credenciais protegidas (bcrypt + defesa em profundidade)
- [x] Controle de acesso com menor privilégio (banco)
- [x] Auditoria de alterações de contas
- [ ] Transparência sobre terceiros → ✅ Onda 1/2
- [ ] Registro de tratamento / LIA / DPIA → ✅ Onda 3
- [ ] Política de retenção e expurgo → ✅ Onda 3
- [ ] Runbook de incidentes (art. 48) → ✅ Onda 3

### ISO/IEC 25010 (síntese)
- [x] Funcional: requisitos rastreáveis e testados
- [x] Usabilidade + acessibilidade AA
- [x] Manutenibilidade: camadas, docs, testes
- [x] Portabilidade: 12-factor, dois SGBDs, um comando
- [ ] Confiabilidade: backups + alertas → Onda 3
- [ ] Segurança: rate limiting + CSP → Onda 2
- [ ] Desempenho: teste de carga + cache → roadmap técnico

## 17. Métricas e Indicadores (propostos)

| Indicador | Meta | Fonte |
|---|---|---|
| Taxa de sucesso do CI na `main` | 100% | GitHub Actions |
| Cobertura de testes (linhas, Application/Domain) | ≥ 80% | phpunit --coverage |
| p95 das rotas de leitura (100 VUs) | < 300 ms | k6 |
| Lighthouse (perf/a11y/BP/SEO) | ≥ 95 | Lighthouse CI |
| Tentativas de login bloqueadas vs. total de falhas | acompanhamento | logs estruturados |
| Solicitações de titulares atendidas no prazo | 100% ≤ 15 dias | registro manual |
| Idade máxima de dado em auditoria | ≤ política definida | consulta agendada |
| Tempo de restauração de backup (RTO) testado | ≤ 1 h | ensaio semestral |

## 18. Critérios de Aceitação do Plano

1. Página de privacidade acessível de todas as telas; cadastro exibe ciência dos termos;
2. Usuária/o autenticado exclui a própria conta pela UI; a linha some de `usuario` e a auditoria registra o DELETE;
3. 6ª tentativa de login incorreta em 1 minuto é bloqueada com resposta 429;
4. Resposta da home contém cabeçalho CSP válido; página não pode ser emoldurada;
5. Nenhuma requisição de runtime a `fonts.googleapis.com`/`gstatic`/`icons8` (self-host) e embeds servidos por `youtube-nocookie.com`;
6. Seed sem endereços de e-mail fora de `example.com`;
7. Pipeline CI verde obrigatório para merge na `main`;
8. Documentos de privacidade (registro, LIA, DPIA, retenção, runbook) versionados em `docs/privacidade/`.

## 19. Conclusão

O Portal Receitas chega a esta auditoria com **fundação técnica sólida e acima da média para o porte**: as medidas de segurança exigidas pelo art. 46 da LGPD estão majoritariamente implementadas e a qualidade de produto (ISO/IEC 25010) é alta nas dimensões estruturais (manutenibilidade, portabilidade, usabilidade, adequação funcional). As não conformidades relevantes são **concentradas e endereçáveis**: nenhuma demanda reescrita; as duas críticas (transparência documental e direito de eliminação) resolvem-se na Onda 1 com esforço pequeno. Executado o plano de adequação, o projeto atinge conformidade material com a LGPD para seu contexto e maturidade ISO estimada ≥ 4,3/5.

**Recomendações para auditorias futuras:** reauditar após a Onda 2 (verificação dos critérios da seção 18); auditoria anual de dependências e imagens base; reexecutar esta análise a cada mudança de escopo de dados (ex.: favoritos/avaliações do roadmap, que criarão novos dados comportamentais e exigirão atualização do registro de tratamento).

## 20. Anexos Técnicos

- **A. Inventário de dados** — §6.1 (tabela completa com locais e ciclo de vida);
- **B. Matriz de riscos** — §10;
- **C. Referências cruzadas:** [backend.md](backend.md) (ADRs 001–007, OWASP §8, roadmap §18) · [frontend.md](frontend.md) (WCAG §9, ADRs F01–F06) · [`DB_Receitas.sql`](../DB_Receitas.sql) (DCL §15, auditoria §12, autotestes §17) · [DEPLOY.md](../DEPLOY.md) (topologias e trade-off do free tier);
- **D. Limitações da auditoria:** itens [NÃO VALIDADO] — configuração de produção da Render, retenção de logs da plataforma, matriz completa de navegadores, comportamento sob carga real.

---

*Relatório produzido por análise estática e dinâmica integral do repositório. Este documento deve ser revisado a cada release que altere o tratamento de dados pessoais.*
