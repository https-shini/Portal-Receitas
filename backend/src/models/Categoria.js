const { executeQuery } = require("../config/database");

class Categoria {
    constructor(data) {
        this.id = data.id;
        this.nome = data.nome;
        this.descricao = data.descricao;
        this.ativa = data.ativa;
        this.data_criacao = data.data_criacao;
        this.data_atualizacao = data.data_atualizacao;
    }

    // Método para criar uma nova categoria
    static async criar(dadosCategoria) {
        try {
            const { nome, descricao = "" } = dadosCategoria;

            // Verificar se categoria já existe
            const categoriaExistente = await this.buscarPorNome(nome);
            if (categoriaExistente) {
                throw new Error("Categoria já existe");
            }

            const query = `
                INSERT INTO categorias (nome, descricao)
                VALUES (?, ?)
            `;
            const resultado = await executeQuery(query, [nome, descricao]);
            
            // Buscar a categoria criada
            const novaCategoria = await this.buscarPorId(resultado.insertId);
            return new Categoria(novaCategoria);

        } catch (error) {
            console.error("Erro ao criar categoria:", error);
            throw error;
        }
    }

    // Método para buscar categoria por ID
    static async buscarPorId(id) {
        try {
            const query = "SELECT * FROM categorias WHERE id = ? AND ativa = TRUE";
            const resultado = await executeQuery(query, [id]);

            if (resultado.length === 0) {
                return null;
            }

            return resultado[0];
        } catch (error) {
            console.error("Erro ao buscar categoria por ID:", error);
            throw error;
        }
    }

    // Método para buscar categoria por nome
    static async buscarPorNome(nome) {
        try {
            const query = "SELECT * FROM categorias WHERE nome = ? AND ativa = TRUE";
            const resultado = await executeQuery(query, [nome]);

            if (resultado.length === 0) {
                return null;
            }

            return resultado[0];
        } catch (error) {
            console.error("Erro ao buscar categoria por nome:", error);
            throw error;
        }
    }

    // Método para listar todas as categorias ativas
    static async listar() {
        try {
            const query = "SELECT * FROM categorias WHERE ativa = TRUE ORDER BY nome ASC";
            const resultado = await executeQuery(query);
            return resultado.map(cat => new Categoria(cat));
        } catch (error) {
            console.error("Erro ao listar categorias:", error);
            throw error;
        }
    }

    // Método para atualizar categoria
    async atualizar(dadosAtualizacao) {
        try {
            const camposPermitidos = ["nome", "descricao", "ativa"];
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
                UPDATE categorias 
                SET ${campos.join(", ")}, data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = ?
            `;

            await executeQuery(query, valores);

            // Recarregar dados da categoria
            const categoriaAtualizada = await Categoria.buscarPorId(this.id);
            Object.assign(this, categoriaAtualizada);

            return this;

        } catch (error) {
            console.error("Erro ao atualizar categoria:", error);
            throw error;
        }
    }

    // Método para desativar categoria (soft delete)
    async desativar() {
        try {
            return await this.atualizar({ ativa: false });
        } catch (error) {
            console.error("Erro ao desativar categoria:", error);
            throw error;
        }
    }

    // Método para ativar categoria
    async ativar() {
        try {
            return await this.atualizar({ ativa: true });
        } catch (error) {
            console.error("Erro ao ativar categoria:", error);
            throw error;
        }
    }

    // Método para serializar dados da categoria
    toJSON() {
        return {
            id: this.id,
            nome: this.nome,
            descricao: this.descricao,
            ativa: this.ativa,
            data_criacao: this.data_criacao,
            data_atualizacao: this.data_atualizacao
        };
    }
}

module.exports = Categoria;
