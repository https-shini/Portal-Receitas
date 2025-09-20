const jwt = require("jsonwebtoken");
require("dotenv").config();

const JWT_SECRET = process.env.JWT_SECRET || "supersecretjwtkey";

const authMiddleware = (req, res, next) => {
    const authHeader = req.headers.authorization;

    if (!authHeader || !authHeader.startsWith("Bearer ")) {
        return res.status(401).json({ message: "Token de autenticação não fornecido ou formato inválido" });
    }

    const token = authHeader.split(" ")[1];

    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        req.user = decoded; // Adiciona os dados do usuário decodificados ao objeto request
        next();
    } catch (error) {
        console.error("Erro na verificação do token:", error);
        return res.status(403).json({ message: "Token de autenticação inválido" });
    }
};

module.exports = authMiddleware;
