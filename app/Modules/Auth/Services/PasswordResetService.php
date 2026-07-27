<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Validator;
use App\Shared\Interfaces\UserRepositoryInterface;

final class PasswordResetService
{
    /** @var callable(string,string,string,string):bool */
    private $mailer;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Validator $validator,
        ?callable $mailer = null
    ) {
        $this->mailer = $mailer ?? static fn (string $to, string $subject, string $message, string $headers): bool =>
            @mail($to, $subject, $message, $headers);
    }

    /** @param array<string, mixed> $input */
    public function request(array $input): void
    {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $this->validator->validate(['email' => $email], ['email' => 'required|email|max_length:100']);
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return;
        }

        $this->users->createPasswordResetNotification($user->name, $user->email);
        $administratorEmail = $this->users->findAdministratorEmail();
        if ($administratorEmail === null) {
            return;
        }

        ($this->mailer)(
            $administratorEmail,
            'Solicitud de restablecimiento de contraseña',
            "El usuario {$user->name} ({$user->email}) ha solicitado restablecer su contraseña.",
            'From: noreply@sembriexport.com'
        );
    }
}
