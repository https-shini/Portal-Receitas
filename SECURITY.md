# Política de Segurança

Este documento descreve como o projeto trata **arquivos sensíveis**, o
**versionamento seguro** e como **relatar vulnerabilidades**.

## Arquivos que NUNCA devem ser versionados

O `.gitignore` bloqueia, entre outros:

| Categoria | Exemplos |
|-----------|----------|
| Ambiente local | `.env`, `.env.*` (exceto `.env.example`), `*.local` |
| Segredos | `secrets.*`, `*.secret`, tokens, chaves de API |
| Chaves/certificados | `*.pem`, `*.key`, `*.crt`, `*.p12`, `*.pfx`, `id_rsa`, `*.gpg` |
| Dependências/artefatos | `vendor/`, `build/`, caches de PHPUnit/PHPStan |
| Logs e temporários | `*.log`, `logs/`, `tmp/`, `*.bak` |
| Dados de runtime | `uploads/`, `storage/` |
| SO / IDE | `.DS_Store`, `Thumbs.db`, `.idea/`, `.vscode/` |

**Configuração:** copie `.env.example` para `.env` e preencha os valores
locais. O `.env` real nunca entra no Git. Em produção (docker-compose,
Render) as variáveis vêm do ambiente/secret manager da plataforma —
consulte [README.md](README.md#️-configuração-variáveis-de-ambiente) e
[DEPLOY.md](DEPLOY.md).

## Boas práticas ao contribuir

- Nunca faça commit de credenciais reais, mesmo temporariamente.
- Rode `git status` antes de `git add`; prefira `git add <arquivos>` a
  `git add .`.
- Se um segredo for commitado por engano, **rotacione-o imediatamente** —
  remover o arquivo em um commit posterior não o apaga do histórico.

## Nota histórica (transparência)

Um arquivo `backend/.env` de uma **arquitetura anterior** (backend em
Node.js, posteriormente substituída pela aplicação PHP atual) foi
versionado no commit inicial e removido em seguida. Ele continha
credenciais de exemplo/desenvolvimento **daquele sistema descontinuado**
(banco `portal_receitas`/`app_portal` e um `JWT_SECRET`), que **não são
usadas em nenhum ponto do projeto atual** (o banco vigente é
`tcc_receitas` e não há JWT no código). Como medida de higiene, qualquer
credencial que tenha correspondido a um recurso real deve ser
**rotacionada**. O histórico foi mantido intacto por decisão do
mantenedor (evitar reescrita/force-push em um repositório compartilhado);
a exposição residual é inerte por se tratar de sistema removido.

## Como relatar uma vulnerabilidade

Abra uma _issue_ com o rótulo `security` **sem incluir detalhes que
facilitem a exploração** e sinalize que deseja contato privado, ou
contate diretamente o mantenedor do repositório. Descreva o impacto e os
passos de reprodução assim que houver um canal seguro.
