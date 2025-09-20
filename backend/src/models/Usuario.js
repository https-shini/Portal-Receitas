const { executeQuery, callStoredProcedure } = require('../config/database');
const bcrypt = require('bcryptjs');

class Usuario {
    constructor(data) {
        this.id = data.id;
        this.nome = data.nome;
        this.email = data.email;
        this.senha_hash = data.senha_hash;
        this.tipo_usuario = data.tipo_usuario;
        this.ativo = data.ativo;
        this.data_criacao = data.data_criacao;
        this.data_atualizacao = data.data_atualizacao;
        this.ultimo_login = data.ultimo_login;
        this.tentativas_login = data.tentativas_login;
        this.bloqueado_ate = data.bloqueado_ate;
    }

    // Método para criar um novo usuário
    static async criar(dadosUsuario) {
        try {
            const { nome, email, senha, tipo_usuario = 'regular' } = dadosUsuario;
            
            // Verificar se email já existe
            const usuarioExistente = await this.buscarPorEmail(email);
            if (usuarioExistente) {
                throw new Error('Email já está em uso');
            }

            // Hash da senha
            const senha_hash = await bcrypt.hash(senha, 12);

            const query = `
                INSERT INTO usuarios (nome, email, senha_hash, tipo_usuario)
                VALUES (?, ?, ?, ?)
            `;
            
            const resultado = await executeQuery(query, [nome, email, senha_hash, tipo_usuario]);
            
            // Buscar o usuário criado
            const novoUsuario = await this.buscarPorId(resultado.insertId);
            return new Usuario(novoUsuario);
            
        } catch (error) {
            console.error('Erro ao criar usuário:', error);
            throw error;
        }
    }

    // Método para buscar usuário por ID
    static async buscarPorId(id) {
        try {
            const query = 'SELECT * FROM usuarios WHERE id = ? AND ativo = TRUE';
            const resultado = await executeQuery(query, [id]);
            
            if (resultado.length === 0) {
                return null;
            }
            
            return resultado[0];
        } catch (error) {
            console.error('Erro ao buscar usuário por ID:', error);
            throw error;
        }
    }

    // Método para buscar usuário por email
    static async buscarPorEmail(email) {
        try {
            const query = 'SELECT * FROM usuarios WHERE email = ? AND ativo = TRUE';
            const resultado = await executeQuery(query, [email]);
            
            if (resultado.length === 0) {
                return null;
            }
            
            return resultado[0];
        } catch (error) {
            console.error('Erro ao buscar usuário por email:', error);
            throw error;
        }
    }

    // Método para autenticar usuário usando stored procedure
    static async autenticar(email, senha) {
        try {
            // Primeiro, buscar o usuário para verificar a senha
            const usuario = await this.buscarPorEmail(email);
            if (!usuario) {
                return { sucesso: false, mensagem: 'Usuário não encontrado' };
            }

            // Verificar senha
            const senhaValida = await bcrypt.compare(senha, usuario.senha_hash);
            if (!senhaValida) {
                // Incrementar tentativas de login
                await this.incrementarTentativasLogin(usuario.id);
                return { sucesso: false, mensagem: 'Senha incorreta' };
            }

            // Chamar stored procedure para autenticação
            const resultado = await callStoredProcedure('AutenticarUsuario', [email, usuario.senha_hash]);
            
            if (resultado && resultado[0] && resultado[0].length > 0) {
                const resposta = resultado[0][0];
                
                if (resposta.status === 'SUCESSO') {
                    return {
                        sucesso: true,
                        usuario: new Usuario(resposta),
                        mensagem: resposta.mensagem
                    };
                } else {
                    return {
                        sucesso: false,
                        mensagem: resposta.mensagem
                    };
                }
            }
            
            return { sucesso: false, mensagem: 'Erro na autenticação' };
            
        } catch (error) {
            console.error('Erro na autenticação:', error);
            throw error;
        }
    }

    // Método para incrementar tentativas de login
    static async incrementarTentativasLogin(usuarioId) {
        try {
            const query = `
                UPDATE usuarios 
                SET tentativas_login = tentativas_login + 1,
                    bloqueado_ate = CASE 
                        WHEN tentativas_login >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                        ELSE bloqueado_ate
                    END
                WHERE id = ?
            `;
            
            await executeQuery(query, [usuarioId]);
        } catch (error) {
            console.error('Erro ao incrementar tentativas de login:', error);
            throw error;
        }
    }

    // Método para resetar tentativas de login
    static async resetarTentativasLogin(usuarioId) {
        try {
            const query = `
                UPDATE usuarios 
                SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_login = NOW()
                WHERE id = ?
            `;
            
            await executeQuery(query, [usuarioId]);
        } catch (error) {
            console.error('Erro ao resetar tentativas de login:', error);
            throw error;
        }
    }

