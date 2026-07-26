<?php

declare(strict_types=1);

namespace App\Model;

/**
 * User Entity Model
 * Represents admin_login user record data.
 */
final class User
{
    private int $id;
    private string $username;
    private string $passwordHash;
    private string $role;

    public function __construct(
        int $id,
        string $username,
        string $passwordHash,
        string $role = 'admin'
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Export object state as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role' => $this->role,
        ];
    }
}
