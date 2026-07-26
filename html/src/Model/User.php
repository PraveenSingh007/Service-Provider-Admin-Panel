<?php

declare(strict_types=1);

namespace App\Model;

/**
 * User Entity Model representing an Admin User.
 */
class User
{
    private ?int $id;
    private ?string $firstName;
    private ?string $lastName;
    private string $username;
    private string $passwordHash;
    private string $role;

    public function __construct(
        ?int $id,
        ?string $firstName,
        ?string $lastName,
        string $username,
        string $passwordHash,
        string $role = 'admin'
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        $fullName = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        return $fullName !== '' ? $fullName : $this->username;
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
     * Export entity as associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'username' => $this->username,
            'role' => $this->role,
        ];
    }
}
