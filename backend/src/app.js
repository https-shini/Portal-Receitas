const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const rateLimit = require("express-rate-limit");
const { testConnection, initializeDatabase, executeQuery } = require("./config/database");
const authRoutes = require("./routes/auth");
const receitaRoutes = require("./routes/receitas");
const categoriaRoutes = require("./routes/categorias");
require("dotenv").config();

const app = express();

// Configurações de segurança
app.use(helmet());

// Habilitar CORS para todas as origens (ajustar em produção)
app.use(cors());

// Rate limiting para prevenir ataques de força bruta e DDoS
const apiLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutos
    max: 100, // Limite de 100 requisições por IP a cada 15 minutos
    message: "Muitas requisições de seu IP, por favor tente novamente após 15 minutos"
});
app.use("/api/auth", apiLimiter); // Aplicar rate limit apenas para rotas de autenticação

// Middleware para parsing de JSON no corpo das requisições
app.use(express.json());

// Rotas da API
app.use("/api/auth", authRoutes);
app.use("/api/receitas", receitaRoutes);
app.use("/api/categorias", categoriaRoutes);

// Rota de teste de conexão com o banco de dados
app.get("/api/health", async (req, res) => {
    const dbConnected = await testConnection();
    if (dbConnected) {
        res.status(200).json({ status: "UP", database: "Connected" });
    } else {
        res.status(500).json({ status: "DOWN", database: "Disconnected" });
    }
});

// Middleware de tratamento de erros (centralizado)
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).send("Algo deu errado!");
});

// Função para aplicar o schema do banco de dados e inserir dados iniciais
const applyDatabaseSchema = async () => {
    try {
        console.log("Aplicando schema do banco de dados...");
        const schemaSql = require("fs").readFileSync("./database/schema_mvp.sql", "utf8");
        
        // Dividir o script em comandos individuais
        const commands = schemaSql.split(";").filter(cmd => cmd.trim().length > 0);

        for (const command of commands) {
            // Ignorar comandos DELIMITER e CALL para execução direta
            if (command.trim().startsWith("DELIMITER") || command.trim().startsWith("CALL")) {
                continue;
            }
            await executeQuery(command);
        }
        console.log("Schema do banco de dados aplicado com sucesso.");
    } catch (error) {
        console.error("Erro ao aplicar schema do banco de dados:", error);
        process.exit(1); // Encerrar a aplicação se o banco não puder ser inicializado
    }
};

// Inicialização do servidor
const startServer = async () => {
    try {
        // Inicializar o banco de dados (criar DB se não existir)
        await initializeDatabase();
        
        // Aplicar o schema completo (tabelas, views, procedures, triggers, dados iniciais)
        await applyDatabaseSchema();

        // Testar conexão com o banco de dados
        const dbConnected = await testConnection();
        if (!dbConnected) {
            console.error("Não foi possível conectar ao banco de dados. Encerrando aplicação.");
            process.exit(1);
        }

        const PORT = process.env.PORT || 5000;
        app.listen(PORT, () => {
            console.log(`🚀 Servidor backend rodando na porta ${PORT}`);
            console.log(`Acesse: http://localhost:${PORT}/api/health`);
        });
    } catch (error) {
        console.error("Erro fatal ao iniciar o servidor:", error);
        process.exit(1);
    }
};

module.exports = { app, startServer };


startServer();


