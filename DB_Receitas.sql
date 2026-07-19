-- ═══════════════════════════════════════════════════════════════════════════
--  PORTAL RECEITAS · HOMEMADE GOURMET — BANCO DE DADOS OFICIAL
-- ═══════════════════════════════════════════════════════════════════════════
--  Script único, autocontido e idempotente — fonte única do banco de dados
--  do projeto (schema, rotinas, seed, controle de acesso e autotestes).
--
--  SGBDs suportados : MySQL 8.x e MariaDB 10.5+ (strict mode habilitado)
--  Charset/Collation: utf8mb4 / utf8mb4_unicode_ci
--  Aplicação        : PHP 8.2 (PDO + prepared statements) — Clean Architecture
--
--  ORGANIZAÇÃO DO SCRIPT
--    1.  Configuração inicial
--    2.  Remoção de objetos existentes
--    3.  Criação do banco de dados
--    4.  Schemas
--    5.  Criação das tabelas (DDL)
--    6.  Constraints (integridade referencial e regras de domínio)
--    7.  Índices e otimização
--    8.  Views
--    9.  Functions
--    10. Stored Procedures
--    11. Packages (nota de compatibilidade)
--    12. Triggers (validação e auditoria)
--    13. Seed — dados iniciais (DML)
--    14. Consultas de exemplo (JOINs, subconsultas, CTEs, janelas, PREPARE)
--    15. Controle de acesso (DCL — usuários, papéis, GRANT/REVOKE)
--    16. Transações e concorrência (TCL — COMMIT/ROLLBACK/SAVEPOINT, isolamento)
--    17. Testes e validações
--
--  IMPORTANTE — contrato com a aplicação PHP (não renomear):
--    · categoria(idCategoria, nomeCategoria)
--    · usuario(idUsuario, nomeUsuario, emailUsuario, senhaUsuario, idCategoriaFK)
--    · receita(idReceita, nomeReceita, porcoes, tempoReceita, qtdCalorias, link,
--              ingrediente_1..ingrediente_15, modoPreparo, idcategoriaFK, imagem)
-- ═══════════════════════════════════════════════════════════════════════════


-- ═══════════════════════════════════════════════════════════════════════════
--  1. CONFIGURAÇÃO INICIAL
-- ═══════════════════════════════════════════════════════════════════════════
--  Charset da sessão e fuso horário neutro. O modo estrito do SGBD permanece
--  habilitado (as regras de integridade abaixo assumem strict mode).

SET NAMES utf8mb4;
SET time_zone = '+00:00';


-- ═══════════════════════════════════════════════════════════════════════════
--  2. REMOÇÃO DE OBJETOS EXISTENTES
-- ═══════════════════════════════════════════════════════════════════════════
--  Torna o script idempotente: pode ser reexecutado em qualquer ambiente de
--  desenvolvimento/homologação sem resíduos de versões anteriores.
--  (Em produção, prefira migrações incrementais a recriar o banco.)

DROP DATABASE IF EXISTS tcc_receitas;


-- ═══════════════════════════════════════════════════════════════════════════
--  3. CRIAÇÃO DO BANCO DE DADOS
-- ═══════════════════════════════════════════════════════════════════════════

CREATE DATABASE tcc_receitas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tcc_receitas;


-- ═══════════════════════════════════════════════════════════════════════════
--  4. SCHEMAS
-- ═══════════════════════════════════════════════════════════════════════════
--  No MySQL/MariaDB, SCHEMA é sinônimo de DATABASE — o banco `tcc_receitas`
--  criado acima já é o schema da aplicação. Em SGBDs com schemas nomeados
--  (PostgreSQL, Oracle), as tabelas abaixo residiriam num schema `portal`.


-- ═══════════════════════════════════════════════════════════════════════════
--  5. CRIAÇÃO DAS TABELAS (DDL)
-- ═══════════════════════════════════════════════════════════════════════════
--  Convenções adotadas:
--    · Tabelas no singular, em minúsculas (contrato herdado pela aplicação);
--    · Constraints nomeadas: pk_ / fk_ / uq_ / ck_  +  tabela  +  papel;
--    · Índices nomeados:     idx_ / ftx_            +  tabela  +  colunas;
--    · Rotinas:              sp_ (procedure) / fn_ (function) / trg_ (trigger)
--                            / vw_ (view) / papel_ (role).

-- ── 5.1 · categoria ─────────────────────────────────────────────────────────
--  Domínio de categorias de receitas (Frutos do Mar, Massas, ...).
CREATE TABLE categoria (
    idCategoria   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nomeCategoria VARCHAR(30)   NOT NULL,
    icone         VARCHAR(40)   NULL COMMENT 'Classe do ícone Line Awesome (ex.: la-fish) — permite adicionar categorias sem tocar no código',

    CONSTRAINT pk_categoria             PRIMARY KEY (idCategoria),
    CONSTRAINT uq_categoria_nome        UNIQUE (nomeCategoria),
    CONSTRAINT ck_categoria_nome_valido CHECK (CHAR_LENGTH(TRIM(nomeCategoria)) >= 3)
) ENGINE = InnoDB
  COMMENT = 'Categorias de receitas exibidas nos filtros do portal';

-- ── 5.2 · usuario ───────────────────────────────────────────────────────────
--  Contas de acesso. A senha é SEMPRE um hash bcrypt (password_hash do PHP,
--  60+ caracteres) — nunca texto puro; a CHECK ck_usuario_senha_hash bloqueia
--  inserções acidentais de senhas curtas em claro.
CREATE TABLE usuario (
    idUsuario     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nomeUsuario   VARCHAR(60)   NOT NULL,
    emailUsuario  VARCHAR(120)  NOT NULL,
    senhaUsuario  VARCHAR(255)  NOT NULL,
    idCategoriaFK INT UNSIGNED  NULL,
    criadoEm      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizadoEm  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_usuario            PRIMARY KEY (idUsuario),
    CONSTRAINT uq_usuario_email      UNIQUE (emailUsuario),
    CONSTRAINT ck_usuario_nome       CHECK (CHAR_LENGTH(TRIM(nomeUsuario)) >= 1),
    CONSTRAINT ck_usuario_email      CHECK (emailUsuario LIKE '_%@_%.%'),
    CONSTRAINT ck_usuario_senha_hash CHECK (CHAR_LENGTH(senhaUsuario) >= 60)
) ENGINE = InnoDB
  COMMENT = 'Contas de usuários do portal (senhas em hash bcrypt)';

-- ── 5.3 · receita ───────────────────────────────────────────────────────────
--  Catálogo de receitas. As 15 colunas de ingredientes preservam o contrato
--  da aplicação (busca parametrizada ingrediente_1..15) — uma modelagem N:N
--  (receita_ingrediente) fica registrada como evolução futura na seção 11.
CREATE TABLE receita (
    idReceita     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    nomeReceita   VARCHAR(70)    NOT NULL,
    porcoes       SMALLINT UNSIGNED NOT NULL,
    tempoReceita  VARCHAR(10)    NOT NULL,
    qtdCalorias   DECIMAL(7,2)   NOT NULL,
    link          VARCHAR(400)   NOT NULL COMMENT 'Embed (iframe) do vídeo no YouTube (youtube-nocookie)',
    ingrediente_1  VARCHAR(60)   NULL,
    ingrediente_2  VARCHAR(60)   NULL,
    ingrediente_3  VARCHAR(60)   NULL,
    ingrediente_4  VARCHAR(60)   NULL,
    ingrediente_5  VARCHAR(60)   NULL,
    ingrediente_6  VARCHAR(60)   NULL,
    ingrediente_7  VARCHAR(60)   NULL,
    ingrediente_8  VARCHAR(60)   NULL,
    ingrediente_9  VARCHAR(60)   NULL,
    ingrediente_10 VARCHAR(60)   NULL,
    ingrediente_11 VARCHAR(60)   NULL,
    ingrediente_12 VARCHAR(60)   NULL,
    ingrediente_13 VARCHAR(60)   NULL,
    ingrediente_14 VARCHAR(60)   NULL,
    ingrediente_15 VARCHAR(60)   NULL,
    modoPreparo   TEXT           NOT NULL,
    idcategoriaFK INT UNSIGNED   NULL,
    imagem        VARCHAR(30)    NOT NULL,
    dificuldade    ENUM('Fácil','Médio','Difícil') NULL COMMENT 'Grau de dificuldade exibido no card e na página',
    tempoCozimento VARCHAR(10)   NULL COMMENT 'Tempo de cozimento, separado do tempo total de preparo',
    dicas          TEXT          NULL COMMENT 'Dicas/observações opcionais exibidas na página da receita',

    CONSTRAINT pk_receita              PRIMARY KEY (idReceita),
    CONSTRAINT uq_receita_nome         UNIQUE (nomeReceita),
    CONSTRAINT uq_receita_link         UNIQUE (link),
    CONSTRAINT ck_receita_porcoes      CHECK (porcoes > 0),
    CONSTRAINT ck_receita_calorias     CHECK (qtdCalorias >= 0),
    CONSTRAINT ck_receita_ingrediente1 CHECK (ingrediente_1 IS NOT NULL)
) ENGINE = InnoDB
  COMMENT = 'Catálogo de receitas do portal';

-- ── 5.4 · auditoria_usuario ─────────────────────────────────────────────────
--  Trilha de auditoria alimentada exclusivamente pelos triggers da seção 12.
--  Não armazena hash de senha — apenas o fato de ela ter sido alterada.
CREATE TABLE auditoria_usuario (
    idAuditoria   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    idUsuario     INT UNSIGNED    NOT NULL,
    acao          VARCHAR(10)     NOT NULL,
    emailUsuario  VARCHAR(120)    NOT NULL,
    alterouSenha  TINYINT(1)      NOT NULL DEFAULT 0,
    executadoPor  VARCHAR(100)    NOT NULL,
    dataEvento    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_auditoria_usuario PRIMARY KEY (idAuditoria),
    CONSTRAINT ck_auditoria_acao    CHECK (acao IN ('INSERT', 'UPDATE', 'DELETE'))
) ENGINE = InnoDB
  COMMENT = 'Auditoria de INSERT/UPDATE/DELETE na tabela usuario (via triggers)';

-- ── 5.5 · favorito ──────────────────────────────────────────────────────────
--  Receitas favoritadas por usuário autenticado (recurso do catálogo). Chave
--  composta impede duplicidade; a PK já indexa idUsuario (prefixo) para listar
--  as favoritas de um usuário. FKs em cascata: apagar a conta ou a receita
--  remove os favoritos correspondentes.
CREATE TABLE favorito (
    idUsuario INT UNSIGNED NOT NULL,
    idReceita INT UNSIGNED NOT NULL,
    criadoEm  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_favorito PRIMARY KEY (idUsuario, idReceita)
) ENGINE = InnoDB
  COMMENT = 'Receitas favoritadas por usuário';

-- ── 5.6 · avaliacao ─────────────────────────────────────────────────────────
--  Nota de 1 a 5 por usuário/receita (uma por par — a PK composta garante um
--  voto único; a atualização reaproveita a linha). Média e contagem são
--  agregadas na leitura do catálogo. FKs em cascata.
CREATE TABLE avaliacao (
    idUsuario    INT UNSIGNED     NOT NULL,
    idReceita    INT UNSIGNED     NOT NULL,
    nota         TINYINT UNSIGNED NOT NULL,
    criadoEm     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizadoEm TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_avaliacao      PRIMARY KEY (idUsuario, idReceita),
    CONSTRAINT ck_avaliacao_nota CHECK (nota BETWEEN 1 AND 5)
) ENGINE = InnoDB
  COMMENT = 'Avaliação (1–5) de uma receita por usuário';

