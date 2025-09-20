-- Script de Criação do Banco de Dados: Portal de Receitas
-- Versão: MVP (Produto Mínimo Viável) - Fase 2
-- Data: 18 de setembro de 2025
-- Implementa todos os conceitos da matéria de banco de dados

-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS portal_receitas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Banco de dados já selecionado na conexão

-- ========================================
-- CRIAÇÃO DE USUÁRIOS E PRIVILÉGIOS
-- ========================================

-- Criar usuário administrador do sistema
CREATE USER IF NOT EXISTS 'admin_portal'@'localhost' IDENTIFIED BY 'admin_portal_2025!';
GRANT ALL PRIVILEGES ON portal_receitas.* TO 'admin_portal'@'localhost';

-- Criar usuário para aplicação
CREATE USER IF NOT EXISTS 'app_portal'@'localhost' IDENTIFIED BY 'app_portal_2025!';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON portal_receitas.* TO 'app_portal'@'localhost';

-- Criar usuário somente leitura
CREATE USER IF NOT EXISTS 'readonly_portal'@'localhost' IDENTIFIED BY 'readonly_portal_2025!';
GRANT SELECT ON portal_receitas.* TO 'readonly_portal'@'localhost';

FLUSH PRIVILEGES;

-- ========================================
-- CRIAÇÃO DAS TABELAS
-- ========================================

-- Tabela de categorias
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT,
    ativa BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categorias_nome (nome),
    INDEX idx_categorias_ativa (ativa)
) ENGINE=InnoDB;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('admin', 'regular') DEFAULT 'regular',
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    tentativas_login INT DEFAULT 0,
    bloqueado_ate TIMESTAMP NULL,
    INDEX idx_usuarios_email (email),
    INDEX idx_usuarios_tipo (tipo_usuario),
    INDEX idx_usuarios_ativo (ativo)
) ENGINE=InnoDB;

-- Tabela de receitas
CREATE TABLE receitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    ingredientes TEXT NOT NULL,
    modo_preparo TEXT NOT NULL,
    tempo_preparo VARCHAR(100),
    porcoes INT DEFAULT 1,
    dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
    categoria_id INT NOT NULL,
    autor_id INT NOT NULL,
    ativa BOOLEAN DEFAULT TRUE,
    aprovada BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    visualizacoes INT DEFAULT 0,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_receitas_categoria (categoria_id),
    INDEX idx_receitas_autor (autor_id),
    INDEX idx_receitas_titulo (titulo),
    INDEX idx_receitas_ativa (ativa),
    INDEX idx_receitas_aprovada (aprovada),
    INDEX idx_receitas_data_criacao (data_criacao),
    FULLTEXT idx_receitas_busca (titulo, descricao, ingredientes)
) ENGINE=InnoDB;

-- Tabela de comentários (para futuras expansões)
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receita_id INT NOT NULL,
    usuario_id INT NOT NULL,
    conteudo TEXT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_comentarios_receita (receita_id),
    INDEX idx_comentarios_usuario (usuario_id),
    INDEX idx_comentarios_data (data_criacao)
) ENGINE=InnoDB;

-- Tabela de avaliações (para futuras expansões)
CREATE TABLE avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receita_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nota DECIMAL(2,1) CHECK (nota >= 1.0 AND nota <= 5.0),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_avaliacao (receita_id, usuario_id),
    INDEX idx_avaliacoes_receita (receita_id),
    INDEX idx_avaliacoes_usuario (usuario_id)
) ENGINE=InnoDB;

-- Tabela de logs de auditoria
CREATE TABLE logs_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabela VARCHAR(50) NOT NULL,
    operacao ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    registro_id INT NOT NULL,
    usuario_id INT,
    dados_anteriores JSON,
    dados_novos JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data_operacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_tabela (tabela),
    INDEX idx_logs_operacao (operacao),
    INDEX idx_logs_data (data_operacao),
    INDEX idx_logs_usuario (usuario_id)
) ENGINE=InnoDB;

