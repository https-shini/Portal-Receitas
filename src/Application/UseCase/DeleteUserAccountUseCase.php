<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

/**
 * Caso de uso: eliminar a conta do titular (LGPD art. 18, VI).
 *
 * Estratégia adotada: ANONIMIZAÇÃO irreversível em vez de DELETE físico —
 * o papel de banco da aplicação não possui DELETE (menor privilégio), e o
 * dado anonimizado deixa de ser dado pessoal (LGPD art. 12). O registro
 * perde nome, e-mail e credencial: torna-se inutilizável para login e
 * inassociável ao titular. A auditoria registra a operação via trigger.
 *
 * Pré-condição: o chamador DEVE reautenticar o titular (senha atual) antes
 * de executar — este caso de uso não repete essa verificação.
 */
class DeleteUserAccountUseCase
{
    /** Domínio reservado para valores anonimizados (nunca roteável — RFC 2606). */
    private const ANON_EMAIL_DOMAIN = 'anonimizado.invalid';

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    /**
     * @throws ValidationException Identificador de usuário inválido.
     */
    public function execute(int $userId): bool
    {
        if ($userId <= 0) {
            throw new ValidationException('Usuário inválido.');
        }

        // Credencial substituída por hash de segredo aleatório descartado:
        // nenhuma senha volta a autenticar esta conta.
        $unusablePassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        return $this->userRepository->updateProfile(
            $userId,
            'Usuário removido',
            sprintf('anonimo-%d@%s', $userId, self::ANON_EMAIL_DOMAIN),
            $unusablePassword
        );
    }
}
