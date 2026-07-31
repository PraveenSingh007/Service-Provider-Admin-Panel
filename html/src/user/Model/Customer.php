<?php

declare(strict_types=1);

namespace App\User\Model;

/**
 * Customer Entity Model
 * Represents a record from the customer table.
 */
final class Customer
{
    private ?int $id;
    private string $email;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $mobileNo;
    private ?string $address;
    private int $isProfileCompleted;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $mobileNo = null,
        ?string $address = null,
        int $isProfileCompleted = 0,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->mobileNo = $mobileNo;
        $this->address = $address;
        $this->isProfileCompleted = $isProfileCompleted;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getFullName(): string {
        $name = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        return $name !== '' ? $name : $this->email;
    }
    public function getMobileNo(): ?string { return $this->mobileNo; }
    public function getAddress(): ?string { return $this->address; }
    public function isProfileCompleted(): bool { return $this->isProfileCompleted === 1; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    /**
     * Export object as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'mobile_no' => $this->mobileNo,
            'address' => $this->address,
            'is_profile_completed' => $this->isProfileCompleted,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