-- ========================================
-- CRIAÇÃO DE VIEWS
-- ========================================

-- View para receitas completas com informações de categoria e autor
CREATE VIEW view_receitas_completas AS
SELECT 
    r.id,
    r.titulo,
    r.descricao,
    r.ingredientes,
    r.modo_preparo,
    r.tempo_preparo,
    r.porcoes,
    r.dificuldade,
    r.visualizacoes,
    r.ativa,
    r.aprovada,
    r.data_criacao,
    r.data_atualizacao,
    c.nome AS categoria_nome,
    c.id AS categoria_id,
    u.nome AS autor_nome,
    u.id AS autor_id,
    u.tipo_usuario AS autor_tipo,
    COALESCE(AVG(av.nota), 0) AS media_avaliacoes,
    COUNT(DISTINCT av.id) AS total_avaliacoes,
    COUNT(DISTINCT com.id) AS total_comentarios
FROM receitas r
JOIN categorias c ON r.categoria_id = c.id
JOIN usuarios u ON r.autor_id = u.id
LEFT JOIN avaliacoes av ON r.id = av.receita_id
LEFT JOIN comentarios com ON r.id = com.receita_id AND com.ativo = TRUE
WHERE r.ativa = TRUE AND c.ativa = TRUE AND u.ativo = TRUE
GROUP BY r.id, r.titulo, r.descricao, r.ingredientes, r.modo_preparo, 
         r.tempo_preparo, r.porcoes, r.dificuldade, r.visualizacoes,
         r.ativa, r.aprovada, r.data_criacao, r.data_atualizacao,
         c.nome, c.id, u.nome, u.id, u.tipo_usuario;

-- View para estatísticas de receitas por categoria
CREATE VIEW view_estatisticas_categorias AS
SELECT 
    c.id,
    c.nome,
    c.descricao,
    COUNT(r.id) AS total_receitas,
    COUNT(CASE WHEN r.aprovada = TRUE THEN 1 END) AS receitas_aprovadas,
    AVG(r.visualizacoes) AS media_visualizacoes,
    MAX(r.data_criacao) AS ultima_receita
FROM categorias c
LEFT JOIN receitas r ON c.id = r.categoria_id AND r.ativa = TRUE
WHERE c.ativa = TRUE
GROUP BY c.id, c.nome, c.descricao;

-- View para receitas populares
CREATE VIEW view_receitas_populares AS
SELECT 
    r.*,
    c.nome AS categoria_nome,
    u.nome AS autor_nome,
    COALESCE(AVG(av.nota), 0) AS media_avaliacoes,
    COUNT(DISTINCT av.id) AS total_avaliacoes,
    COUNT(DISTINCT com.id) AS total_comentarios,
    (r.visualizacoes * 0.3 + COUNT(DISTINCT av.id) * 0.4 + COUNT(DISTINCT com.id) * 0.3) AS score_popularidade
FROM receitas r
JOIN categorias c ON r.categoria_id = c.id
JOIN usuarios u ON r.autor_id = u.id
LEFT JOIN avaliacoes av ON r.id = av.receita_id
LEFT JOIN comentarios com ON r.id = com.receita_id AND com.ativo = TRUE
WHERE r.ativa = TRUE AND r.aprovada = TRUE AND c.ativa = TRUE AND u.ativo = TRUE
GROUP BY r.id
ORDER BY score_popularidade DESC;

-- ========================================
-- STORED PROCEDURES
-- ========================================

DELIMITER //

