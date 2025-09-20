const { executeQuery } = require("../config/database");

class Receita {
    constructor(data) {
        this.id = data.id;
        this.titulo = data.titulo;
        this.descricao = data.descricao;
        this.ingredientes = data.ingredientes;
        this.modo_preparo = data.modo_preparo;
        this.tempo_preparo = data.tempo_preparo;
        this.porcoes = data.porcoes;
        this.dificuldade = data.dificuldade;
        this.categoria_id = data.categoria_id;
        this.autor_id = data.autor_id;
        this.aprovada = data.aprovada;
        this.ativa = data.ativa;
        this.visualizacoes = data.visualizacoes;
        this.data_criacao = data.data_criacao;
        this.data_atualizacao = data.data_atualizacao;
        
        // Campos adicionais da view
        this.categoria_nome = data.categoria_nome;
        this.autor_nome = data.autor_nome;
        this.media_avaliacoes = data.media_avaliacoes || 0;
        this.total_avaliacoes = data.total_avaliacoes || 0;
    }

    // Método para criar uma nova receita
    static async criar(dadosReceita) {
        try {
            const {
                titulo,
                descricao = "",
                ingredientes,
                modo_preparo,
                tempo_preparo = null,
                porcoes = 1,
                dificuldade = "medio",
                categoria_id,
                autor_id
            } = dadosReceita;

            const query = `
                INSERT INTO receitas (titulo, descricao, ingredientes, modo_preparo, tempo_preparo, porcoes, dificuldade, categoria_id, autor_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            `;
            
            const resultado = await executeQuery(query, [
                titulo, descricao, ingredientes, modo_preparo, tempo_preparo, porcoes, dificuldade, categoria_id, autor_id
            ]);
            
            // Buscar a receita criada
            const novaReceita = await this.buscarPorId(resultado.insertId);
            return new Receita(novaReceita);

        } catch (error) {
            console.error("Erro ao criar receita:", error);
            throw error;
        }
    }

    // Método para buscar receita por ID
    static async buscarPorId(id) {
        try {
            const query = "SELECT * FROM vw_receitas_completas WHERE id = ?";
            const resultado = await executeQuery(query, [id]);

            if (resultado.length === 0) {
                return null;
            }

            return resultado[0];
        } catch (error) {
            console.error("Erro ao buscar receita por ID:", error);
            throw error;
        }
    }

    // Método para listar receitas
    static async listar(opcoes = {}) {
        try {
            const {
                limit = 10,
                offset = 0,
                categoria_id = null,
                apenas_aprovadas = true
            } = opcoes;

            let query = "SELECT * FROM vw_receitas_completas WHERE 1=1";
            const params = [];

            if (apenas_aprovadas) {
                query += " AND aprovada = TRUE";
            }

            if (categoria_id) {
                query += " AND categoria_id = ?";
                params.push(categoria_id);
            }

            query += ` ORDER BY data_criacao DESC LIMIT ${limit} OFFSET ${offset}`;

            const receitas = await executeQuery(query, params);

            // Contar total
            let countQuery = "SELECT COUNT(*) as total FROM vw_receitas_completas WHERE 1=1";
            const countParams = [];

            if (apenas_aprovadas) {
                countQuery += " AND aprovada = TRUE";
            }

            if (categoria_id) {
                countQuery += " AND categoria_id = ?";
                countParams.push(categoria_id);
            }

            const totalResult = await executeQuery(countQuery, countParams);
            const total = totalResult[0].total;

            const totalPaginas = Math.ceil(total / limit);
            const pagina = Math.floor(offset / limit) + 1;

            return {
                receitas: receitas.map(r => new Receita(r)),
                total,
                pagina,
                totalPaginas,
                temProxima: pagina < totalPaginas,
                temAnterior: pagina > 1
            };

        } catch (error) {
            console.error("Erro ao listar receitas:", error);
            throw error;
        }
    }

