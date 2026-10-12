<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Recurso referenciado pela entrada não existe (ex.: escrever em uma receita
 * que não está no catálogo).
 *
 * Distinta de ValidationException: o dado é bem formado, mas aponta para algo
 * inexistente. A mensagem é segura para exibição direta ao usuário final; os
 * controllers a convertem em resposta HTTP 404.
 */
class NotFoundException extends DomainException
{
}