-- ── 5.7 · receita_imagem ────────────────────────────────────────────────────
--  Fotos adicionais de uma receita (galeria), além da imagem principal em
--  receita.imagem. 'ordem' controla a exibição. Só leitura pela aplicação.
CREATE TABLE receita_imagem (
    idImagem  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idReceita INT UNSIGNED NOT NULL,
    arquivo   VARCHAR(60)  NOT NULL,
    ordem     SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT pk_receita_imagem PRIMARY KEY (idImagem),
    CONSTRAINT uq_receita_imagem UNIQUE (idReceita, arquivo)
) ENGINE = InnoDB
  COMMENT = 'Galeria de imagens adicionais por receita';


-- ═══════════════════════════════════════════════════════════════════════════
--  6. CONSTRAINTS DE INTEGRIDADE REFERENCIAL
-- ═══════════════════════════════════════════════════════════════════════════
--  FKs declaradas via ALTER TABLE para manter a seção de relacionamentos
--  centralizada e legível.
--    · usuario.idCategoriaFK  → categoria (preferência do usuário; opcional);
--    · receita.idcategoriaFK  → categoria (classificação da receita).
--  ON DELETE SET NULL: apagar uma categoria não apaga usuários nem receitas.
--  ON UPDATE CASCADE : renumerar uma categoria propaga o novo id.

ALTER TABLE usuario
    ADD CONSTRAINT fk_usuario_categoria
        FOREIGN KEY (idCategoriaFK) REFERENCES categoria (idCategoria)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

ALTER TABLE receita
    ADD CONSTRAINT fk_receita_categoria
        FOREIGN KEY (idcategoriaFK) REFERENCES categoria (idCategoria)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

-- favorito → usuario / receita. ON DELETE CASCADE: apagar a conta ou a receita
-- limpa os favoritos ligados a ela.
ALTER TABLE favorito
    ADD CONSTRAINT fk_favorito_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT fk_favorito_receita
        FOREIGN KEY (idReceita) REFERENCES receita (idReceita)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

-- avaliacao → usuario / receita (mesma política de cascata dos favoritos).
ALTER TABLE avaliacao
    ADD CONSTRAINT fk_avaliacao_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT fk_avaliacao_receita
        FOREIGN KEY (idReceita) REFERENCES receita (idReceita)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

-- receita_imagem → receita (apagar a receita apaga suas fotos).
ALTER TABLE receita_imagem
    ADD CONSTRAINT fk_receita_imagem_receita
        FOREIGN KEY (idReceita) REFERENCES receita (idReceita)
        ON DELETE CASCADE
        ON UPDATE CASCADE;


-- ═══════════════════════════════════════════════════════════════════════════
--  7. ÍNDICES E OTIMIZAÇÃO
-- ═══════════════════════════════════════════════════════════════════════════
--  · PKs e UNIQUEs já criam índices (idUsuario, emailUsuario, nomeReceita,
--    link, nomeCategoria) — o login busca por emailUsuario via índice único;
--  · idx_*: aceleram os filtros por categoria da home;
--  · ftx_receita_ingredientes: índice FULLTEXT para evolução da busca de
--    ingredientes (MATCH ... AGAINST), mais eficiente que 15 LIKEs em tabelas
--    grandes — exemplo de uso na seção 14.7.

CREATE INDEX idx_receita_categoria ON receita (idcategoriaFK);
CREATE INDEX idx_receita_dificuldade ON receita (dificuldade);
CREATE INDEX idx_usuario_categoria ON usuario (idCategoriaFK);
-- Média/contagem de avaliações são agrupadas por receita (a PK começa por idUsuario).
CREATE INDEX idx_avaliacao_receita ON avaliacao (idReceita);
CREATE INDEX idx_auditoria_usuario_evento ON auditoria_usuario (idUsuario, dataEvento);

CREATE FULLTEXT INDEX ftx_receita_ingredientes ON receita (
    ingrediente_1, ingrediente_2, ingrediente_3, ingrediente_4, ingrediente_5,
    ingrediente_6, ingrediente_7, ingrediente_8, ingrediente_9, ingrediente_10,
    ingrediente_11, ingrediente_12, ingrediente_13, ingrediente_14, ingrediente_15
);


-- ═══════════════════════════════════════════════════════════════════════════
--  8. VIEWS
-- ═══════════════════════════════════════════════════════════════════════════
--  Casos de uso: encapsular JOINs repetitivos, expor dados sem colunas
--  sensíveis e servir de contrato estável para relatórios.
--  Atualização: vw_usuario_publico é ATUALIZÁVEL (base em uma única tabela,
--  sem agregação) — exemplo prático na seção 17.3. Views com JOIN/GROUP BY
--  (vw_receita_card, vw_estatisticas_categoria) são somente leitura.

-- ── 8.1 · Cards da home: receita + nome da categoria ────────────────────────
CREATE OR REPLACE VIEW vw_receita_card AS
SELECT r.idReceita,
       r.nomeReceita,
       r.tempoReceita,
       r.porcoes,
       r.qtdCalorias,
       r.imagem,
       r.idcategoriaFK,
       c.nomeCategoria
FROM receita r
LEFT JOIN categoria c ON c.idCategoria = r.idcategoriaFK;

-- ── 8.2 · Estatísticas por categoria (agregação) ────────────────────────────
CREATE OR REPLACE VIEW vw_estatisticas_categoria AS
SELECT c.idCategoria,
       c.nomeCategoria,
       COUNT(r.idReceita)                     AS totalReceitas,
       ROUND(AVG(r.qtdCalorias), 2)           AS mediaCalorias,
       MIN(r.qtdCalorias)                     AS menorCaloria,
       MAX(r.qtdCalorias)                     AS maiorCaloria
FROM categoria c
LEFT JOIN receita r ON r.idcategoriaFK = c.idCategoria
GROUP BY c.idCategoria, c.nomeCategoria;

-- ── 8.3 · Usuários sem dados sensíveis (view atualizável) ───────────────────
CREATE OR REPLACE VIEW vw_usuario_publico AS
SELECT idUsuario,
       nomeUsuario,
       emailUsuario,
       idCategoriaFK,
       criadoEm
FROM usuario;


-- ═══════════════════════════════════════════════════════════════════════════
--  9. FUNCTIONS
-- ═══════════════════════════════════════════════════════════════════════════

DELIMITER $$

-- ── 9.1 · Calorias por porção de uma receita ────────────────────────────────
CREATE FUNCTION fn_calorias_por_porcao(p_idReceita INT UNSIGNED)
    RETURNS DECIMAL(7,2)
    READS SQL DATA
    DETERMINISTIC
    COMMENT 'Retorna qtdCalorias/porcoes da receita; NULL se não existir'
BEGIN
    DECLARE v_resultado DECIMAL(7,2);

    SELECT ROUND(r.qtdCalorias / r.porcoes, 2)
      INTO v_resultado
      FROM receita r
     WHERE r.idReceita = p_idReceita;

    RETURN v_resultado;
END$$

-- ── 9.2 · Total de receitas de uma categoria ────────────────────────────────
CREATE FUNCTION fn_total_receitas_categoria(p_idCategoria INT UNSIGNED)
    RETURNS INT
    READS SQL DATA
    DETERMINISTIC
    COMMENT 'Quantidade de receitas classificadas na categoria informada'
BEGIN
    DECLARE v_total INT DEFAULT 0;

    SELECT COUNT(*)
      INTO v_total
      FROM receita
     WHERE idcategoriaFK = p_idCategoria;

    RETURN v_total;
END$$

DELIMITER ;


-- ═══════════════════════════════════════════════════════════════════════════
--  10. STORED PROCEDURES
-- ═══════════════════════════════════════════════════════════════════════════

DELIMITER $$

-- ── 10.1 · Busca de receitas por ingrediente (consulta parametrizada) ───────
--  Reproduz, no banco, a regra de busca usada pela aplicação (LIKE nas 15
--  colunas de ingredientes) com parâmetro de entrada.
CREATE PROCEDURE sp_buscar_receitas_por_ingrediente(IN p_termo VARCHAR(60))
    READS SQL DATA
    COMMENT 'Cards das receitas que contêm o ingrediente informado'
BEGIN
    DECLARE v_like VARCHAR(62);
    SET v_like = CONCAT('%', p_termo, '%');

    SELECT idReceita, nomeReceita, tempoReceita, nomeCategoria, imagem
      FROM vw_receita_card
     WHERE EXISTS (
               SELECT 1
                 FROM receita r
                WHERE r.idReceita = vw_receita_card.idReceita
                  AND (r.ingrediente_1  LIKE v_like OR r.ingrediente_2  LIKE v_like
                    OR r.ingrediente_3  LIKE v_like OR r.ingrediente_4  LIKE v_like
                    OR r.ingrediente_5  LIKE v_like OR r.ingrediente_6  LIKE v_like
                    OR r.ingrediente_7  LIKE v_like OR r.ingrediente_8  LIKE v_like
                    OR r.ingrediente_9  LIKE v_like OR r.ingrediente_10 LIKE v_like
                    OR r.ingrediente_11 LIKE v_like OR r.ingrediente_12 LIKE v_like
                    OR r.ingrediente_13 LIKE v_like OR r.ingrediente_14 LIKE v_like
                    OR r.ingrediente_15 LIKE v_like)
           )
     ORDER BY nomeReceita;
END$$

-- ── 10.2 · Relatório por categoria (cursor + repetição + condicionais) ──────
--  Demonstra programação procedural completa: DECLARE, cursor, LOOP,
--  CONTINUE HANDLER (tratamento de exceção NOT FOUND) e IF/ELSE.
CREATE PROCEDURE sp_relatorio_categorias()
    READS SQL DATA
    COMMENT 'Percorre as categorias com cursor e classifica o acervo de cada uma'
BEGIN
    DECLARE v_fim        TINYINT(1) DEFAULT 0;
    DECLARE v_id         INT UNSIGNED;
    DECLARE v_nome       VARCHAR(30);
    DECLARE v_total      INT;
    DECLARE v_diagnostico VARCHAR(30);

    DECLARE cur_categorias CURSOR FOR
        SELECT idCategoria, nomeCategoria FROM categoria ORDER BY idCategoria;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_fim = 1;

    DROP TEMPORARY TABLE IF EXISTS tmp_relatorio_categorias;
    CREATE TEMPORARY TABLE tmp_relatorio_categorias (
        idCategoria INT UNSIGNED,
        nomeCategoria VARCHAR(30),
        totalReceitas INT,
        diagnostico VARCHAR(30)
    ) ENGINE = MEMORY;

    OPEN cur_categorias;

    laco_categorias: LOOP
        FETCH cur_categorias INTO v_id, v_nome;
        IF v_fim = 1 THEN
            LEAVE laco_categorias;
        END IF;

        SET v_total = fn_total_receitas_categoria(v_id);  -- reutilização (seção 9.2)

        IF v_total = 0 THEN
            SET v_diagnostico = 'SEM RECEITAS';
        ELSEIF v_total < 5 THEN
            SET v_diagnostico = 'ACERVO PEQUENO';
        ELSE
            SET v_diagnostico = 'ACERVO OK';
        END IF;

        INSERT INTO tmp_relatorio_categorias
        VALUES (v_id, v_nome, v_total, v_diagnostico);
    END LOOP;

    CLOSE cur_categorias;

    SELECT * FROM tmp_relatorio_categorias ORDER BY idCategoria;
    DROP TEMPORARY TABLE IF EXISTS tmp_relatorio_categorias;
END$$

