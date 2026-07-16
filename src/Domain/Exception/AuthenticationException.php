<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Falha de autenticação (credenciais não conferem).
 *
 * Regra de negócio: a mensagem nunca revela se o erro foi no e-mail ou na
 * senha, evitando enumeração de contas; os controllers a convertem em
 * resposta HTTP 401.
 */
class AuthenticationException extends DomainException
{
}