-- Procedure para autenticar usuário
CREATE PROCEDURE AutenticarUsuario(
    IN p_email VARCHAR(255),
    IN p_senha_hash VARCHAR(255)
)
BEGIN
    DECLARE v_usuario_id INT DEFAULT 0;
    DECLARE v_tentativas INT DEFAULT 0;
    DECLARE v_bloqueado_ate TIMESTAMP;
    
    -- Verificar se usuário existe e está ativo
    SELECT id, tentativas_login, bloqueado_ate 
    INTO v_usuario_id, v_tentativas, v_bloqueado_ate
    FROM usuarios 
    WHERE email = p_email AND ativo = TRUE;
    
    -- Verificar se usuário está bloqueado
    IF v_bloqueado_ate IS NOT NULL AND v_bloqueado_ate > NOW() THEN
        SELECT 'BLOQUEADO' AS status, 'Usuário temporariamente bloqueado' AS mensagem;
    ELSEIF v_usuario_id > 0 THEN
        -- Verificar senha
        IF (SELECT senha_hash FROM usuarios WHERE id = v_usuario_id) = p_senha_hash THEN
            -- Login bem-sucedido
            UPDATE usuarios 
            SET ultimo_login = NOW(), tentativas_login = 0, bloqueado_ate = NULL
            WHERE id = v_usuario_id;
            
            SELECT 'SUCESSO' AS status, 'Login realizado com sucesso' AS mensagem,
                   id, nome, email, tipo_usuario, data_criacao
            FROM usuarios WHERE id = v_usuario_id;
        ELSE
            -- Senha incorreta
            SET v_tentativas = v_tentativas + 1;
            
            IF v_tentativas >= 5 THEN
                -- Bloquear usuário por 30 minutos
                UPDATE usuarios 
                SET tentativas_login = v_tentativas, bloqueado_ate = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                WHERE id = v_usuario_id;
                
                SELECT 'BLOQUEADO' AS status, 'Muitas tentativas. Usuário bloqueado por 30 minutos' AS mensagem;
            ELSE
                UPDATE usuarios 
                SET tentativas_login = v_tentativas
                WHERE id = v_usuario_id;
                
                SELECT 'ERRO' AS status, CONCAT('Credenciais inválidas. Tentativas restantes: ', (5 - v_tentativas)) AS mensagem;
            END IF;
        END IF;
    ELSE
        SELECT 'ERRO' AS status, 'Usuário não encontrado ou inativo' AS mensagem;
    END IF;
END //

-- Procedure para listar receitas com paginação
CREATE PROCEDURE ListarReceitas(
    IN p_limite INT,
    IN p_offset INT,
    IN p_categoria_id INT,
    IN p_apenas_aprovadas BOOLEAN
)
BEGIN
    DECLARE v_where_clause TEXT DEFAULT '';
    
    -- Construir cláusula WHERE dinamicamente
    IF p_categoria_id IS NOT NULL AND p_categoria_id > 0 THEN
        SET v_where_clause = CONCAT(v_where_clause, ' AND r.categoria_id = ', p_categoria_id);
    END IF;
    
    IF p_apenas_aprovadas = TRUE THEN
        SET v_where_clause = CONCAT(v_where_clause, ' AND r.aprovada = TRUE');
    END IF;
    
    SET @sql = CONCAT('
        SELECT 
            r.id,
            r.titulo,
            r.descricao,
            r.tempo_preparo,
            r.porcoes,
            r.dificuldade,
            r.visualizacoes,
            r.data_criacao,
            c.nome AS categoria_nome,
            u.nome AS autor_nome,
            COALESCE(AVG(av.nota), 0) AS media_avaliacoes,
            COUNT(DISTINCT av.id) AS total_avaliacoes
        FROM receitas r
        JOIN categorias c ON r.categoria_id = c.id
        JOIN usuarios u ON r.autor_id = u.id
        LEFT JOIN avaliacoes av ON r.id = av.receita_id
        WHERE r.ativa = TRUE AND c.ativa = TRUE AND u.ativo = TRUE',
        v_where_clause,
        ' GROUP BY r.id
        ORDER BY r.data_criacao DESC
        LIMIT ', p_limite, ' OFFSET ', p_offset
    );
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //

-- Procedure para obter detalhes de uma receita
CREATE PROCEDURE ObterDetalhesReceita(
    IN p_receita_id INT
)
BEGIN
    -- Incrementar contador de visualizações
    UPDATE receitas SET visualizacoes = visualizacoes + 1 WHERE id = p_receita_id;
    
    -- Retornar detalhes completos
    SELECT * FROM view_receitas_completas WHERE id = p_receita_id;