-- ── 10.3 · Troca de categoria favorita (transação + exceção + RESIGNAL) ─────
--  Demonstra TCL dentro de rotina: validação com SIGNAL, EXIT HANDLER com
--  ROLLBACK e repasse do erro original (RESIGNAL) ao chamador.
CREATE PROCEDURE sp_trocar_categoria_favorita(
    IN p_idUsuario   INT UNSIGNED,
    IN p_idCategoria INT UNSIGNED
)
    MODIFIES SQL DATA
    COMMENT 'Atualiza a categoria favorita do usuário com validação transacional'
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;  -- devolve o erro original para a aplicação tratar
    END;

    START TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM categoria WHERE idCategoria = p_idCategoria) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Categoria inexistente.';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM usuario WHERE idUsuario = p_idUsuario) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Usuário inexistente.';
    END IF;

    UPDATE usuario
       SET idCategoriaFK = p_idCategoria
     WHERE idUsuario = p_idUsuario;

    COMMIT;
END$$

-- ── 10.4 · Expurgo da auditoria (política de retenção — LGPD) ────────────────
--  Remove eventos de auditoria mais antigos que o prazo de retenção definido
--  em docs/privacidade/politica-retencao.md (padrão: 365 dias). Agendável via
--  EVENT do MySQL ou cron externo: CALL sp_expurgar_auditoria(365);
CREATE PROCEDURE sp_expurgar_auditoria(IN p_dias_retencao INT UNSIGNED)
    MODIFIES SQL DATA
    COMMENT 'Expurga auditoria_usuario além do prazo de retenção (em dias)'
BEGIN
    DELETE FROM auditoria_usuario
     WHERE dataEvento < (NOW() - INTERVAL p_dias_retencao DAY);
END$$

DELIMITER ;


-- ═══════════════════════════════════════════════════════════════════════════
--  11. PACKAGES — NOTA DE COMPATIBILIDADE
-- ═══════════════════════════════════════════════════════════════════════════
--  MySQL e MariaDB (modo padrão) não suportam PACKAGES (recurso Oracle;
--  MariaDB oferece apenas em sql_mode=ORACLE). A modularização equivalente é
--  obtida por convenção de prefixos — fn_ / sp_ / trg_ / vw_ — agrupando as
--  rotinas por domínio, como feito nas seções 9 e 10.
--
--  Evolução futura registrada: normalizar ingredientes em tabela própria
--  (receita_ingrediente N:N) mantendo compatibilidade via views.


-- ═══════════════════════════════════════════════════════════════════════════
--  12. TRIGGERS — VALIDAÇÃO E AUDITORIA
-- ═══════════════════════════════════════════════════════════════════════════
--  · BEFORE: normalização e validação defensiva (complementa as CHECKs);
--  · AFTER : trilha de auditoria em auditoria_usuario (regra de negócio:
--            toda alteração em contas fica registrada, sem expor senhas).

DELIMITER $$

-- ── 12.1 · Validação/normalização antes de inserir usuário ──────────────────
CREATE TRIGGER trg_usuario_before_insert
BEFORE INSERT ON usuario
FOR EACH ROW
BEGIN
    SET NEW.nomeUsuario  = TRIM(NEW.nomeUsuario);
    SET NEW.emailUsuario = TRIM(NEW.emailUsuario);

    IF NEW.emailUsuario NOT LIKE '_%@_%.%' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'E-mail em formato inválido.';
    END IF;
END$$

-- ── 12.2 · Restrição de utilização: e-mail não pode mudar de dono ────────────
--  Regra de negócio de exemplo: impedir a "transferência" de conta alterando
--  o e-mail para o de outro usuário já existente (o UNIQUE já garante isso;
--  o trigger devolve uma mensagem de domínio mais clara).
CREATE TRIGGER trg_usuario_before_update
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    SET NEW.nomeUsuario  = TRIM(NEW.nomeUsuario);
    SET NEW.emailUsuario = TRIM(NEW.emailUsuario);

    IF NEW.emailUsuario NOT LIKE '_%@_%.%' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'E-mail em formato inválido.';
    END IF;

    IF NEW.emailUsuario <> OLD.emailUsuario
       AND EXISTS (SELECT 1 FROM usuario u
                    WHERE u.emailUsuario = NEW.emailUsuario
                      AND u.idUsuario <> OLD.idUsuario) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'E-mail já utilizado por outra conta.';
    END IF;
END$$

-- ── 12.3 · Auditoria de INSERT ───────────────────────────────────────────────
CREATE TRIGGER trg_usuario_after_insert
AFTER INSERT ON usuario
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_usuario (idUsuario, acao, emailUsuario, alterouSenha, executadoPor)
    VALUES (NEW.idUsuario, 'INSERT', NEW.emailUsuario, 0, CURRENT_USER());
END$$

-- ── 12.4 · Auditoria de UPDATE (marca se a senha foi trocada) ────────────────
CREATE TRIGGER trg_usuario_after_update
AFTER UPDATE ON usuario
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_usuario (idUsuario, acao, emailUsuario, alterouSenha, executadoPor)
    VALUES (NEW.idUsuario,
            'UPDATE',
            NEW.emailUsuario,
            IF(NEW.senhaUsuario <> OLD.senhaUsuario, 1, 0),
            CURRENT_USER());
END$$

-- ── 12.5 · Auditoria de DELETE ───────────────────────────────────────────────
CREATE TRIGGER trg_usuario_after_delete
AFTER DELETE ON usuario
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_usuario (idUsuario, acao, emailUsuario, alterouSenha, executadoPor)
    VALUES (OLD.idUsuario, 'DELETE', OLD.emailUsuario, 0, CURRENT_USER());
END$$

DELIMITER ;


-- ═══════════════════════════════════════════════════════════════════════════
--  13. SEED — DADOS INICIAIS (DML)
-- ═══════════════════════════════════════════════════════════════════════════
--  Carga oficial do portal: 20 categorias, 36 receitas e 2 usuários de
--  demonstração. Os INSERTs abaixo também disparam os triggers de auditoria
--  da seção 12 (verificado na seção 17).

-- ── 13.1 · Categorias ────────────────────────────────────────────────────────
--  Nome de exibição (Title Case) e ícone Line Awesome vêm do banco: a interface
--  não fixa nenhuma categoria em código — novas linhas aqui já aparecem nos
--  filtros. Ids 1–6 preservam o mapeamento das 36 receitas; 7+ são categorias
--  de referência prontas para receber receitas futuras.
INSERT INTO `categoria` (`idCategoria`, `nomeCategoria`, `icone`) VALUES
(1,  'Frutos do Mar',  'la-fish'),
(2,  'Massas',         'la-utensils'),
(3,  'Veganas',        'la-leaf'),
(4,  'Salgados',       'la-hotdog'),
(5,  'Doces',          'la-cookie'),
(6,  'Carnes',         'la-drumstick-bite'),
(7,  'Aves',           'la-feather-alt'),
(8,  'Peixes',         'la-fish'),
(9,  'Saladas',        'la-carrot'),
(10, 'Bolos',          'la-birthday-cake'),
(11, 'Sobremesas',     'la-ice-cream'),
(12, 'Lanches',        'la-hamburger'),
(13, 'Bebidas',        'la-cocktail'),
(14, 'Vegetarianas',   'la-seedling'),
(15, 'Fitness',        'la-dumbbell'),
(16, 'Café da Manhã',  'la-mug-hot'),
(17, 'Almoço',         'la-concierge-bell'),
(18, 'Jantar',         'la-moon'),
(19, 'Aperitivos',     'la-cheese'),
(20, 'Molhos',         'la-pepper-hot');


