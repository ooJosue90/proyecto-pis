<?php

declare(strict_types=1);

namespace Tests\Modules\Auth;

use App\Core\Validator;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Services\AuthService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Interfaces\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testCorrectLoginReturnsTheUser(): void
    {
        $repository = new FakeUserRepository(new User(
            'AGR001',
            'Ana',
            'ana@example.com',
            password_hash('secreto123', PASSWORD_DEFAULT),
            'Agricultor'
        ));
        $service = new AuthService($repository, new Validator());

        $user = $service->authenticate(['email' => ' ANA@EXAMPLE.COM ', 'password' => 'secreto123']);

        self::assertSame('AGR001', $user->id);
        self::assertSame('ana@example.com', $repository->requestedEmail);
    }

    public function testIncorrectLoginUsesAGenericMessage(): void
    {
        $repository = new FakeUserRepository(null);
        $service = new AuthService($repository, new Validator());

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('El correo o la contraseña son incorrectos.');
        $service->authenticate(['email' => 'nadie@example.com', 'password' => 'incorrecta']);
    }

    public function testLegacyPlainTextPasswordIsRehashed(): void
    {
        $repository = new FakeUserRepository(new User(
            'BOD001',
            'Bruno',
            'bruno@example.com',
            'bodega123',
            'Bodeguero'
        ));
        $service = new AuthService($repository, new Validator());

        $service->authenticate(['email' => 'bruno@example.com', 'password' => 'bodega123']);

        self::assertNotNull($repository->updatedHash);
        self::assertTrue(password_verify('bodega123', (string) $repository->updatedHash));
    }
}

final class FakeUserRepository implements UserRepositoryInterface
{
    public ?string $requestedEmail = null;
    public ?string $updatedHash = null;

    public function __construct(private readonly ?User $user)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $this->requestedEmail = $email;
        return $this->user;
    }

    public function updatePasswordHash(string $userId, string $passwordHash): void
    {
        $this->updatedHash = $passwordHash;
    }

    public function createPasswordResetNotification(string $name, string $email): void
    {
    }

    public function findAdministratorEmail(): ?string
    {
        return null;
    }
}
