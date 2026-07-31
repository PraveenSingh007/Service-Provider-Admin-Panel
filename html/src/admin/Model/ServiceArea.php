<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * ServiceArea Entity Model
 * Represents a record from the service_areas table.
 */
final class ServiceArea
{
    private ?int $id;
    private string $areaName;
    private string $pincode;
    private string $city;
    private string $state;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $areaName,
        string $pincode,
        string $city,
        string $state,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->areaName = $areaName;
        $this->pincode = $pincode;
        $this->city = $city;
        $this->state = $state;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAreaName(): string
    {
        return $this->areaName;
    }

    public function getPincode(): string
    {
        return $this->pincode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
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
            'area_name' => $this->areaName,
            'pincode' => $this->pincode,
            'city' => $this->city,
            'state' => $this->state,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
