const express = require("express");
const router = express.Router();
const Usuario = require("../models/Usuario");
const jwt = require("jsonwebtoken");
const bcrypt = require("bcryptjs");
const { body, validationResult } = require("express-validator");
require("dotenv").config();

// Chave secreta para JWT (deve ser uma variável de ambiente em produção)
const JWT_SECRET = process.env.JWT_SECRET || "supersecretjwtkey";

// Middleware de validação para registro de usuário
const validarRegistro = [
    body("nome").notEmpty().withMessage("Nome é obrigatório"),
    body("email").isEmail().withMessage("Email inválido").normalizeEmail(),
    body("senha").isLength({ min: 6 }).withMessage("A senha deve ter no mínimo 6 caracteres"),
];

// Middleware de validação para login de usuário
const validarLogin = [
    body("email").isEmail().withMessage("Email inválido").normalizeEmail(),
    body("senha").notEmpty().withMessage("Senha é obrigatória"),
];

/**
 * @route POST /api/auth/register
 * @desc Registra um novo usuário
 * @access Public
 */
router.post("/register", validarRegistro, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { nome, email, senha, tipo_usuario } = req.body;

    try {
        const novoUsuario = await Usuario.criar({ nome, email, senha, tipo_usuario });
        
        // Gerar token JWT
        const token = jwt.sign(
            { user_id: novoUsuario.id, tipo_usuario: novoUsuario.tipo_usuario },
            JWT_SECRET,
            { expiresIn: "1h" }
        );

        res.status(201).json({
            message: "Usuário registrado com sucesso",
            token,
            usuario: novoUsuario.toJSON()
        });

    } catch (error) {
        console.error("Erro no registro de usuário:", error);
        if (error.message.includes("Email já está em uso")) {
            return res.status(409).json({ message: error.message });
        }
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

/**
 * @route POST /api/auth/login
 * @desc Autentica um usuário e retorna um token JWT
 * @access Public
 */
router.post("/login", validarLogin, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { email, senha } = req.body;

    try {
        const usuario = await Usuario.buscarPorEmail(email);

        if (!usuario) {
            return res.status(401).json({ message: "Credenciais inválidas" });
        }

        if (usuario.isBloqueado()) {
            return res.status(403).json({ message: "Usuário temporariamente bloqueado devido a muitas tentativas de login." });
        }

        const senhaValida = await bcrypt.compare(senha, usuario.senha_hash);

        if (!senhaValida) {
            await Usuario.incrementarTentativasLogin(usuario.id);
            return res.status(401).json({ message: "Credenciais inválidas" });
        }

        // Resetar tentativas de login e atualizar último login
        await Usuario.resetarTentativasLogin(usuario.id);

        // Gerar token JWT
        const token = jwt.sign(
            { user_id: usuario.id, tipo_usuario: usuario.tipo_usuario },
            JWT_SECRET,
            { expiresIn: "1h" }
        );

        res.status(200).json({
            message: "Login realizado com sucesso",
            token,
            usuario: usuario.getDadosSeguro()
        });

    } catch (error) {
        console.error("Erro no login de usuário:", error);
        res.status(500).json({ message: "Erro interno do servidor" });
    }
});

module.exports = router;