    // Método para buscar receitas
    static async buscar(opcoes = {}) {
        try {
            const {
                termo_busca = "",
                categoria_id = null,
                dificuldade = null,
                limit = 10,
                offset = 0
            } = opcoes;

            let query = "SELECT * FROM vw_receitas_completas WHERE aprovada = TRUE";
            const params = [];

            if (termo_busca && termo_busca.trim()) {
                query += " AND (titulo LIKE ? OR descricao LIKE ? OR ingredientes LIKE ?)";
                const searchTerm = `%${termo_busca.trim()}%`;
                params.push(searchTerm, searchTerm, searchTerm);
            }

            if (categoria_id) {
                query += " AND categoria_id = ?";
                params.push(categoria_id);
            }

            if (dificuldade) {
                query += " AND dificuldade = ?";
                params.push(dificuldade);
            }

            query += ` ORDER BY data_criacao DESC LIMIT ${limit} OFFSET ${offset}`;

            const receitas = await executeQuery(query, params);

            // Contar total
            let countQuery = "SELECT COUNT(*) as total FROM vw_receitas_completas WHERE aprovada = TRUE";
            const countParams = [];

            if (termo_busca && termo_busca.trim()) {
                countQuery += " AND (titulo LIKE ? OR descricao LIKE ? OR ingredientes LIKE ?)";
                const searchTerm = `%${termo_busca.trim()}%`;
                countParams.push(searchTerm, searchTerm, searchTerm);
            }

            if (categoria_id) {
                countQuery += " AND categoria_id = ?";
                countParams.push(categoria_id);
            }

            if (dificuldade) {
                countQuery += " AND dificuldade = ?";
                countParams.push(dificuldade);
            }

            const totalResult = await executeQuery(countQuery, countParams);
            const total = totalResult[0].total;

            const totalPaginas = Math.ceil(total / limit);
            const pagina = Math.floor(offset / limit) + 1;

            return {
                receitas: receitas.map(r => new Receita(r)),
                total,
                pagina,
                totalPaginas,
                temProxima: pagina < totalPaginas,
                temAnterior: pagina > 1
            };

        } catch (error) {
            console.error("Erro ao buscar receitas:", error);
            throw error;
        }
    }

    // Método para atualizar receita
    async atualizar(dadosAtualizacao) {
        try {
            const camposPermitidos = [
                "titulo", "descricao", "ingredientes", "modo_preparo", 
                "tempo_preparo", "porcoes", "dificuldade", "categoria_id", 
                "aprovada", "ativa"
            ];
            const campos = [];
            const valores = [];

            for (const [campo, valor] of Object.entries(dadosAtualizacao)) {
                if (camposPermitidos.includes(campo)) {
                    campos.push(`${campo} = ?`);
                    valores.push(valor);
                }
            }

            if (campos.length === 0) {
                throw new Error("Nenhum campo válido para atualização");
            }

            valores.push(this.id);

            const query = `
                UPDATE receitas 
                SET ${campos.join(", ")}, data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = ?
            `;

            await executeQuery(query, valores);

            // Recarregar dados da receita
            const receitaAtualizada = await Receita.buscarPorId(this.id);
            Object.assign(this, receitaAtualizada);

            return this;

        } catch (error) {
            console.error("Erro ao atualizar receita:", error);
            throw error;
        }
    }

    // Método para desativar receita (soft delete)
    async desativar() {
        try {
            return await this.atualizar({ ativa: false });
        } catch (error) {
            console.error("Erro ao desativar receita:", error);
            throw error;
        }
    }

    // Método para incrementar visualizações
    async incrementarVisualizacoes() {
        try {
            const query = "UPDATE receitas SET visualizacoes = visualizacoes + 1 WHERE id = ?";
            await executeQuery(query, [this.id]);
            this.visualizacoes += 1;
        } catch (error) {
            console.error("Erro ao incrementar visualizações:", error);
            throw error;
        }
    }

    // Métodos de verificação
    isAtiva() {
        return this.ativa === true || this.ativa === 1;
    }

    isAprovada() {
        return this.aprovada === true || this.aprovada === 1;
    }

    podeEditar(userId, userTipo) {
        return userTipo === 'admin' || this.autor_id === userId;
    }

    // Método para serializar dados da receita
    toJSON() {
        return {
            id: this.id,
            titulo: this.titulo,
            descricao: this.descricao,
            ingredientes: this.ingredientes,
            modo_preparo: this.modo_preparo,
            tempo_preparo: this.tempo_preparo,
            porcoes: this.porcoes,
            dificuldade: this.dificuldade,
            categoria_id: this.categoria_id,
            categoria_nome: this.categoria_nome,
            autor_id: this.autor_id,
            autor_nome: this.autor_nome,
            aprovada: this.aprovada,
            ativa: this.ativa,
            visualizacoes: this.visualizacoes,
            media_avaliacoes: this.media_avaliacoes,
            total_avaliacoes: this.total_avaliacoes,
            data_criacao: this.data_criacao,
            data_atualizacao: this.data_atualizacao
        };
    }
}

module.exports = Receita;