-- ── 13.2 · Receitas (36 registros com vídeo, ingredientes e modo de preparo) ─
INSERT INTO `receita` (`idReceita`, `nomeReceita`, `porcoes`, `tempoReceita`, `qtdCalorias`, `link`, `ingrediente_1`, `ingrediente_2`, `ingrediente_3`, `ingrediente_4`, `ingrediente_5`, `ingrediente_6`, `ingrediente_7`, `ingrediente_8`, `ingrediente_9`, `ingrediente_10`, `ingrediente_11`, `ingrediente_12`, `ingrediente_13`, `ingrediente_14`, `ingrediente_15`, `modoPreparo`, `idcategoriaFK`, `imagem`) VALUES
(1, 'Macarrão à carbonara', 6, '15 min', 295.50, '<iframe width=\"962\" height=\"541\" src=\"https://www.youtube-nocookie.com/embed/pZdnOiH4q2Q\"\ntitle=\"Macarrão à carbonara — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', 'bacon picado gosto', 'queijo ralado a gosto', '3 ovos', 'sal', 'pimenta-do-reino a gosto', 'macarrão de sua escolha (espaguete, fusili,etc.)', 'creme de leite', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Frite bem o bacon, até ficar crocante (pode-se adicionar salame picado).\r\nColoque o macarrão para cozinhar em água e sal. No refratário onde será servido o macarrão, bata bem os ovos com um garfo.\r\nTempere com sal e pimenta a gosto, e junte o queijo ralado, também a gosto.\r\nQuando o macarrão estiver pronto, escorra e coloque (bem quente) sobre a mistura de ovos, misture bem.\r\nO calor da massa cozinha os ovos. Coloque o bacon, ainda quente, sobre o macarrão e sirva.', 2, 'carbonara.png'),
(2, 'Estrogonofe de Carne', 8, '30 min', 303.27, '<iframe width=\"962\" height=\"541\" src=\"https://www.youtube-nocookie.com/embed/uTDuchZ7XPE\"\r\n title=\"Estrogonofe de carne — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '3 colheres (sopa) de azeite', '1 kg de alcatra picada', 'sal a gosto', 'pimenta-do-reino a gosto', '1 cebola picada', '3 tomates picados sem pele e sem sementes', '2 colheres (sopa) de ketchup', '360 g de champignon fatiado', '2 latas de creme de leite sem soro', NULL, NULL, NULL, NULL, NULL, NULL, 'Em uma panela, adicione o óleo, a carne, a cebola, os tomates, o caldo de carne e deixe cozinhar por 20 minutos.\r\nAcrescente o ketchup e o champignon e deixe cozinhar até obter um molho consistente e cremoso. Desligue o fogo e acrescente o creme de leite sem soro.\r\nMexa até incorporar o molho ao creme. Coloque em uma forma refratária e decore com tempero e batata palha.', 6, 'estrogonofe_carne.png'),
(3, 'Macarrão com molho branco e bacon', 6, '30 min', 159.97, '<iframe width=\"962\" height=\"541\" src=\"https://www.youtube-nocookie.com/embed/eje5eCAz2Rc\" \r\ntitle=\"Macarrão com bacon e molho branco — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1/2 kg de bacon', '1 colher (sopa) de manteiga', '1 colher (sopa) de cebola', 'sal a gosto', '1 colher (sopa) de farinha de trigo', '400 ml de leite', '1 pacote de macarrão cozido', 'cheiro-verde a gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, ' Frite o bacon e escorra o óleo.\r\nEm uma panela, adicione a manteiga e refogue a cebola. Adicione o sal e a farinha de trigo e mexa bem. Acrescente o leite e mexa até engrossar um pouco. \r\nJunte o bacon e o macarrão cozido e mexa. Finalize com cheiro-verde a gosto.', 2, 'macarrao.png'),
(4, 'Filé de Salmão ao Forno', 2, '50 min', 171.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/hZ7ELIu-rgE\" \r\ntitle=\"Filé de Salmão ao Forno - Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '500 g de filé de salmão', '50 g de azeitonas fatiadas sem caroço', 'Orégano', '3 colheres de sopa de Molho de soja (shoyu)', 'Sal a gosto', 'Azeite a gosto', 'Limão', '1/2 cebola fatiada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Lave o salmão com suco de limão.\r\nAqueça o azeite e adicione a cebola fatiada, deixando no fogo até que fique transparente. Reserve.\r\nCubra uma assadeira com papel alumínio de maneira que a sobra dê para forrar todo o peixe.\r\nSobre o papel alumínio na assadeira, coloque o peixe já temperado com sal, regue com azeite e shoyu.\r\nDecore com fatias de azeitonas e um pouco de orégano. Despeje a cebola por cima.\r\nEmbrulhe com o papel alumínio, de maneira que o líquido não derrame quando começar a esquentar. Leve ao forno médio para assar por cerca de 30 minutos.\r\nSirva com legumes e salada verde.', 1, 'salmao.png'),
(5, 'Ostras Gratinadas', 2, '30 min', 49.13, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/yoq6h79mMFY\" \r\ntitle=\"Receita de Ostras Gratinadas - Riviera Gourmet\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 dúzia de ostras médias', '1 dente de alho pequeno', '1 pitada de sal grosso', '1/2 tablete de manteiga sem sal (100g)', 'Raspas de 1 limão siciliano', 'Pimenta do reino moída', '1 colher de sopa de salsinha picada', 'Azeite de oliva', '1 colher de sopa de suco de limão', 'Farinha de rosca ', NULL, NULL, NULL, NULL, NULL, 'Inicie a receita amassando o alho com uma pitada de sal grosso. Use a lâmina da faca deitada para pressionar o alho e o sal grosso contra a tábua.\r\nO sal grosso serve de moinho para o alho. Se tiver um pilão de pedra, melhor ainda.\r\nColoque a pasta de alho e sal num prato e acrescente aos poucos a manteiga em cubinhos, já na temperatura ambiente para facilitar o processo.\r\nAmasse com um grafo e com o auxílio de uma espátula, vá misturando os demais ingredientes, que são as raspas do limão, a pimenta o reino moída, a salsinha.\r\nO azeite e o suco do limão ajudam a manteiga a ficar mais fluida, mas não exagere senão ela ficará muito líquida.\r\nCom a manteiga já temperada pegue 2 colheres de café e faça pequenas bolinhas sobre cada ostra.\r\nCubra com uma pitada generosa de farinha de rosca e leve ao forno preaquecido a 200ºC por 12 minutos, ou até a farinha de rosca ficar dourada.\r\nSirva num prato quente (microondas por 1 minuto). Acompanha fatias de pão italiano, ou pão preto.', 1, 'ostras.png'),
(6, 'Peixe Porquinho Frito', 10, '35 min', 267.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/14OUIGu3RFE\" \r\ntitle=\"Como fazer Peixe Porquinho frito\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 kilo de peixe porquinho (limpo e sem cabeça)', 'sal a gosto', 'pimenta-do-reino em pó a gosto', '2 ovos', 'farinha de trigo', '3 limões médios', '1 colher (sopa) de açafrão', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Tempere o peixe com os limões, o sal, e a pimenta do reino \r\ne deixe curtir por pelo menos 2 horas, para o tempero se infiltrar no peixe (costumo deixar de um dia para o outro). Bata os ovos e reserve.\r\nEm outro recipiente coloque a farinha de trigo, e o açafrão, e misture. Passe o peixe na farinha, nos ovos, e novamente na farinha de trigo.\r\nFrite em fogo médio/alto até que doure.', 1, 'porquinho.png'),
(7, 'Filé de Tilápia Grelhado', 2, '15 min', 249.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/atvFUEpoKIs\" \r\ntitle=\"Tilápia Grelhada | Receitas Práticas Mueller\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '500g de filé de tilápia', 'Sal a gosto', 'Pimenta do reino a gosto', '1 limão Taiti', '2 colheres de sopa de manteiga', '1 fio de azeite de oliva', '2 ramos de alecrim', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Tempere o filé com o sal, pimenta e suco de um limão taiti e deixe descansar por 30 minutos.\r\n2) Aqueça uma frigideira com duas colheres de manteiga, um fio de azeite e os ramos de alecrim e grelhe os filés.', 1, 'tilapia.png'),
(8, 'Camarão Empanado', 4, '30 min', 297.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/Ee1BF9_hO3M\" \r\ntitle=\"Camarão frito empanado — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '400 g de camarões limpos com o rabo', '1 colher (sopa) de limão', '1 colher (sopa) de alho picados', 'sal a gosto', 'pimenta-do-reino a gosto', '1 xícara de farinha de trigo', '1/4 de colher (chá) de açafrão em pó', '2 colheres (sopa) de salsa', '3 colheres (sopa) de leite', '1 ovo', '1 xícara de farinha de rosca', NULL, NULL, NULL, NULL, 'Tempere os camarões com o suco do limão, alho e sal. Em uma travessa misture o leite e o ovo.\r\nEm outra travessa, junte a farinha de trigo, o açafrão, a pimenta-do-reino e a salsa.\r\nMergulhe os camarões na mistura de leite e em seguida passe-os na farinha temperada, frite-os em óleo bem quente. \r\nPasse os camarões na farinha de trigo temperada, depois na mistura de leite com ovo e por fim na farinha de rosca.\r\nFrite em óleo em imersão.', 1, 'camarao.png'),
(9, 'Pudim de Chocolate', 8, '90 min', 142.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/Fgt1Lah-mnM\" \r\ntitle=\"Pudim de Chocolate | Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 lata de leite condensado', '200 g de cacau em pó 70%', '200 ml de creme de leite', '300 ml de leite', '4 ovos', '2 xícaras de açúcar', '1/2 xícara de água', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'No liquidificador, junte todos os ingredientes do pudim com cuidado.\r\nBata tudo muito bem. Reserve. Em uma panela, coloque o açúcar e a água. Deixe derreter até virar um caramelo.\r\nDespeje no fundo de uma forma de pudim e deixe esfriar. Depois, coloque a massa do pudim por cima e leve a forma para uma travessa com água dentro, para cozinhar em banho-maria.\r\nCubra com papel-alumínio. Leve para o forno a 180°C por 1 hora. Sirva.', 5, 'pudim.png'),
(10, 'Sardinha assada no forno', 4, '25 min', 164.40, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/hudVMVKMhAI\" \r\ntitle=\"SARDINHA NO FORNO ASSADA [SUPER SABOROSA E SAUDÁVEL]\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '500g de sardinha fresca', '1/2 pimenta de cheiro picada', '2 dentes de alho picados', 'sal a gosto', 'pimenta-do-reino a gosto', 'raspas de 1 limão tahiti', '1 tomate em rodelas', '1 cebola em rodelas', '1 colher de sopa de alcaparras', 'fios de azeite por cima', '1 colher de chá rasa de erva doce', '1 colher de chá rasa de dill', NULL, NULL, NULL, 'Tempere a sardinha fresca com pimenta de cheiro, alho, raspas de limão, sal, pimenta do reino, erva doce, dill.\r\nArrume em uma assadeira a cebola, a alcaparra e o tomate, tempere com sal e pimenta do reino. Coloque as sardinhas por cima temperadas.\r\nRegue com azeite. Coloque no forno a 250 graus por 20 minutos com dourador ligado.', 1, 'sardinha.png'),
(11, 'Brigadeiro', 30, '25 min', 314.06, '<iframe width=\"555\" height=\"312\" src=\"https://www.youtube-nocookie.com/embed/u4fJ5pnpzyg\" \r\ntitle=\"Brigadeiro — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 colher (sopa) de manteiga', '1 lata de leite condensado', '4 colheres (sopa) de chocolate em pó', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Em uma panela funda, acrescente o leite condensado, a margarina e o achocolatado (ou 4 colheres de sopa de chocolate em pó).\r\nCozinhe em fogo médio e mexa até que o brigadeiro comece a desgrudar da panela.\r\nDeixe esfriar e faça pequenas bolas com a mão passando a massa no chocolate granulado.', 5, 'brigadeiro.png'),
(12, 'Bolo de Cenoura', 8, '40 min', 415.00, '<iframe width=\"736\" height=\"414\" src=\"https://www.youtube-nocookie.com/embed/mRij59AYQP0\" \r\ntitle=\"Bolo de cenoura — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1/2 xícara (chá) de óleo', '3 cenouras médias raladas', '4 ovos', '2 xícaras (chá) de açúcar', '2 e 1/2 xícaras (chá) de farinha de trigo', '1 colher (sopa) de fermento em pó', '1 colher (sopa) de manteiga', '3 colheres (sopa) de chocolate em pó', '1 xícara (chá) de açúcar', '1 xícara (chá) de leite', NULL, NULL, NULL, NULL, NULL, 'Em um liquidificador, adicione a cenoura, os ovos e o óleo, depois misture. Acrescente o açúcar e bata novamente por 5 minutos.\r\nEm uma tigela ou na batedeira, adicione a farinha de trigo e depois misture novamente. Acrescente o fermento e misture lentamente com uma colher.\r\nAsse em um forno preaquecido a 180° C por aproximadamente 40 minutos. Para a cobertura, despeje em uma tigela a manteiga, o chocolate em pó,\r\no açúcar e o leite, depois misture. Leve a mistura ao fogo e continue misturando até obter uma consistência cremosa, depois despeje a calda por cima do bolo.', 5, 'bolo_cenoura.png'),
(13, 'Picadinho vegano', 6, '45 min', 130.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/0zoS4r_5yDM\" title=\"Picadinho Vegano | Drica na Cozinha\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '2 batatas em cubos', '2 cenouras em cubos', '1 abobrinha em cubos', '200g de abóbora em cubos', '2 colheres (sopa) de óleo', '2 dentes de alho picados', '1 cebola picada', '1 berinjela cubos', 'Queijo parmesão ralado para polvilhar', '2 colheres (sopa) de manteiga', '2 colheres (sopa) de farinha de trigo', '3 xícaras (chá) de leite', '1/2 colher (café) de noz-moscada ralada', 'Sal e pimenta-do-reino a gosto', NULL, '1. Em uma panela, em fogo médio, cozinhe a batata, a cenoura, a abobrinha e a abóbora, separadamente, até que fiquem al dente e reserve.\r\n2. Em outra panela com o óleo, refogue o alho, a cebola, a berinjela e os legumes pré-cozidos por 5 minutos, em fogo médio.\r\n3. Para o molho, em outra panela, em fogo médio, derreta a manteiga, misture a farinha e adicione, aos poucos, o leite, mexendo sempre, por 5 minutos ou até que dissolva por igual.\r\n4. Coloque a noz-moscada, sal, pimenta e continue mexendo por mais 5 minutos.\r\n5. Junte o molho branco aos legumes já refogados, misture, polvilhe com queijo ralado e sirva.', 3, 'picadinhovegano.png'),
(14, 'Almôndega de grão-de-bico', 6, '90 min', 39.30, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/5znFDSqMsLM\" title=\"Almôndega de grão-de-bico — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '2 colheres (sopa) de azeite', '1/2 cebola', '2 dentes de alho', 'pimenta-do-reino a gosto', '1 colher (chá) de cominho em pó', '1/2 xícara de cenoura', '1 xícara de grão-de-bico', '1 ovo', '1 xícara de farinha de rosca', '1/4 de xícara de cheiro-verde', NULL, NULL, NULL, NULL, NULL, '1. Deixe o grão-de-bico de molho por 12 horas.\r\n2. Em seguida, cozinhe por 20 minutos na panela de pressão e escorra a água.\r\n3. Em uma panela, aqueça o azeite e refogue a cebola e o alho.\r\n4. Acrescente o sal, a pimenta-do-reino, o cominho e a cenoura.\r\n5. Tranfira essa mistura para um processador e adicione o grão-de-bico, o ovo, a farinha de rosca, a pimenta-do-reino e o cheiro-verde.\r\n6. Bata bem até formar uma massa homogênea.\r\n7. Leve à geladeira por 1 hora.\r\n8. Depois, faça bolinhas com a massa e sele-as em uma panela com azeite.\r\n9. Transfira as almôndegas para uma travessa e leve ao forno preaquecido (200° C), por cerca de 10 minutos.\r\n10. Sirva com molho de tomate e aproveite!', 3, 'almondegas.png'),
(15, 'Bolinha de queijo', 30, '20 min', 52.00, '<iframe width=\"733\" height=\"412\" src=\"https://www.youtube-nocookie.com/embed/H9-dKpqclHQ\" title=\"Bolinha de queijo — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 xícara de leite', '1 colher (sopa) de manteiga', '1 xícara de farinha de trigo', 'sal a gosto', '2 gemas', '150 g de mussarela', '1/2 xícara de farinha de rosca', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Em uma panela, adicione o leite, a farinha de trigo, a margarina, a gema e o sal.\r\n2. Leve ao fogo e mexa com um garfo até que a massa solte da panela, depois deixe esfriar.\r\n3. Modele a massa em bolinhas e recheie com o queijo, depois passe o bolinho na gema de ovo e na farinha de rosca.\r\n4. Em uma panela, adicione o óleo, depois de quente adicione as bolinhas e frite-as.', 4, 'bolinhaqueijo.png'),
(16, 'Coxinha low carb', 6, '50 min', 114.38, '<iframe width=\"696\" height=\"392\" src=\"https://www.youtube-nocookie.com/embed/ScPz7gU_k0E\" title=\"Coxinha saudável\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 batata doce cozida', '1/2 filé de peito de frango sem pele cozido', '1 tomate', '2 dentes de alho', '1/2 cebola', '1 colher (sopa) de requeijão', '3 colheres (sopa) de farinha de linhaça', '1 colher (sopa) de azeite de oliva', 'sal a gosto', 'pimenta-do-reino a gosto', NULL, NULL, NULL, NULL, NULL, '1. Preaqueça o forno a 200°.\r\n2. Em uma panela, doure o alho e a cebola com o azeite.\r\n3. Acrescente o frango desfiado cozido, o tomate, e temperos a gosto.\r\n4. Refogue por 3 minutos.\r\n5. Acrescente o requeijão, misture e desligue o fogo.\r\n6. Tempere a batata amassada com um pouco de sal.\r\n7. Pegue um pouco de massa, faça uma bolinha, abra e recheie. Feche formando a coxinha.\r\n8. Empane na farinha de linhaça.\r\n9. Leve ao forno por 15 minutos ou até dourar.', 4, 'coxinha.png'),
(17, 'Bolinho de arroz recheado', 6, '35 min', 109.60, '<iframe width=\"733\" height=\"412\" src=\"https://www.youtube-nocookie.com/embed/Ed6Obc7AVBo\" title=\"Bolinho de Arroz Recheado | Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '200g de arroz cozido', '100g de tomate seco', '80g de queijo parmesão ralado', '50g de farinha de trigo', '1 ovo', 'noz-moscada a gosto', 'pimenta-do-reino a gosto', 'pimenta-da-jamaica a gosto', 'sal a gosto', 'cheiro-verde a gosto', '150g de queijo mussarela em cubos', NULL, NULL, NULL, NULL, '1. Em um recipiente, misture os primeiros 10 ingredientes muito bem.\r\n2. Quando se formar uma mistura mais homogênea, reserve.\r\n3. Com o auxílio de uma colher (sopa), retire um pouco da massa e modele na mão.\r\n4. Recheie com o queijo mussarela e feche muito bem para não abrir na panela.\r\n5. Leve para fritar em óleo quente.\r\n6. Retire, escorra em papel-toalha e sirva acompanhado de um molho de sua preferência.', 4, 'bolinhoarroz.png'),
(18, 'Pizza de frigideira', 4, '10 min', 200.47, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/TduXsxH0WWM\" title=\"Pizza de frigideira — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 + 1/4 de xícara de farinha de trigo', '1/2 colher (sopa) de manteiga', '1 pitada de sal', '1 copo de leite morno', '2 colheres (sopa) de molho de tomate', '1/4 de xícara de queijo mussarela ralado', 'tomate cereja a gosto', 'cebola a gosto', 'orégano a gosto', NULL, NULL, NULL, NULL, NULL, NULL, '1. Misture tudo em uma travessa com as mãos.\r\n2. Caso não encontre o ponto certo, adicione mais farinha até desgrudar a massa das mãos.\r\n3. Separar 4 bolinhas de massa. Abra-as separadamente na frigideira.\r\n4. Asse somente um lado até o ponto desejado. Vire a massa.\r\n5. Desligue o fogo.\r\n6. Coloque molho de tomate.\r\n7. Cubra o molho com mussarela, rodelas de tomate e orégano.\r\n8. Asse agora o outro lado.\r\n9. Tampe para que o queijo derreta.', 4, 'pizza.png'),
(19, 'Kibe de forno', 8, '40 min', 148.53, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/pO4obYnWcCY\" title=\"Kibe de forno — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '500g de carne moída', '5 dentes de alho moído', '3 cebolas raladas', '1/4 xícara (chá) de salsinha picadinha', '1/4 xícara (chá) de hortelã', 'sal, pimenta-do-reino e um pouquinho de vinagre', '500g de trigo para kibe, que deve ficar 1 hora de molho', '1 pacote de creme de cebola', '500g de carne moída refogada a seu gosto.', '1 pote de queijo catupiry', NULL, NULL, NULL, NULL, NULL, '1. Escorra o trigo, misture todos os ingredientes da massa muito bem.\r\n2. Eles devem estar bem picadinhos ou ralados.\r\n3. Coloque metade em uma assadeira ou refratário, espalhe o recheio e tampe com o restante da massa.\r\n4. Regue com bastante azeite de oliva.\r\n5. Leve ao forno regular por, aproximadamente, 30 a 40 minutos ou até estar bem assado.\r\n6. Corte ainda na assadeira e sirva.', 4, 'kibe.png'),
(20, 'Enroladinho de salsicha', 15, '35 min', 65.00, '<iframe width=\"962\" height=\"541\" src=\"https://www.youtube-nocookie.com/embed/_ipEGoVEoAA\" title=\"Enroladinho de salsicha — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '3 xícaras de água', '2 xícaras e 1/2 de trigo', '2 colheres de óleo', '1 sachê de tempero pronto ou 1 colher (chá) de cúrcuma', 'sal a gosto', '1 kg de salsicha', 'farinha de rosca pra empanar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Misture tudo da massa e leve ao fogo.\r\n2. Mexa bastante (o ponto e a massa desgrudar da panela), quando der o ponto retire do fogo e reserve.\r\n3. Corte todas as salsichas pela metade.\r\n4. Unte as mãos com óleo e enrole as salsichas na massa.\r\n5. Passe na farinha de rosca e frite em óleo novo e bem quente. Uma dica: Jogue um fósforo no óleo, se o fósforo acender, o óleo está quente.', 4, 'enroladinho.png'),
(21, 'Brownie', 16, '45 min', 243.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/z9EywpP3XGU\" title=\"Brownie — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '4 ovos', '320 g de açúcar refinado', 'água fervendo', '150 g de chocolate amargo', '150 g de manteiga', '140 g de farinha de trigo', '20 g de cacau em pó', '50 g de nozes picadas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Quebre os 4 ovos em uma tigela, junte o açúcar e misture bem.\r\n2. Em uma tigela à parte, derreta o chocolate amargo em banho-maria ou no micro-ondas, acrescente a manteiga e misture até formar um líquido homogêneo.\r\n3. Adicione o chocolate à mistura de ovos e manteiga e, novamente, mexa até ficar homogêneo.\r\n4. Junte a farinha de trigo peneirada, mexendo sempre.\r\n5. Em seguida, coloque o cacau em pó peneirado, mexendo, e adicione as nozes picadas.\r\n6. Forre uma forma retangular com papel manteiga e despeje a massa.\r\n7. Salpique um pouco mais de nozes picadas sobre a massa na forma e leve ao forno preaquecido a 180º C por 30 minutos.', 5, 'brownie.png'),
(22, 'Cookies de chocolate', 12, '90 min', 99.07, '<iframe width=\"733\" height=\"412\" src=\"https://www.youtube-nocookie.com/embed/32bvO6_oe2I\" title=\"Os melhores cookies que você vai comer!\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '140 gramas de manteiga em temperatura ambiente', '40 gramas de manteiga queimada ou noisette (opcional)', '120 gramas de açúcar mascavo', '90 gramas de açúcar refinado', '1 ovo grande', '1 colher de chá de fermento em pó', '3/4 colher de chá de bicarbonato', '3/4 colher de chá de sal', '250 gramas de farinha de trigo', 'essência ou pasta de baunilha a gosto (opcional)', '180 gramas de chocolate picado', NULL, NULL, NULL, NULL, '1. Em uma panela pequena, vaqueça 40 g de manteiga em fogo médio até ficar bem dourada.\r\n2. Transfira para uma tigela e coloque na geladeira por 10 minutos para abaixar a temperatura. Reserve.\r\n3. Coloque o restante da manteiga (140 g) em temperatura ambiente numa tigela grande e, com uma espátula, misture e amasse até ficar em ponto de pomada.\r\n4. Adicione os açúcares e a manteiga noisette e bata com a batedeira até ficar cremoso.\r\n5. Adicione o ovo e a baunilha e misture bem. Reserve.\r\n6. Peneire a farinha com o sal e o fermento e pique o chocolate.\r\n7. O chocolate deve ser picado de forma irregular para que haja pedaços maiores e menores dentro dos cookies depois de assados.\r\n8. Incorpore delicadamente com uma espátula, aos poucos, a farinha peneirada alternando com o chocolate picado.\r\n9. Molde a massa em formato de bola, transfira para uma assadeira forrada com papel manteiga, deixando espaço entre elas, e leve para a geladeira por pelo menos 1 hora. \r\n10. Asse em forno pré-aquecido a 180 ºC por 12 a 15 minutos. Deixe esfriar antes de comer.', 5, 'cookies.png'),
(23, 'Pão recheado', 8, '15 min', 365.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/KnPXvRS0U1w\" title=\"Pão recheado — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '3 ovos', '1 cebola média', '1 1/2 copo de leite morno', '1/2 copo de óleo', '2 tabletes de caldo de galinha', '1 colher (sopa) rasa de acúçar', '1 sachê de fermento biológico em pó (ou 2 tabletes frescos)', '1kg de farinha de trigo', '500g de linguiça calabresa', '3 tomates (italiano) picados', '15 azeitonas picadas', '1 gema para pincelar, pimenta, orégano e salsa', ' Cebolinha, sementes de gergelim e papoula (opcional).', NULL, NULL, '1. Bater no liquidificador 2 tabletes de caldo de galinha em 1 copo e 1/2 de leite morno.\r\n2. Acrescente 2 tabletes de fermento para pão.\r\n3. Em seguida os ovos, a cebola, o óleo e o açúcar - se for o fermento biológico seco/em pó, bata no liquificador tudo, exceto a farinha de trigo e o fermento seco, que serão misturados numa bacia junto à parte líquida. Depois, despeje em uma bacia e coloque a farinha até desgrudar dos dedos.\r\n4. Depois que desgrudar das mãos coloque a massa sobre uma banca de granito para sovar (ou qualquer outra parte lisa).\r\n5. Em seguida rale (ou processe no multiprocessador) as calabresas e uma cebola. Pique 3 tomates, cebolinha e salsinha (ou coentro), cerca de 15 azeitonas num recipiente à parte e junte a calabresa moída.\r\n6. Divida a massa em 6 partes (seis pães grandes) e abra, individualmente com um rolo; aplique o recheio sobre a massa aberta (na espessura de uma massa fina de pizza) e enrole (sem apertar) feito um rocambole.\r\n7. Coloque no tabuleiro já untado, enrole os próximos.\r\n8. Espere crescer antes de levar para assar.\r\n9. Faça pequenos cortes nos pães, de modo a aparecer o recheio. Se quiser uns pães mais bonitos, é só pincelar com gema e jogar gergelim, sementes de papoula e orégano por cima.\r\n10. Coloque, então, para assar em forno brando.', 2, 'pao.png'),
(24, 'Panqueca de frango', 10, '30 min', 486.27, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/qbd4_ETAENU\" title=\"Panqueca de frango — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '3 ovos', '2 xícaras (chá) de farinha de trigo', '2 xícaras (chá) de leite', '2 colheres (sopa) de manteiga', '1 colher (chá) de sal', '2 peitos de frango sem osso', '1 sachê de caldo de galinha', '1 lata de molho de tomate pronto', '2 colheres (sopa) de azeite', '1/2 cebola picada', '3 dentes de alho amassados', 'pimenta, sal e salsinha a gosto', NULL, NULL, NULL, '1. Bata no liquidificador todos os ingredientes durante 3 minutos, deixe descansando.\r\n2. Cozinhe o peito de frango em um pouco de água com o caldo de galinha, até ficar bem cozido.\r\n3. Retire da panela em que foi cozido e comece a desfiar com um garfo.\r\n4. Leve uma panela ao fogo, coloque o azeite a cebola picada e o alho, deixe dourar. Acrescente o frango desfiado e tempere com pimenta e sal e mexa.\r\n5. Deixe refogar por 5 minutos mexendo de vez em quando, agora acrescente um pouco de molho de tomate só para dar um corzinha no frango e retire do fogo e reserve.\r\n6. Agora faremos a panqueca, use uma frigideira teflon rasa unte-a com um pouco de manteiga.\r\n7. Coloque uma quantidade razoável de massa da frigideira que não fique grossa, vá fazendo até acabar a massa.\r\n8. Agora coloque um pouco de recheio na ponta da panqueca e enrole faça isso com todas, vá colocando todas em uma forma retangular para ir ao forno.\r\nAgora aqueça o molho de tomate e derrame em cima das panquecas jogue um pouco de queijo parmesão em cima se preferir e leve ao forno preaquecido por 5 minutos. Bom apetite!', 2, 'panqueca.png'),
(25, 'Lasanha tradicional', 8, '50 min', 336.00, '<iframe width=\"962\" height=\"541\" src=\"https://www.youtube-nocookie.com/embed/CdlrWXq0vpA\" title=\"Lasanha Tradicional — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 massa de lasanha (pronta)', '500 g de presunto', '500 g de queijo mussarela', '500 g carne moída', '1 massa de tomate pronta', 'sal a gosto', 'pimenta-do-reino a gosto', 'orégano a gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Cozinhe a massa da lasanha em aproximadamente em 2 litros de água por 5 minutos.\r\n2. Em uma panela cozinhe a carne moída, depois de cozida coloque molho de tomate, o sal e temperos a gosto.\r\n3. Comece montando com uma camada de molho, a massa da lasanha, o presunto e o queijo.\r\n4. Faça esse processo até tudo terminar.\r\n5. Aqueça o forno a 180º C durante 5 minutos.\r\n6. Coloque a lasanha no forno de 20 a 30 minutos.', 2, 'lasanha.png'),
(26, 'Pão de queijo vegano', 20, '100 min', 22.58, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/BnrPrtZ_6CY\" title=\"O Melhor PÃO DE QUEIJO VEGANO - Fácil e barato!!\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '2 xícaras de polvilho doce', '1 e 1/2 xícaras de batata baroa bem cozida e amassada', '1/2 xícara de polvilho azedo', '1/3 de xícara de óleo vegetal', '1/4 de xícara de água morna', 'Sal a gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Comece cozinhando a batata baroa e fazendo o purê.\r\n2. Espere o purê esfriar até o ponto de conseguir tocá-lo sem queimar as mãos.\r\n3. Adicione todos os ingredientes numa tigela grande.\r\n4. Misture e amasse tudo com as mãos até que tenha uma massa homogênea.\r\n5. Molde os pães de queijo e coloque-os em uma assadeira.\r\n6. Considere o mesmo espaçamento de quando está assando pães de queijo congelados. Eles crescem um pouquinho.\r\n7. Asse os pãezinhos em forno baixo (150ºC) até que estejam sequinhos e com as rachaduras características.', 3, 'paodequeijo.png'),
(27, 'Hambúrger vegano de soja', 2, '20 min', 125.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/fOfbTq_8nuE\" title=\"HAMBÚRGUER DE PROTEÍNA DE SOJA | DE-LI-CI-O-SO DEMAIS | TNM Vegg\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1/4 de xícara (chá) de proteína de soja texturizada pequena', '1/2 xícara de água', '1 dente de alho picado', '1 colher (sopa) de farinha de trigo ou farinha de aveia', '2 colheres de sopa de shoyu (adicione sal se preferir)', 'Temperos a gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Coloque a proteína de soja e a água em uma panela, leve ao fogo até começar a ferver.\r\n2. Retire a proteína de soja do fogo e escorra em água corrente.\r\n3. Quando estiver fria, esprema até sair toda a água para a soja ficar sequinha.\r\n4. Em uma vasilha misture a soja com os outros ingredientes.\r\n5. Modele os hambúrgueres e faça bolinhas e depois aperte no meio para dar liga e ficar no formato de hambúrguer e está pronto.', 3, 'hamburguer.png'),
(28, 'Sorvete caseiro', 20, '240 min', 267.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/O5YC2KGkIJ4\" title=\"SORVETE CASEIRO COM APENAS 3 INGREDIENTES / RENDE MUITO...\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 lata de leite condensado', '1 caixinha de creme de leite', '1 pacote de gelatina do sabor da sua preferência', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Prepare a gelatina conforme instruções da embalagem.\r\n2. Em seguida, despeje, a gelatina ainda quente, o leite condensado e o creme de leite no liquidificador.\r\nBata por 3 a 4 minutos e leve ao freezer por, no mínimo, 4 horas.\r\n3. Retire do freezer e bata na batedeira por 5 a 8 minutos.\r\n4. Por fim, retorne ao freezer por mais 4 horas.\r\n5. Antes de bater na batedeira, você pode acrescentar à massa pedaços da fruta do sabor da gelatina. Fica muito mais gostoso.', 5, 'sorvete.png'),
(29, 'Nhoque de batata', 6, '60 min', 154.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/H-EwBabJic4\" title=\"Nhoque de batata — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '6 batatas médias', '1 xícara de farinha de trigo', 'sal a gosto', '1 lata de molho de tomate', '1/2 cebola', '1 colher (sopa) de azeite', '2 tabletes de caldo de carne', '1/2 kg de carne moída', '1 pacote de queijo ralado', NULL, NULL, NULL, NULL, NULL, NULL, '1. Cozinhe as batatas, em água, até que estejam macias.\r\n2. Descasque-as e passe pelo espremedor ainda quentes.\r\n3. Vá acrescentando a farinha aos poucos e o sal, amasse bem.\r\n4. Coloque a massa sobre uma mesa enfarinhada e faça rolinhos.\r\n5. Corte cada rolinho em pedaços de mais ou menos 2 cm.\r\n6. Leve ao fogo, em uma panela com bastante água temperada com sal.\r\n7. Quando a água levantar fervura, vá colocando os nhoques, até eles começarem a subir.\r\n8. Coloque água fria em uma bacia com um escorredor dentro, retire os nhoques já cozidos e coloque-os no escorredor para dar choque térmico.\r\n9. Repita o processo até toda massa estar cozida.\r\n10. Escorra bem e coloque o nhoque em um refratário, reserve.', 2, 'nhoque.png'),
(30, 'Torta vegana de legumes', 8, '45 min', 244.80, '<iframe width=\"733\" height=\"412\" src=\"https://www.youtube-nocookie.com/embed/1ZFkFBL0ryQ\" title=\"TORTA DE LEGUMES VEGANA DE LIQUIDIFICADOR  | PLANTTE\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 1/2 xícaras (chá) de leite de amêndoas', '1/2 xícara (chá) de óleo de soja', '1 1/2 xícaras (chá) de farinha de trigo', '1 colher (sopa) de amido de milho', '1 colher (sopa) de fermento químico em pó', '1 colher (sopa) de vinagre de maçã', '1 xícara (chá) de abóbora', '1 xícara (chá) de mandioquinha', '1 xícara (chá) de cenoura', '1 unidade de batata', 'Sal a gosto', 'Pimenta-do-reino a gosto', 'Páprica doce defumada a gosto', NULL, NULL, '1. Refogue a cebola e alho picados no azeite. Acrescente os legumes: abóbora, mandioquinha, cenoura e batata cortadas em cubinhos e tempere a gosto com o sal, pimenta do reino e páprica defumada. Refogue tudo e reserve. (Se os seus vegetais já estiverem cozidos e temperados, pode pular essa parte).\r\n2. No liquidificador, coloque o leite vegetal, o óleo, a farinha de trigo, o amido de milho e o sal. Bata bem até incorporar tudo.\r\n3. Acrescente o vinagre e o fermento e bata mais um pouco, brevemente.\r\n4. Unte uma forma com óleo e farinha e despeje metade da massa. Coloque o refogado de vegetais e cubra com o restante da massa.\r\n5. Leve para assar a 180°C por 45 minutos. O tempo depende de forno pra forno, fique de olho. Faça o teste do palito para ter certeza de que a torta está assada.\r\n6. É só enfiar um palito de dente: se ele sair limpo, é porque está pronto, mas, se sair sujo, deixe no forno por mais alguns minutos.', 3, 'tortavegana.png'),
(31, 'Quibe de abóbora', 9, '40 min', 197.02, '<iframe width=\"368\" height=\"207\" src=\"https://www.youtube-nocookie.com/embed/1L25IqBmtJg\" title=\"Quibe de abóbora simples\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 xícara de chá de trigo para quibe', '135 g de abóbora cozida e amassada', '1/2 cebola pequena picada', 'Sal e pimenta-do-reino a gosto', 'Cebolinha a gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Em um recipiente, adicione o trigo para quibe e deixe de molho por 2 horas.\r\n2. Retire o excesso de água e reserve.\r\n3. Coloque a abóbora amassada em um pano e aperte bem para retirar o excesso de água do cozimento.\r\n4. Em uma tigela, adicione o trigo para quibe hidratado, a abóbora, o sal, a pimenta, a cebola, a cebolinha e misture bem.\r\n5. Pegue pequenas porções da massa e molde em formato de quibes.\r\n6. Transfira eles para uma forma e leve ao forno preaquecido a 180º graus por cerca de 30 minutos.\r\n7. Agora é só servir. Bom apetite.', 3, 'quibevegano.png'),
(32, 'Bife à milanesa', 6, '40 min', 230.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/igo6iYss5ms\" title=\"Bife à milanesa - Tudo Gostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1/2 kg de bife patinho, alcatra ou mignon', '3 ovos batidos', 'farinha de rosca a gosto', 'farinha de trigo a gosto', '3 dentes de alho amassados opcional', 'sal a gosto', 'pimenta do reino-a-gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Tempere os bifes a gosto e reserve.\r\n2. Em um prato fundo, bata os ovos até obter uma mistura homogênea.\r\n3. Separe a farinha de rosca e a farinha de trigo em pratos diferentes.\r\n4. Passe os bifes na farinha de trigo, depois nos ovos batidos e na farinha de rosca.\r\n5. Em uma frigideira, frite os bifes em óleo quente até que fiquem dourados.\r\n6. Ao retirar da frigideira, coloque os bifes em papel toalha para que a gordura em excesso seja absorvida.', 6, 'bife.png'),
(33, 'Escondidinho de carne moída', 8, '40 min', 348.94, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/E_0k16uOfZ0\" title=\"Escondidinho de carne moída — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 kg de batata', '500 g de carne moída', '200 g de queijo mussarela', 'azeite', '1 cebola', '1 dente de alho amassado', 'sal a gosto', 'pimenta branca a gosto', 'cheiro-verde a gosto', '1/2 copo de leite', '2 colheres de manteiga', NULL, NULL, NULL, NULL, '1. Descasque as batatas, corte ao meio e cozinhe com água e sal.\r\n2. Depois de cozidas, amasse as batatas, adicione o leite e a manteiga, mexa bem até formar um purê e reserve.\r\n3. Em uma panela, adicione 1 fio de azeite, a cebola, o alho e refogue a carne moída.\r\n4. Tempere com sal, pimenta branca, cheiro-verde e cozinhe até secar a água que se formar na panela.\r\n5. Forre um refratário com a metade do purê de batatas.\r\n6. Acrescente uma camada de queijo e uma camada de carne moída.\r\n7. Repita o processo e finalize com queijo ralado por cima.\r\n8. Leve ao forno por 40 minutos.', 6, 'escondidinho.png'),
(34, 'Carne de panela', 5, '50 min', 238.90, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/yBLptTjDU8k\" title=\"Carne de panela — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '500 g de coxão mole cortado em bifes', '1 cebola ralada', '1 dente de alho amassado', '1/2 xícara chá de óleo', 'sal e pimenta-do-reino a gosto', '1/2 colher (sopa) de tempero em pó sabor umami (opcional)', '1 colher (sopa) de salsinha picada', '500 ml de água quente', '1/2 lata de massa de tomate', '1 pimentão verde picado', '1 tomate sem sementes picado', '1 cenoura pequena picada', 'orégano a gosto', NULL, NULL, '1. Em uma panela de pressão, coloque o óleo junte a cebola, alho e refogue bem.\r\n2. Acrescente a carne frite por 5 minutos mexendo bem, depois coloque o tempero em pó sabor umami (opcional), tomate, pimentão, massa de tomate, cenoura e a seguir acrescente a água orégano.\r\n3. Deixe cozinhar por 30 minutos contando o inicio da fervura, assim que a carne estiver cozida retire do fogo, misture a salsinha e sirva em seguida com arroz branco.', 6, 'carnepanela.png'),
(35, 'Picadinho de carne', 4, '30 min', 53.00, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/3C517wuWSbA\" title=\"Picadinho de carne — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1 kg de patinho em cubos', '4 colheres (sopa) de farinha de trigo', '1 colher (sopa) de óleo', 'sal a gosto', 'pimenta-do-reino a gosto', '1 colher (sopa) de manteiga', '1/2 cebola ralada', '1 dente de alho picado', '1/4 de xícara de vinho tinto', '1/2 lata de tomate', '3 xícaras de água', NULL, NULL, NULL, NULL, '1. Em um recipiente, misture a carne com a farinha de trigo.\r\n2. Em uma panela, aqueça o óleo, acrescente a carne, tempere com sal e pimenta-do-reino e deixe dourar.\r\n3. Retire a carne da panela e reserve.\r\n4. Na mesma panela, adicione a manteiga e refogue a cebola e o alho.\r\n5. Acrescente o vinho tinto e deixe reduzir um pouco.\r\n6. Volte a carne para a panela e misture bem.\r\n7. Adicione o tomate e a água.\r\n8. Tempere com sal a pimenta-do-reino e deixe cozinhar.', 6, 'picadinhocarne.png'),
(36, 'Rocambole de carne', 8, '15 min', 204.86, '<iframe width=\"735\" height=\"413\" src=\"https://www.youtube-nocookie.com/embed/NmICuanRLck\" title=\"Rocambole de carne moída — Receitas TudoGostoso\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>', '1/2 kg de carne moída', '1 pacote de sopa de cebola', 'presunto fatiado', 'queijo fatiado', 'tempero verde', 'sal a gosto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Tempere a carne moída com a sopa de cebola, o tempero verde e o sal.\r\n2. Coloque a carne temperada sobre uma folha de papel laminado ou papel manteiga e abra a massa com um rolo, na espessura de 1 cm, mais ou menos.\r\n3. Forre a carne com o presunto e o queijo, pode-se colocar também milho verde, ervilha e requeijão.\r\n4. Enrole a carne, com ajuda da folha de papel laminado ou manteiga, em forma de rocambole.\r\n5. Leve ao forno, em temperatura alta, por mais ou menos 30 minutos, ou no microondas por 15 minutos.\r\n6. Bom apetite!', 6, 'rocambole.png');

-- ── 13.2.1 · Backfill de dificuldade, tempo de cozimento e dicas ─────────────
--  Núcleo de campos do catálogo marketplace preenchido de forma derivada:
--  dificuldade e tempo de cozimento a partir do tempo de preparo; dicas por
--  categoria. Roda no seed (reseed recria o banco do zero).
-- REGEXP_REPLACE isola os dígitos de tempoReceita ('15 min' → '15') antes do
-- CAST: sob sql_mode estrito (MariaDB), converter a string direto abortaria a
-- UPDATE por "Truncated incorrect INTEGER value".
UPDATE receita SET
    dificuldade = CASE
        WHEN CAST(REGEXP_REPLACE(tempoReceita, '[^0-9]', '') AS UNSIGNED) <= 20 THEN 'Fácil'
        WHEN CAST(REGEXP_REPLACE(tempoReceita, '[^0-9]', '') AS UNSIGNED) <= 40 THEN 'Médio'
        ELSE 'Difícil'
    END,
    tempoCozimento = CONCAT(GREATEST(10, FLOOR(CAST(REGEXP_REPLACE(tempoReceita, '[^0-9]', '') AS UNSIGNED) / 2)), ' min'),
    dicas = CASE idcategoriaFK
        WHEN 1 THEN 'Não cozinhe demais os frutos do mar para não ressecar; finalize com um toque de limão.'
        WHEN 2 THEN 'Reserve um pouco da água do cozimento da massa para ajustar a cremosidade do molho.'
        WHEN 3 THEN 'Prefira ingredientes frescos e da estação para realçar o sabor do prato.'
        WHEN 4 THEN 'Sirva quente para manter a crocância; escorra bem o excesso de óleo após fritar.'
        WHEN 5 THEN 'Use os ingredientes em temperatura ambiente para uma massa mais homogênea.'
        WHEN 6 THEN 'Deixe a carne descansar alguns minutos antes de servir para manter a suculência.'
        ELSE 'Ajuste o sal e os temperos a gosto e prefira ingredientes frescos.'
    END;


-- ── 13.3 · Usuários de demonstração ─────────────────────────────────────────
--  Senhas armazenadas como hash bcrypt (compatíveis com password_verify):
--    demo1@example.com → senha em claro: 123456
--    demo2@example.com → senha em claro: 271821
INSERT INTO `usuario` (`idUsuario`, `nomeUsuario`, `emailUsuario`, `senhaUsuario`, `idCategoriaFK`) VALUES
(1, 'Ana Exemplo', 'demo1@example.com', '$2y$10$bFuehjBZFt7sbgDjS4dDU.VLMmqrNH/D0Y5qG3uxYYeXF6p4eXUjW', 2),
(2, 'Bruno Exemplo', 'demo2@example.com', '$2y$10$eTiUF8o3aqtvPaqyNNRs7.VwJlvcU0SU7.bt8lVoJ45o4/.f21DSW', 2);


-- ── 13.4 · Avaliações de demonstração ────────────────────────────────────────
--  Notas dos dois usuários-demo em algumas receitas, para as médias já
--  aparecerem no catálogo (o ambiente free é efêmero; votos reais somem no
--  reseed, estes voltam).
INSERT INTO `avaliacao` (`idUsuario`, `idReceita`, `nota`) VALUES
(1, 1, 5), (1, 2, 4), (1, 5, 5), (1, 8, 4), (1, 12, 3), (1, 21, 5), (1, 22, 5), (1, 25, 4),
(2, 1, 4), (2, 2, 5), (2, 5, 4), (2, 8, 5), (2, 15, 3), (2, 21, 4), (2, 28, 5), (2, 25, 5);


-- ── 13.5 · Galeria de imagens (demonstração) ─────────────────────────────────
--  Fotos adicionais (além da principal) em algumas receitas, para exibir a
--  galeria com miniaturas. Usa imagens genéricas do acervo; um deploy real
--  substituiria por fotos próprias de cada prato.
INSERT INTO `receita_imagem` (`idReceita`, `arquivo`, `ordem`) VALUES
(1, 'food.jpg', 1), (1, 'imagemExemplo.png', 2),
(8, 'food.jpg', 1), (8, 'imagemExemplo.png', 2),
(21, 'food.jpg', 1),
(25, 'food.jpg', 1), (25, 'imagemExemplo.png', 2);


-- ═══════════════════════════════════════════════════════════════════════════
--  14. CONSULTAS DE EXEMPLO
-- ═══════════════════════════════════════════════════════════════════════════
--  Consultas executáveis de complexidade crescente. Rodam durante a
--  implantação como autoteste de sintaxe/planos e servem de referência para
--  relatórios futuros. Na aplicação, TODA consulta é parametrizada via
--  prepared statements do PDO (ver src/Infrastructure/Repository).

-- ── 14.1 · JOIN interno: receitas com sua categoria ─────────────────────────
SELECT r.nomeReceita, c.nomeCategoria
  FROM receita r
 INNER JOIN categoria c ON c.idCategoria = r.idcategoriaFK
 ORDER BY c.nomeCategoria, r.nomeReceita
 LIMIT 5;

-- ── 14.2 · JOIN externo + agregação: categorias mesmo sem receitas ──────────
SELECT c.nomeCategoria,
       COUNT(r.idReceita) AS totalReceitas
  FROM categoria c
  LEFT JOIN receita r ON r.idcategoriaFK = c.idCategoria
 GROUP BY c.idCategoria, c.nomeCategoria
HAVING COUNT(r.idReceita) >= 0
 ORDER BY totalReceitas DESC;

-- ── 14.3 · Subconsulta: receitas acima da média de calorias do acervo ───────
SELECT nomeReceita, qtdCalorias
  FROM receita
 WHERE qtdCalorias > (SELECT AVG(qtdCalorias) FROM receita)
 ORDER BY qtdCalorias DESC
 LIMIT 5;

-- ── 14.4 · CTE (Common Table Expression): ranking de categorias leves ───────
WITH calorias_categoria AS (
    SELECT c.nomeCategoria,
           AVG(r.qtdCalorias) AS mediaCalorias
      FROM categoria c
      JOIN receita r ON r.idcategoriaFK = c.idCategoria
     GROUP BY c.idCategoria, c.nomeCategoria
)
SELECT nomeCategoria, ROUND(mediaCalorias, 2) AS mediaCalorias
  FROM calorias_categoria
 ORDER BY mediaCalorias ASC;

-- ── 14.5 · Função analítica (janela): receita mais leve de cada categoria ───
SELECT nomeCategoria, nomeReceita, qtdCalorias
  FROM (
        SELECT c.nomeCategoria,
               r.nomeReceita,
               r.qtdCalorias,
               ROW_NUMBER() OVER (PARTITION BY r.idcategoriaFK
                                  ORDER BY r.qtdCalorias ASC) AS posicao
          FROM receita r
          JOIN categoria c ON c.idCategoria = r.idcategoriaFK
       ) ranking
 WHERE posicao = 1
 ORDER BY nomeCategoria;

-- ── 14.6 · Consulta parametrizada no servidor (PREPARE/EXECUTE) ─────────────
--  Mesmo mecanismo usado pelo PDO com emulação desligada.
SET @p_categoria := 5;  -- Doces
PREPARE stmt_receitas_categoria FROM
    'SELECT nomeReceita, qtdCalorias FROM receita WHERE idcategoriaFK = ? ORDER BY nomeReceita';
EXECUTE stmt_receitas_categoria USING @p_categoria;
DEALLOCATE PREPARE stmt_receitas_categoria;

-- ── 14.7 · Otimização: busca por ingrediente via índice FULLTEXT ────────────
--  Alternativa escalável aos 15 LIKEs (usa ftx_receita_ingredientes).
SELECT idReceita, nomeReceita
  FROM receita
 WHERE MATCH (ingrediente_1, ingrediente_2, ingrediente_3, ingrediente_4,
              ingrediente_5, ingrediente_6, ingrediente_7, ingrediente_8,
              ingrediente_9, ingrediente_10, ingrediente_11, ingrediente_12,
              ingrediente_13, ingrediente_14, ingrediente_15)
       AGAINST ('bacon' IN NATURAL LANGUAGE MODE);

--  Análise de plano de execução (descomentee para inspecionar índices em uso):
--  EXPLAIN SELECT * FROM receita WHERE idcategoriaFK = 5;


-- ═══════════════════════════════════════════════════════════════════════════
--  15. CONTROLE DE ACESSO (DCL) — PRINCÍPIO DO MENOR PRIVILÉGIO
-- ═══════════════════════════════════════════════════════════════════════════
--  Papéis (roles) e usuários dedicados. A aplicação NÃO recebe DDL nem DELETE:
--  a exclusão de conta pelo titular (LGPD art. 18, VI) é implementada por
--  ANONIMIZAÇÃO irreversível via UPDATE (privilégio já concedido) — mantém o
--  menor privilégio e fica registrada em auditoria pelo trigger AFTER UPDATE.
--    · papel_leitura   → relatórios/BI: apenas SELECT;
--    · papel_aplicacao → operação do portal: SELECT + INSERT/UPDATE pontuais;
--    · portal_app      → conta da aplicação (troque a senha em produção e
--                        aponte DB_USER/DB_PASS para ela);
--    · portal_relatorios → conta somente leitura para análises.
--  Obs.: os triggers de auditoria executam como DEFINER, então portal_app não
--  precisa (e não recebe) INSERT em auditoria_usuario.

DROP USER IF EXISTS 'portal_app'@'%';
DROP USER IF EXISTS 'portal_relatorios'@'%';
DROP ROLE IF EXISTS papel_leitura;
DROP ROLE IF EXISTS papel_aplicacao;

CREATE ROLE papel_leitura;
CREATE ROLE papel_aplicacao;

-- Permissões dos papéis
GRANT SELECT ON tcc_receitas.* TO papel_leitura;

GRANT SELECT                 ON tcc_receitas.categoria TO papel_aplicacao;
GRANT SELECT                 ON tcc_receitas.receita   TO papel_aplicacao;
GRANT SELECT, INSERT, UPDATE ON tcc_receitas.usuario   TO papel_aplicacao;
-- Favoritos: a aplicação escreve apenas nesta tabela (menor privilégio).
GRANT SELECT, INSERT, DELETE         ON tcc_receitas.favorito  TO papel_aplicacao;
-- Avaliações: cria, atualiza (revoto) e remove a própria nota.
GRANT SELECT, INSERT, UPDATE, DELETE ON tcc_receitas.avaliacao TO papel_aplicacao;
-- Galeria de imagens: apenas leitura pela aplicação.
GRANT SELECT                         ON tcc_receitas.receita_imagem TO papel_aplicacao;

-- Exemplo de REVOKE: um privilégio concedido além do necessário é retirado
GRANT DELETE ON tcc_receitas.usuario TO papel_aplicacao;
REVOKE DELETE ON tcc_receitas.usuario FROM papel_aplicacao;

-- Usuários e vínculo com os papéis
CREATE USER 'portal_app'@'%'         IDENTIFIED BY 'TroqueEstaSenha_123';
CREATE USER 'portal_relatorios'@'%'  IDENTIFIED BY 'TroqueEstaSenha_456';

GRANT papel_aplicacao TO 'portal_app'@'%';
GRANT papel_leitura   TO 'portal_relatorios'@'%';

--  Ativação do papel por padrão (sintaxe varia entre os SGBDs — execute a do
--  seu ambiente ao adotar as contas):
--    MySQL 8 : SET DEFAULT ROLE ALL TO 'portal_app'@'%';
--    MariaDB : SET DEFAULT ROLE papel_aplicacao FOR 'portal_app'@'%';


-- ═══════════════════════════════════════════════════════════════════════════
--  16. TRANSAÇÕES E CONCORRÊNCIA (TCL / ACID)
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 16.1 · BEGIN / SAVEPOINT / ROLLBACK — nada abaixo persiste ───────────────
START TRANSACTION;

INSERT INTO usuario (idUsuario, nomeUsuario, emailUsuario, senhaUsuario, idCategoriaFK)
VALUES (9001, 'Conta Temporária', 'temp.tcl@example.com',
        '$2y$10$bFuehjBZFt7sbgDjS4dDU.VLMmqrNH/D0Y5qG3uxYYeXF6p4eXUjW', 1);

SAVEPOINT sp_apos_insert;

UPDATE usuario SET nomeUsuario = 'Conta Renomeada' WHERE idUsuario = 9001;

ROLLBACK TO SAVEPOINT sp_apos_insert;  -- desfaz apenas o UPDATE
ROLLBACK;                              -- desfaz a transação inteira (atomicidade)

-- ── 16.2 · BEGIN / COMMIT — efeito durável (durabilidade) ────────────────────
--  Ciclo completo dentro de uma transação confirmada; o estado final do seed
--  permanece o mesmo, e a auditoria registra INSERT/UPDATE/DELETE.
START TRANSACTION;

INSERT INTO usuario (idUsuario, nomeUsuario, emailUsuario, senhaUsuario, idCategoriaFK)
VALUES (9002, 'Conta Demonstração', 'demo.tcl@example.com',
        '$2y$10$bFuehjBZFt7sbgDjS4dDU.VLMmqrNH/D0Y5qG3uxYYeXF6p4eXUjW', 2);

UPDATE usuario SET nomeUsuario = 'Conta Demonstração ACID' WHERE idUsuario = 9002;
DELETE FROM usuario WHERE idUsuario = 9002;

COMMIT;

-- ── 16.3 · Níveis de isolamento ──────────────────────────────────────────────
--  READ COMMITTED evita leituras sujas; REPEATABLE READ (padrão do InnoDB)
--  garante leituras estáveis dentro da transação (consistência/isolamento).
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
SELECT COUNT(*) AS usuariosVisiveis FROM usuario;
COMMIT;
SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;

-- ── 16.4 · Bloqueios e acesso simultâneo ─────────────────────────────────────
--  FOR UPDATE: bloqueio exclusivo de linha até o fim da transação — outra
--  sessão que tente alterar o mesmo usuário aguarda (prevenção de conflito).
START TRANSACTION;
SELECT idUsuario, nomeUsuario
  FROM usuario
 WHERE idUsuario = 1
   FOR UPDATE;
COMMIT;

--  Bloqueio compartilhado (leitura consistente permitindo outras leituras):
--    SELECT ... LOCK IN SHARE MODE;   -- MariaDB e MySQL (no MySQL 8: FOR SHARE)
--  Deadlocks: o InnoDB detecta e aborta a transação mais barata; a aplicação
--  deve tratar SQLSTATE 40001 (serialization failure) com retry.


-- ═══════════════════════════════════════════════════════════════════════════
--  17. TESTES E VALIDAÇÕES
-- ═══════════════════════════════════════════════════════════════════════════
--  Autoverificação da implantação: cada linha deve retornar 'OK'.

-- ── 17.1 · Volumetria do seed ────────────────────────────────────────────────
SELECT IF(COUNT(*) = 20, 'OK', 'ERRO: categorias')      AS teste_categorias FROM categoria;
SELECT IF(COUNT(*) = 36, 'OK', 'ERRO: receitas')        AS teste_receitas   FROM receita;
SELECT IF(COUNT(*) = 2,  'OK', 'ERRO: usuarios')        AS teste_usuarios   FROM usuario;

-- ── 17.2 · Integridade: sem órfãos e senhas sempre em hash ──────────────────
SELECT IF(COUNT(*) = 0, 'OK', 'ERRO: receita órfã') AS teste_fk_receita
  FROM receita r
  LEFT JOIN categoria c ON c.idCategoria = r.idcategoriaFK
 WHERE r.idcategoriaFK IS NOT NULL AND c.idCategoria IS NULL;

SELECT IF(COUNT(*) = 0, 'OK', 'ERRO: senha fora do padrão hash') AS teste_hash
  FROM usuario
 WHERE senhaUsuario NOT LIKE '$2y$%';

-- ── 17.3 · View atualizável (atualiza e reverte pela própria view) ──────────
UPDATE vw_usuario_publico SET nomeUsuario = 'Ana Exemplo (via view)' WHERE idUsuario = 1;
UPDATE vw_usuario_publico SET nomeUsuario = 'Ana Exemplo'            WHERE idUsuario = 1;
SELECT IF(nomeUsuario = 'Ana Exemplo', 'OK', 'ERRO: view atualizável') AS teste_view
  FROM usuario WHERE idUsuario = 1;

-- ── 17.4 · Rotinas: functions e procedures respondem ────────────────────────
SELECT IF(fn_calorias_por_porcao(1) > 0, 'OK', 'ERRO: fn_calorias_por_porcao') AS teste_fn_calorias;
SELECT IF(fn_total_receitas_categoria(5) > 0, 'OK', 'ERRO: fn_total_receitas') AS teste_fn_total;

CALL sp_buscar_receitas_por_ingrediente('bacon');
CALL sp_relatorio_categorias();
CALL sp_trocar_categoria_favorita(1, 3);  -- troca…
CALL sp_trocar_categoria_favorita(1, 2);  -- …e restaura o seed

-- ── 17.5 · Auditoria: triggers registraram os eventos deste script ──────────
--  Espera-se: 2 INSERTs do seed + INSERT/UPDATE/DELETE da seção 16.2 +
--  2 UPDATEs da view (17.3) + 2 UPDATEs das procedures (17.4).
SELECT IF(COUNT(*) >= 9, 'OK', 'ERRO: auditoria incompleta') AS teste_auditoria
  FROM auditoria_usuario;

SELECT acao, COUNT(*) AS eventos
  FROM auditoria_usuario
 GROUP BY acao
 ORDER BY acao;

-- ═══════════════════════════════════════════════════════════════════════════
--  FIM DO SCRIPT — banco oficial do Portal Receitas implantado e validado.
-- ═══════════════════════════════════════════════════════════════════════════
