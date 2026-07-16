<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Raiz da hierarquia de exceções do domínio.
 *
 * Toda falha de regra de negócio herda desta classe, permitindo que a camada
 * de apresentação capture erros do domínio de forma genérica, sem depender de
 * exceções de infraestrutura (ex.: PDOException).
 */
class DomainException extends RuntimeException
{
}