END //

-- Procedure para adicionar nova receita
CREATE PROCEDURE AdicionarReceita(
    IN p_titulo VARCHAR(255),
    IN p_descricao TEXT,
    IN p_ingredientes TEXT,
    IN p_modo_preparo TEXT,
    IN p_tempo_preparo VARCHAR(100),
    IN p_porcoes INT,
    IN p_dificuldade ENUM('facil', 'medio', 'dificil'),
    IN p_categoria_id INT,
    IN p_autor_id INT
)
BEGIN
    DECLARE v_receita_id INT;
    DECLARE v_aprovada BOOLEAN DEFAULT FALSE;
    
    -- Auto-aprovar receitas de administradores
    IF (SELECT tipo_usuario FROM usuarios WHERE id = p_autor_id) = 'admin' THEN
        SET v_aprovada = TRUE;
    END IF;
    
    INSERT INTO receitas (
        titulo, descricao, ingredientes, modo_preparo, tempo_preparo,
        porcoes, dificuldade, categoria_id, autor_id, aprovada
    ) VALUES (
        p_titulo, p_descricao, p_ingredientes, p_modo_preparo, p_tempo_preparo,
        p_porcoes, p_dificuldade, p_categoria_id, p_autor_id, v_aprovada
    );
    
    SET v_receita_id = LAST_INSERT_ID();
    
    SELECT v_receita_id AS receita_id, v_aprovada AS aprovada;
END //

