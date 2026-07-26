<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Service Entity Model
 * Represents a record from the services table.
 */
final class Service
{
    private ?int $id;
    private string $serviceName;
    private ?string $serviceImage;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $serviceName,
        ?string $serviceImage = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->serviceName = $serviceName;
        $this->serviceImage = $serviceImage;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getServiceImage(): ?string
    {
        return $this->serviceImage;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Export object as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service_name' => $this->serviceName,
            'service_image' => $this->serviceImage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
