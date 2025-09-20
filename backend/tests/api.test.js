const request = require('supertest');
const { app } = require('../src/app');

describe('Portal de Receitas API', () => {
    describe('Health Check', () => {
        test('GET /api/health should return status UP', async () => {
            const response = await request(app)
                .get('/api/health')
                .expect(200);

            expect(response.body).toHaveProperty('status', 'UP');
        });
    });

    describe('Categorias', () => {
        test('GET /api/categorias should return list of categories', async () => {
            const response = await request(app)
                .get('/api/categorias')
                .expect(200);

            expect(response.body).toHaveProperty('categorias');
            expect(Array.isArray(response.body.categorias)).toBe(true);
        });
    });

    describe('Receitas', () => {
        test('GET /api/receitas should return list of recipes', async () => {
            const response = await request(app)
                .get('/api/receitas')
                .expect(200);

            expect(response.body).toHaveProperty('receitas');
            expect(Array.isArray(response.body.receitas)).toBe(true);
            expect(response.body).toHaveProperty('total');
            expect(response.body).toHaveProperty('pagina');
        });

        test('GET /api/receitas with search should filter results', async () => {
            const response = await request(app)
                .get('/api/receitas?busca=chocolate')
                .expect(200);

            expect(response.body).toHaveProperty('receitas');
            expect(Array.isArray(response.body.receitas)).toBe(true);
        });
    });

    describe('Authentication', () => {
        test('POST /api/auth/login with invalid credentials should return 401', async () => {
            const response = await request(app)
                .post('/api/auth/login')
                .send({
                    email: 'invalid@email.com',
                    senha: 'wrongpassword'
                })
                .expect(401);

            expect(response.body).toHaveProperty('message');
        });

        test('POST /api/auth/register with invalid data should return 400', async () => {
            const response = await request(app)
                .post('/api/auth/register')
                .send({
                    nome: '',
                    email: 'invalid-email',
                    senha: '123'
                })
                .expect(400);

            expect(response.body).toHaveProperty('errors');
        });
    });
});