-- Procedure para buscar receitas
CREATE PROCEDURE BuscarReceitas(
    IN p_termo_busca VARCHAR(255),
    IN p_categoria_id INT,
    IN p_dificuldade VARCHAR(20),
    IN p_limite INT,
    IN p_offset INT
)
BEGIN
    DECLARE v_where_clause TEXT DEFAULT '';
    
    -- Construir busca por texto
    IF p_termo_busca IS NOT NULL AND LENGTH(p_termo_busca) > 0 THEN
        SET v_where_clause = CONCAT(v_where_clause, 
            ' AND (MATCH(r.titulo, r.descricao, r.ingredientes) AGAINST(''', p_termo_busca, ''' IN NATURAL LANGUAGE MODE)',
            ' OR r.titulo LIKE ''%', p_termo_busca, '%''',
            ' OR r.ingredientes LIKE ''%', p_termo_busca, '%'')');
    END IF;
    
    IF p_categoria_id IS NOT NULL AND p_categoria_id > 0 THEN
        SET v_where_clause = CONCAT(v_where_clause, ' AND r.categoria_id = ', p_categoria_id);
    END IF;
    
    IF p_dificuldade IS NOT NULL AND LENGTH(p_dificuldade) > 0 THEN
        SET v_where_clause = CONCAT(v_where_clause, ' AND r.dificuldade = ''', p_dificuldade, '''');
    END IF;
    
    SET @sql = CONCAT('
        SELECT 
            r.id,
            r.titulo,
            r.descricao,
            r.tempo_preparo,
            r.porcoes,
            r.dificuldade,
            r.visualizacoes,
            r.data_criacao,
            c.nome AS categoria_nome,
            u.nome AS autor_nome,
            COALESCE(AVG(av.nota), 0) AS media_avaliacoes,
            COUNT(DISTINCT av.id) AS total_avaliacoes
        FROM receitas r
        JOIN categorias c ON r.categoria_id = c.id
        JOIN usuarios u ON r.autor_id = u.id
        LEFT JOIN avaliacoes av ON r.id = av.receita_id
        WHERE r.ativa = TRUE AND r.aprovada = TRUE AND c.ativa = TRUE AND u.ativo = TRUE',
        v_where_clause,
        ' GROUP BY r.id
        ORDER BY r.data_criacao DESC
        LIMIT ', p_limite, ' OFFSET ', p_offset
    );
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //

-- Procedure para obter estatísticas do sistema
CREATE PROCEDURE ObterEstatisticasSistema()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE ativo = TRUE) AS total_usuarios,
        (SELECT COUNT(*) FROM receitas WHERE ativa = TRUE) AS total_receitas,
        (SELECT COUNT(*) FROM receitas WHERE ativa = TRUE AND aprovada = TRUE) AS receitas_aprovadas,
        (SELECT COUNT(*) FROM categorias WHERE ativa = TRUE) AS total_categorias,
        (SELECT COUNT(*) FROM comentarios WHERE ativo = TRUE) AS total_comentarios,
        (SELECT COUNT(*) FROM avaliacoes) AS total_avaliacoes,
        (SELECT AVG(nota) FROM avaliacoes) AS media_geral_avaliacoes;
END //

DELIMITER ;

-- ========================================
-- TRIGGERS
-- ========================================

DELIMITER //

-- Trigger para log de auditoria em receitas (INSERT)
CREATE TRIGGER tr_receitas_insert_audit
AFTER INSERT ON receitas
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (tabela, operacao, registro_id, usuario_id, dados_novos)
    VALUES ('receitas', 'INSERT', NEW.id, NEW.autor_id, JSON_OBJECT(
        'titulo', NEW.titulo,
        'categoria_id', NEW.categoria_id,
        'autor_id', NEW.autor_id,
        'aprovada', NEW.aprovada
    ));
END //

-- Trigger para log de auditoria em receitas (UPDATE)
CREATE TRIGGER tr_receitas_update_audit
AFTER UPDATE ON receitas
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (tabela, operacao, registro_id, usuario_id, dados_anteriores, dados_novos)
    VALUES ('receitas', 'UPDATE', NEW.id, NEW.autor_id, 
        JSON_OBJECT(
            'titulo', OLD.titulo,
            'aprovada', OLD.aprovada,
            'ativa', OLD.ativa
        ),
        JSON_OBJECT(
            'titulo', NEW.titulo,
            'aprovada', NEW.aprovada,
            'ativa', NEW.ativa
        )
    );
END //

-- Trigger para validação de dados em receitas
CREATE TRIGGER tr_receitas_validacao
BEFORE INSERT ON receitas
FOR EACH ROW
BEGIN
    -- Validar título não vazio
    IF LENGTH(TRIM(NEW.titulo)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Título da receita não pode estar vazio';
    END IF;
    
    -- Validar ingredientes não vazios
    IF LENGTH(TRIM(NEW.ingredientes)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ingredientes não podem estar vazios';
    END IF;
    
    -- Validar modo de preparo não vazio
    IF LENGTH(TRIM(NEW.modo_preparo)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modo de preparo não pode estar vazio';
    END IF;
    
    -- Validar porções
    IF NEW.porcoes <= 0 THEN
        SET NEW.porcoes = 1;
    END IF;
END //

-- Trigger para atualizar estatísticas após inserção de avaliação
CREATE TRIGGER tr_avaliacoes_insert_stats
AFTER INSERT ON avaliacoes
FOR EACH ROW
BEGIN
    -- Aqui poderia atualizar uma tabela de estatísticas se necessário
    -- Por enquanto, apenas registra no log
    INSERT INTO logs_auditoria (tabela, operacao, registro_id, usuario_id, dados_novos)
    VALUES ('avaliacoes', 'INSERT', NEW.id, NEW.usuario_id, JSON_OBJECT(
        'receita_id', NEW.receita_id,
        'nota', NEW.nota
    ));
END //

DELIMITER ;

-- ========================================
-- INSERÇÃO DE DADOS INICIAIS
-- ========================================

-- Categorias de exemplo
INSERT INTO categorias (nome, descricao) VALUES
('Doces', 'Receitas de sobremesas e doces em geral'),
('Salgados', 'Receitas de pratos salgados e principais'),
('Bebidas', 'Receitas de bebidas, sucos e drinks'),
('Massas', 'Receitas de massas, lasanhas e similares'),
('Carnes', 'Receitas com carnes vermelhas e brancas'),
('Vegetarianos', 'Receitas vegetarianas e veganas'),
('Lanches', 'Receitas para lanches e petiscos'),
('Sopas', 'Receitas de sopas e caldos');

-- Usuários de exemplo (senhas são hashes de 'senha123')
INSERT INTO usuarios (nome, email, senha_hash, tipo_usuario) VALUES
('Administrador do Sistema', 'admin@portalreceitas.com', '$2b$10$rOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKq', 'admin'),
('Maria Silva', 'maria@email.com', '$2b$10$rOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKq', 'regular'),
('João Santos', 'joao@email.com', '$2b$10$rOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKq', 'regular'),
('Ana Costa', 'ana@email.com', '$2b$10$rOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKqKqKqKqKqOzJqQZ8kKqKq', 'regular');

-- Receitas de exemplo usando a stored procedure
CALL AdicionarReceita(
    'Bolo de Chocolate Fofinho',
    'Um delicioso bolo de chocolate fofinho e saboroso, perfeito para qualquer ocasião especial',
    '2 xícaras de farinha de trigo\n1 xícara de açúcar\n1/2 xícara de chocolate em pó\n3 ovos\n1 xícara de leite\n1/2 xícara de óleo\n1 colher de sopa de fermento',
    '1. Pré-aqueça o forno a 180°C\n2. Misture os ingredientes secos em uma tigela\n3. Em outra tigela, bata os ovos e adicione o leite e óleo\n4. Misture os ingredientes líquidos aos secos\n5. Bata bem até obter uma massa homogênea\n6. Despeje em forma untada\n7. Asse por 40 minutos ou até dourar',
    '1 hora',
    8,
    'facil',
    1,
    1
);

CALL AdicionarReceita(
    'Lasanha de Carne Tradicional',
    'Lasanha tradicional com molho de carne e queijo, uma receita que agrada toda a família',
    '500g de massa para lasanha\n500g de carne moída\n2 latas de molho de tomate\n500g de queijo mussarela\n200g de presunto\n1 cebola grande\n2 dentes de alho\n1 xícara de leite\n2 colheres de farinha de trigo\n2 colheres de manteiga',
    '1. Refogue a cebola e alho\n2. Adicione a carne moída e temperos\n3. Acrescente o molho de tomate e cozinhe\n4. Prepare o molho branco com leite, farinha e manteiga\n5. Cozinhe a massa conforme instruções\n6. Monte as camadas: massa, carne, presunto, queijo\n7. Finalize com molho branco e queijo\n8. Asse por 45 minutos a 180°C',
    '1 hora e 30 minutos',
    6,
    'medio',
    4,
    2
);

-- Inserir algumas avaliações de exemplo
INSERT INTO avaliacoes (receita_id, usuario_id, nota) VALUES
(1, 2, 5.0),
(1, 3, 4.5),
(2, 3, 4.8),
(2, 4, 4.2);

-- Inserir alguns comentários de exemplo
INSERT INTO comentarios (receita_id, usuario_id, conteudo) VALUES
(1, 2, 'Ficou delicioso! Toda a família adorou.'),
(1, 3, 'Fácil de fazer e muito saboroso. Recomendo!'),
(2, 3, 'A melhor lasanha que já fiz. Obrigada pela receita!');

-- ========================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ========================================

-- Índices compostos para consultas frequentes
CREATE INDEX idx_receitas_categoria_aprovada ON receitas(categoria_id, aprovada, ativa);
CREATE INDEX idx_receitas_autor_data ON receitas(autor_id, data_criacao);
CREATE INDEX idx_avaliacoes_receita_nota ON avaliacoes(receita_id, nota);
CREATE INDEX idx_comentarios_receita_ativo ON comentarios(receita_id, ativo, data_criacao);

-- ========================================
-- CONFIGURAÇÕES DE SEGURANÇA
-- ========================================

-- Configurar timeouts de conexão
SET GLOBAL interactive_timeout = 28800;
SET GLOBAL wait_timeout = 28800;

-- Configurar logs de consultas lentas
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

COMMIT;
