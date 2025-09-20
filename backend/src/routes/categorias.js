const express = require("express");
const router = express.Router();
const Categoria = require("../models/Categoria");
const authMiddleware = require("../middleware/authMiddleware");
const { body, validationResult } = require("express-validator");

// Middleware de validação para criação/atualização de categoria
const validarCategoria = [
    body("nome").notEmpty().withMessage("Nome da categoria é obrigatório").isLength({ max: 255 }).withMessage("Nome muito longo"),
    body("descricao").optional().isLength({ max: 500 }).withMessage("Descrição muito longa"),
];

/**
 * @route GET /api/categorias
 * @desc Lista todas as categorias ativas
 * @access Public
 */
router.get("/", async (req, res) => {
    try {
        const categorias = await Categoria.listar();
        res.status(200).json({ categorias: categorias.map(c => c.toJSON()) });
    } catch (error) {
        console.error("Erro ao listar categorias:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route GET /api/categorias/:id
 * @desc Obtém detalhes de uma categoria específica
 * @access Public
 */
router.get("/:id", async (req, res) => {
    try {
        const { id } = req.params;
        const categoria = await Categoria.buscarPorId(id);

        if (!categoria || !new Categoria(categoria).ativa) {
            return res.status(404).json({ message: "Categoria não encontrada ou inativa" });
        }

        res.status(200).json({ categoria: new Categoria(categoria).toJSON() });

    } catch (error) {
        console.error("Erro ao obter detalhes da categoria:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route POST /api/categorias
 * @desc Adiciona uma nova categoria (apenas admin)
 * @access Private (admin)
 */
router.post("/", authMiddleware, validarCategoria, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    if (req.user.tipo_usuario !== "admin") {
        return res.status(403).json({ message: "Apenas administradores podem adicionar categorias" });
    }

    const { nome, descricao } = req.body;

    try {
        const novaCategoria = await Categoria.criar({ nome, descricao });
        res.status(201).json({
            message: "Categoria adicionada com sucesso",
            categoria: novaCategoria.toJSON()
        });
    } catch (error) {
        console.error("Erro ao adicionar categoria:", error);
        if (error.message.includes("Categoria já existe")) {
            return res.status(409).json({ message: error.message });
        }
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route PUT /api/categorias/:id
 * @desc Atualiza uma categoria existente (apenas admin)
 * @access Private (admin)
 */
router.put("/:id", authMiddleware, validarCategoria, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    if (req.user.tipo_usuario !== "admin") {
        return res.status(403).json({ message: "Apenas administradores podem atualizar categorias" });
    }

    const { id } = req.params;
    const { nome, descricao, ativa } = req.body;

    try {
        const categoriaExistente = await Categoria.buscarPorId(id);

        if (!categoriaExistente) {
            return res.status(404).json({ message: "Categoria não encontrada" });
        }

        const categoriaObj = new Categoria(categoriaExistente);
        const dadosAtualizacao = { nome, descricao };
        if (ativa !== undefined) dadosAtualizacao.ativa = ativa;

        const categoriaAtualizada = await categoriaObj.atualizar(dadosAtualizacao);

        res.status(200).json({
            message: "Categoria atualizada com sucesso",
            categoria: categoriaAtualizada.toJSON()
        });

    } catch (error) {
        console.error("Erro ao atualizar categoria:", error);
        if (error.message.includes("Categoria já existe")) {
            return res.status(409).json({ message: error.message });
        }
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route DELETE /api/categorias/:id
 * @desc Desativa uma categoria (soft delete) (apenas admin)
 * @access Private (admin)
 */
router.delete("/:id", authMiddleware, async (req, res) => {
    if (req.user.tipo_usuario !== "admin") {
        return res.status(403).json({ message: "Apenas administradores podem desativar categorias" });
    }

    const { id } = req.params;

    try {
        const categoriaExistente = await Categoria.buscarPorId(id);

        if (!categoriaExistente || !new Categoria(categoriaExistente).ativa) {
            return res.status(404).json({ message: "Categoria não encontrada ou já inativa" });
        }

        const categoriaObj = new Categoria(categoriaExistente);
        await categoriaObj.desativar();

        res.status(200).json({ message: "Categoria desativada com sucesso" });

    } catch (error) {
        console.error("Erro ao desativar categoria:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

module.exports = router;
