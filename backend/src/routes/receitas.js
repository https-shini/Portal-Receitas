const express = require("express");
const router = express.Router();
const Receita = require("../models/ReceitaSimples");
const Categoria = require("../models/Categoria");
const { body, validationResult } = require("express-validator");
const authMiddleware = require("../middleware/authMiddleware");

// Middleware de validação para criação/atualização de receita
const validarReceita = [
    body("titulo").notEmpty().withMessage("Título é obrigatório").isLength({ max: 255 }).withMessage("Título muito longo"),
    body("ingredientes").notEmpty().withMessage("Ingredientes são obrigatórios"),
    body("modo_preparo").notEmpty().withMessage("Modo de preparo é obrigatório"),
    body("categoria_id").isInt({ min: 1 }).withMessage("ID da categoria inválido"),
    body("porcoes").optional().isInt({ min: 1 }).withMessage("Porções deve ser um número inteiro positivo"),
    body("dificuldade").optional().isIn(["facil", "medio", "dificil"]).withMessage("Dificuldade inválida"),
];

/**
 * @route GET /api/receitas
 * @desc Lista todas as receitas com paginação e filtros
 * @access Public
 */
router.get("/", async (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = parseInt(req.query.limit) || 10;
        const offset = (page - 1) * limit;
        const categoria_id = parseInt(req.query.categoria_id) || null;
        const termo_busca = req.query.busca || "";
        const dificuldade = req.query.dificuldade || null;

        let resultado;
        if (termo_busca || categoria_id || dificuldade) {
            resultado = await Receita.buscar({
                termo_busca,
                categoria_id,
                dificuldade,
                limit,
                offset
            });
        } else {
            resultado = await Receita.listar({
                limit,                offset,
                categoria_id,
                apenas_aprovadas: true // Apenas receitas aprovadas para o público
            });
        }

        res.status(200).json({
            receitas: resultado.receitas.map(r => r.toJSON()),
            total: resultado.total,
            pagina: resultado.pagina,
            totalPaginas: resultado.totalPaginas,
            temProxima: resultado.temProxima,
            temAnterior: resultado.temAnterior
        });

    } catch (error) {
        console.error("Erro ao listar receitas:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route GET /api/receitas/:id
 * @desc Obtém detalhes de uma receita específica
 * @access Public
 */
router.get("/:id", async (req, res) => {
    try {
        const { id } = req.params;
        const receita = await Receita.buscarPorId(id);

        if (!receita || !receita.isAtiva() || !receita.isAprovada()) {
            return res.status(404).json({ message: "Receita não encontrada ou não disponível" });
        }

        res.status(200).json({ receita: new Receita(receita).toJSON() });

    } catch (error) {
        console.error("Erro ao obter detalhes da receita:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route POST /api/receitas
 * @desc Adiciona uma nova receita
 * @access Private (apenas usuários autenticados)
 */
router.post("/", authMiddleware, validarReceita, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { titulo, descricao, ingredientes, modo_preparo, tempo_preparo, porcoes, dificuldade, categoria_id } = req.body;
    const autor_id = req.user.user_id; // ID do usuário autenticado

    try {
        // Verificar se a categoria existe e está ativa
        const categoria = await Categoria.buscarPorId(categoria_id);
        if (!categoria || !new Categoria(categoria).ativa) {
            return res.status(400).json({ message: "Categoria inválida ou inativa" });
        }

        const novaReceita = await Receita.criar({
            titulo, descricao, ingredientes, modo_preparo, tempo_preparo,
            porcoes, dificuldade, categoria_id, autor_id
        });

        res.status(201).json({
            message: "Receita adicionada com sucesso. Aguardando aprovação.",
            receita: novaReceita.toJSON()
        });

    } catch (error) {
        console.error("Erro ao adicionar receita:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route PUT /api/receitas/:id
 * @desc Atualiza uma receita existente
 * @access Private (apenas autor ou admin)
 */
router.put("/:id", authMiddleware, validarReceita, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { titulo, descricao, ingredientes, modo_preparo, tempo_preparo, porcoes, dificuldade, categoria_id, ativa, aprovada } = req.body;
    const userId = req.user.user_id;
    const userTipo = req.user.tipo_usuario;

    try {
        const receitaExistente = await Receita.buscarPorId(id);

        if (!receitaExistente || !new Receita(receitaExistente).isAtiva()) {
            return res.status(404).json({ message: "Receita não encontrada ou inativa" });
        }

        const receitaObj = new Receita(receitaExistente);

        // Verificar permissão
        if (!receitaObj.podeEditar(userId, userTipo)) {
            return res.status(403).json({ message: "Você não tem permissão para editar esta receita" });
        }

        // Verificar se a categoria existe e está ativa
        if (categoria_id) {
            const categoria = await Categoria.buscarPorId(categoria_id);
            if (!categoria || !new Categoria(categoria).ativa) {
                return res.status(400).json({ message: "Categoria inválida ou inativa" });
            }
        }

        const dadosAtualizacao = {
            titulo, descricao, ingredientes, modo_preparo, tempo_preparo,
            porcoes, dificuldade, categoria_id
        };

        // Apenas admins podem alterar status de ativação e aprovação
        if (userTipo === "admin") {
            if (ativa !== undefined) dadosAtualizacao.ativa = ativa;
            if (aprovada !== undefined) dadosAtualizacao.aprovada = aprovada;
        }

        const receitaAtualizada = await receitaObj.atualizar(dadosAtualizacao);

        res.status(200).json({
            message: "Receita atualizada com sucesso",
            receita: receitaAtualizada.toJSON()
        });

    } catch (error) {
        console.error("Erro ao atualizar receita:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route DELETE /api/receitas/:id
 * @desc Desativa uma receita (soft delete)
 * @access Private (apenas autor ou admin)
 */
router.delete("/:id", authMiddleware, async (req, res) => {
    const { id } = req.params;
    const userId = req.user.user_id;
    const userTipo = req.user.tipo_usuario;

    try {
        const receitaExistente = await Receita.buscarPorId(id);

        if (!receitaExistente || !new Receita(receitaExistente).isAtiva()) {
            return res.status(404).json({ message: "Receita não encontrada ou já inativa" });
        }

        const receitaObj = new Receita(receitaExistente);

        // Verificar permissão
        if (!receitaObj.podeEditar(userId, userTipo)) {
            return res.status(403).json({ message: "Você não tem permissão para desativar esta receita" });
        }

        await receitaObj.desativar();

        res.status(200).json({ message: "Receita desativada com sucesso" });

    } catch (error) {
        console.error("Erro ao desativar receita:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

module.exports = router;
