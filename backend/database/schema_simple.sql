-- Portal de Receitas - Schema Simplificado para MVP
-- Versão: MVP (Produto Mínimo Viável) - Fase 2
-- Data: 19 de setembro de 2025

-- ========================================
-- CRIAÇÃO DAS TABELAS PRINCIPAIS
-- ========================================

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT,
    ativa BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categorias_nome (nome),
    INDEX idx_categorias_ativa (ativa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('admin', 'usuario') DEFAULT 'usuario',
    ativo BOOLEAN DEFAULT TRUE,
    tentativas_login INT DEFAULT 0,
    ultimo_login TIMESTAMP NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuarios_email (email),
    INDEX idx_usuarios_tipo (tipo_usuario),
    INDEX idx_usuarios_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de receitas
CREATE TABLE IF NOT EXISTS receitas (
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
    aprovada BOOLEAN DEFAULT FALSE,
    ativa BOOLEAN DEFAULT TRUE,
    visualizacoes INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_receitas_categoria (categoria_id),
    INDEX idx_receitas_autor (autor_id),
    INDEX idx_receitas_aprovada (aprovada),
    INDEX idx_receitas_ativa (ativa),
    INDEX idx_receitas_titulo (titulo),
    INDEX idx_receitas_dificuldade (dificuldade),
    FULLTEXT INDEX idx_receitas_busca (titulo, descricao, ingredientes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de avaliações
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receita_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nota INT NOT NULL CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_avaliacao (receita_id, usuario_id),
    INDEX idx_avaliacoes_receita (receita_id),
    INDEX idx_avaliacoes_usuario (usuario_id),
    INDEX idx_avaliacoes_nota (nota)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- VIEWS PARA CONSULTAS OTIMIZADAS
-- ========================================

-- View para receitas com informações completas
CREATE OR REPLACE VIEW vw_receitas_completas AS
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
    r.aprovada,
    r.ativa,
    r.data_criacao,
    r.data_atualizacao,
    c.nome AS categoria_nome,
    c.id AS categoria_id,
    u.nome AS autor_nome,
    u.id AS autor_id,
    COALESCE(AVG(av.nota), 0) AS media_avaliacoes,
    COUNT(av.id) AS total_avaliacoes
FROM receitas r
INNER JOIN categorias c ON r.categoria_id = c.id
INNER JOIN usuarios u ON r.autor_id = u.id
LEFT JOIN avaliacoes av ON r.id = av.receita_id
WHERE r.ativa = TRUE AND c.ativa = TRUE AND u.ativo = TRUE
GROUP BY r.id, r.titulo, r.descricao, r.ingredientes, r.modo_preparo, 
         r.tempo_preparo, r.porcoes, r.dificuldade, r.visualizacoes, 
         r.aprovada, r.ativa, r.data_criacao, r.data_atualizacao,
         c.nome, c.id, u.nome, u.id;

-- View para estatísticas de categorias
CREATE OR REPLACE VIEW vw_categorias_estatisticas AS
SELECT 
    c.id,
    c.nome,
    c.descricao,
    c.ativa,
    COUNT(r.id) AS total_receitas,
    COUNT(CASE WHEN r.aprovada = TRUE THEN 1 END) AS receitas_aprovadas,
    COALESCE(AVG(av.nota), 0) AS media_avaliacoes_categoria
FROM categorias c
LEFT JOIN receitas r ON c.id = r.categoria_id AND r.ativa = TRUE
LEFT JOIN avaliacoes av ON r.id = av.receita_id
WHERE c.ativa = TRUE
GROUP BY c.id, c.nome, c.descricao, c.ativa;

-- ========================================
-- STORED PROCEDURES
-- ========================================

DELIMITER //

-- Procedure para buscar receitas com filtros
CREATE PROCEDURE IF NOT EXISTS sp_buscar_receitas(
    IN p_termo_busca VARCHAR(255),
    IN p_categoria_id INT,
    IN p_dificuldade VARCHAR(20),
    IN p_limite INT,
    IN p_offset INT,
    IN p_apenas_aprovadas BOOLEAN
)
BEGIN
    DECLARE sql_query TEXT;
    DECLARE where_clause TEXT DEFAULT '';
    
    SET sql_query = 'SELECT * FROM vw_receitas_completas WHERE 1=1';
    
    IF p_apenas_aprovadas THEN
        SET where_clause = CONCAT(where_clause, ' AND aprovada = TRUE');
    END IF;
    
    IF p_termo_busca IS NOT NULL AND p_termo_busca != '' THEN
        SET where_clause = CONCAT(where_clause, ' AND (titulo LIKE ''%', p_termo_busca, '%'' OR descricao LIKE ''%', p_termo_busca, '%'' OR ingredientes LIKE ''%', p_termo_busca, '%'')');
    END IF;
    
    IF p_categoria_id IS NOT NULL AND p_categoria_id > 0 THEN
        SET where_clause = CONCAT(where_clause, ' AND categoria_id = ', p_categoria_id);
    END IF;
    
    IF p_dificuldade IS NOT NULL AND p_dificuldade != '' THEN
        SET where_clause = CONCAT(where_clause, ' AND dificuldade = ''', p_dificuldade, '''');
    END IF;
    
    SET sql_query = CONCAT(sql_query, where_clause, ' ORDER BY data_criacao DESC');
    
    IF p_limite IS NOT NULL AND p_limite > 0 THEN
        SET sql_query = CONCAT(sql_query, ' LIMIT ', p_limite);
        
        IF p_offset IS NOT NULL AND p_offset > 0 THEN
            SET sql_query = CONCAT(sql_query, ' OFFSET ', p_offset);
        END IF;
    END IF;
    
    SET @sql = sql_query;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //

-- Procedure para incrementar visualizações
CREATE PROCEDURE IF NOT EXISTS sp_incrementar_visualizacoes(
    IN p_receita_id INT
)
BEGIN
    UPDATE receitas 
    SET visualizacoes = visualizacoes + 1 
    WHERE id = p_receita_id AND ativa = TRUE;
END //

DELIMITER ;

-- ========================================
-- TRIGGERS
-- ========================================

-- Trigger para validar dados antes de inserir receita
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_receitas_before_insert
BEFORE INSERT ON receitas
FOR EACH ROW
BEGIN
    -- Validar se categoria existe e está ativa
    IF NOT EXISTS (SELECT 1 FROM categorias WHERE id = NEW.categoria_id AND ativa = TRUE) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Categoria inválida ou inativa';
    END IF;
    
    -- Validar se usuário existe e está ativo
    IF NOT EXISTS (SELECT 1 FROM usuarios WHERE id = NEW.autor_id AND ativo = TRUE) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuário inválido ou inativo';
    END IF;
    
    -- Garantir que título não seja vazio
    IF TRIM(NEW.titulo) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Título da receita não pode ser vazio';
    END IF;
END //
DELIMITER ;

-- ========================================
-- DADOS INICIAIS
-- ========================================

-- Inserir categorias padrão
INSERT IGNORE INTO categorias (nome, descricao) VALUES
('Doces', 'Sobremesas, bolos, tortas e outras delícias doces'),
('Salgados', 'Pratos principais, aperitivos e lanches salgados'),
('Massas', 'Macarrão, lasanha, nhoque e outras massas'),
('Carnes', 'Pratos com carne bovina, suína, frango e outras carnes'),
('Peixes e Frutos do Mar', 'Receitas com peixes, camarão, lula e frutos do mar'),
('Vegetarianas', 'Pratos sem carne, adequados para vegetarianos'),
('Veganas', 'Receitas 100% vegetais, sem produtos de origem animal'),
('Bebidas', 'Sucos, vitaminas, drinks e outras bebidas'),
('Saladas', 'Saladas frescas e nutritivas'),
('Sopas', 'Sopas quentes e frias para todas as ocasiões');

-- Inserir usuário administrador padrão
INSERT IGNORE INTO usuarios (nome, email, senha_hash, tipo_usuario) VALUES
('Administrador', 'admin@portalreceitas.com', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Chef Maria Silva', 'maria@portalreceitas.com', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
('João Cozinheiro', 'joao@portalreceitas.com', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario');

-- Inserir receitas de exemplo
INSERT IGNORE INTO receitas (titulo, descricao, ingredientes, modo_preparo, tempo_preparo, porcoes, dificuldade, categoria_id, autor_id, aprovada) VALUES
('Bolo de Chocolate Simples', 
 'Um delicioso bolo de chocolate fácil de fazer, perfeito para qualquer ocasião.',
 '2 xícaras de farinha de trigo\n1 xícara de açúcar\n1/2 xícara de chocolate em pó\n1 xícara de leite\n3 ovos\n1/2 xícara de óleo\n1 colher de sopa de fermento em pó',
 '1. Pré-aqueça o forno a 180°C\n2. Em uma tigela, misture todos os ingredientes secos\n3. Em outra tigela, bata os ovos, adicione o leite e o óleo\n4. Misture os ingredientes líquidos aos secos\n5. Despeje em forma untada\n6. Asse por 40 minutos',
 '1 hora',
 8,
 'facil',
 1,
 2,
 TRUE),

('Macarrão à Carbonara', 
 'Clássica receita italiana de macarrão com molho cremoso de ovos e bacon.',
 '400g de espaguete\n200g de bacon em cubos\n4 gemas de ovo\n1 xícara de queijo parmesão ralado\n2 dentes de alho\nSal e pimenta do reino a gosto\nSalsinha picada',
 '1. Cozinhe o macarrão al dente\n2. Frite o bacon até ficar crocante\n3. Misture as gemas com o queijo parmesão\n4. Escorra o macarrão e misture com o bacon\n5. Adicione a mistura de ovos e queijo\n6. Mexa rapidamente para criar o molho cremoso\n7. Tempere e sirva imediatamente',
 '30 minutos',
 4,
 'medio',
 3,
 3,
 TRUE),

('Salada Caesar', 
 'Refrescante salada com alface americana, croutons e molho caesar caseiro.',
 '1 pé de alface americana\n1/2 xícara de queijo parmesão\n1 xícara de croutons\n2 filés de anchova\n2 dentes de alho\n1 gema de ovo\n3 colheres de sopa de azeite\n1 colher de sopa de suco de limão\nSal e pimenta a gosto',
 '1. Lave e corte a alface em pedaços\n2. Prepare o molho misturando alho, anchova, gema, azeite e limão\n3. Tempere o molho com sal e pimenta\n4. Misture a alface com o molho\n5. Adicione o queijo parmesão e os croutons\n6. Sirva imediatamente',
 '20 minutos',
 4,
 'facil',
 9,
 2,
 TRUE);

-- Inserir algumas avaliações de exemplo
INSERT IGNORE INTO avaliacoes (receita_id, usuario_id, nota, comentario) VALUES
(1, 3, 5, 'Bolo delicioso e muito fácil de fazer! Toda a família adorou.'),
(1, 1, 4, 'Receita excelente, ficou muito saboroso.'),
(2, 2, 5, 'Perfeita! Exatamente como na Itália.'),
(3, 1, 4, 'Salada fresca e saborosa, molho muito bom.');