    // Método para listar usuários com paginação
    static async listar(limite = 10, offset = 0, filtros = {}) {
        try {
            let query = `
                SELECT id, nome, email, tipo_usuario, ativo, data_criacao, ultimo_login
                FROM usuarios 
                WHERE 1=1
            `;
            const params = [];

            // Aplicar filtros
            if (filtros.ativo !== undefined) {
                query += ' AND ativo = ?';
                params.push(filtros.ativo);
            }

            if (filtros.tipo_usuario) {
                query += ' AND tipo_usuario = ?';
                params.push(filtros.tipo_usuario);
            }

            if (filtros.busca) {
                query += ' AND (nome LIKE ? OR email LIKE ?)';
                params.push(`%${filtros.busca}%`, `%${filtros.busca}%`);
            }

            query += ' ORDER BY data_criacao DESC LIMIT ? OFFSET ?';
            params.push(limite, offset);

            const usuarios = await executeQuery(query, params);
            
            // Contar total de registros
            let countQuery = 'SELECT COUNT(*) as total FROM usuarios WHERE 1=1';
            const countParams = [];

            if (filtros.ativo !== undefined) {
                countQuery += ' AND ativo = ?';
                countParams.push(filtros.ativo);
            }

            if (filtros.tipo_usuario) {
                countQuery += ' AND tipo_usuario = ?';
                countParams.push(filtros.tipo_usuario);
            }

            if (filtros.busca) {
                countQuery += ' AND (nome LIKE ? OR email LIKE ?)';
                countParams.push(`%${filtros.busca}%`, `%${filtros.busca}%`);
            }

            const totalResult = await executeQuery(countQuery, countParams);
            const total = totalResult[0].total;

            return {
                usuarios: usuarios.map(u => new Usuario(u)),
                total,
                pagina: Math.floor(offset / limite) + 1,
                totalPaginas: Math.ceil(total / limite)
            };
            
        } catch (error) {
            console.error('Erro ao listar usuários:', error);
            throw error;
        }
    }

    // Método para atualizar usuário
    async atualizar(dadosAtualizacao) {
        try {
            const camposPermitidos = ['nome', 'email', 'tipo_usuario', 'ativo'];
            const campos = [];
            const valores = [];

            // Construir query dinamicamente
            for (const [campo, valor] of Object.entries(dadosAtualizacao)) {
                if (camposPermitidos.includes(campo)) {
                    campos.push(`${campo} = ?`);
                    valores.push(valor);
                }
            }

            if (campos.length === 0) {
                throw new Error('Nenhum campo válido para atualização');
            }

            valores.push(this.id);

            const query = `
                UPDATE usuarios 
                SET ${campos.join(', ')}, data_atualizacao = NOW()
                WHERE id = ?
            `;

            await executeQuery(query, valores);

            // Recarregar dados do usuário
            const usuarioAtualizado = await Usuario.buscarPorId(this.id);
            Object.assign(this, usuarioAtualizado);

            return this;
            
        } catch (error) {
            console.error('Erro ao atualizar usuário:', error);
            throw error;
        }
    }

    // Método para alterar senha
    async alterarSenha(senhaAtual, novaSenha) {
        try {
            // Verificar senha atual
            const senhaValida = await bcrypt.compare(senhaAtual, this.senha_hash);
            if (!senhaValida) {
                throw new Error('Senha atual incorreta');
            }

            // Hash da nova senha
            const novaSenhaHash = await bcrypt.hash(novaSenha, 12);

            const query = `
                UPDATE usuarios 
                SET senha_hash = ?, data_atualizacao = NOW()
                WHERE id = ?
            `;

            await executeQuery(query, [novaSenhaHash, this.id]);
            this.senha_hash = novaSenhaHash;

            return true;
            
        } catch (error) {
            console.error('Erro ao alterar senha:', error);
            throw error;
        }
    }

    // Método para desativar usuário (soft delete)
    async desativar() {
        try {
            const query = `
                UPDATE usuarios 
                SET ativo = FALSE, data_atualizacao = NOW()
                WHERE id = ?
            `;

            await executeQuery(query, [this.id]);
            this.ativo = false;

            return true;
            
        } catch (error) {
            console.error('Erro ao desativar usuário:', error);
            throw error;
        }
    }

    // Método para verificar se usuário é administrador
    isAdmin() {
        return this.tipo_usuario === 'admin';
    }

    // Método para verificar se usuário está ativo
    isAtivo() {
        return this.ativo === true || this.ativo === 1;
    }

    // Método para verificar se usuário está bloqueado
    isBloqueado() {
        if (!this.bloqueado_ate) return false;
        return new Date(this.bloqueado_ate) > new Date();
    }

    // Método para serializar dados do usuário (sem senha)
    toJSON() {
        const { senha_hash, ...dadosPublicos } = this;
        return dadosPublicos;
    }

    // Método para obter dados seguros do usuário
    getDadosSeguro() {
        return {
            id: this.id,
            nome: this.nome,
            email: this.email,
            tipo_usuario: this.tipo_usuario,
            ativo: this.ativo,
            data_criacao: this.data_criacao,
            ultimo_login: this.ultimo_login
        };
    }
}

module.exports = Usuario;
