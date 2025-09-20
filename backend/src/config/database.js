const mysql = require('mysql2/promise');
require('dotenv').config();

// Configuração do pool de conexões MySQL
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'portal_receitas',
    port: process.env.DB_PORT || 3306,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    acquireTimeout: 60000,
    timeout: 60000,
    reconnect: true,
    charset: 'utf8mb4'
});

// Função para testar a conexão
const testConnection = async () => {
    try {
        const connection = await pool.getConnection();
        console.log('✅ Conexão com MySQL estabelecida com sucesso');
        connection.release();
        return true;
    } catch (error) {
        console.error('❌ Erro ao conectar com MySQL:', error.message);
        return false;
    }
};

// Função para executar queries
const executeQuery = async (query, params = []) => {
    try {
        const [rows] = await pool.execute(query, params);
        return rows;
    } catch (error) {
        console.error('Erro ao executar query:', error);
        throw error;
    }
};

// Função para executar stored procedures
const callStoredProcedure = async (procedureName, params = []) => {
    try {
        const placeholders = params.map(() => '?').join(', ');
        const query = `CALL ${procedureName}(${placeholders})`;
        const [rows] = await pool.execute(query, params);
        return rows;
    } catch (error) {
        console.error(`Erro ao executar stored procedure ${procedureName}:`, error);
        throw error;
    }
};

// Função para inicializar o banco de dados
const initializeDatabase = async () => {
    try {
        console.log('🔄 Inicializando banco de dados...');
        
        // Verificar se o banco existe, se não, criar
        const createDbQuery = `CREATE DATABASE IF NOT EXISTS ${process.env.DB_NAME || 'portal_receitas'} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`;
        await pool.execute(createDbQuery);
        
        console.log('✅ Banco de dados inicializado com sucesso');
        return true;
    } catch (error) {
        console.error('❌ Erro ao inicializar banco de dados:', error);
        return false;
    }
};

// Função para executar transações
const executeTransaction = async (queries) => {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();
        
        const results = [];
        for (const { query, params } of queries) {
            const [result] = await connection.execute(query, params || []);
            results.push(result);
        }
        
        await connection.commit();
        return results;
    } catch (error) {
        await connection.rollback();
        throw error;
    } finally {
        connection.release();
    }
};

module.exports = {
    pool,
    testConnection,
    executeQuery,
    callStoredProcedure,
    initializeDatabase,
    executeTransaction
};
