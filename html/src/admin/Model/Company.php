<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Entity model for Company Profile.
 */
class Company
{
    private ?int $id;
    private string $companyName;
    private ?string $registrationNo;
    private ?string $gstNo;
    private ?string $address;
    private ?string $phone;
    private ?string $fax;
    private ?string $email;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $companyName,
        ?string $registrationNo = null,
        ?string $gstNo = null,
        ?string $address = null,
        ?string $phone = null,
        ?string $fax = null,
        ?string $email = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->companyName = $companyName;
        $this->registrationNo = $registrationNo;
        $this->gstNo = $gstNo;
        $this->address = $address;
        $this->phone = $phone;
        $this->fax = $fax;
        $this->email = $email;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getRegistrationNo(): ?string
    {
        return $this->registrationNo;
    }

    public function getGstNo(): ?string
    {
        return $this->gstNo;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
