<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Falha de validação de dados de entrada (formato, obrigatoriedade ou
 * unicidade violados).
 *
 * A mensagem é segura para exibição direta ao usuário final; os controllers
 * a convertem em resposta HTTP 400.
 */
class ValidationException extends DomainException
{
}
