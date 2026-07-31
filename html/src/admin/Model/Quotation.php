<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Entity model for a Service Request Quotation.
 */
class Quotation
{
    private ?int $id;
    private string $quotationNumber;
    private string $serviceRequestId;
    private string $customerName;
    private string $customerMobile;
    private ?string $customerEmail;
    private string $serviceName;
    private int $currentVersion;
    private float $totalAmount;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $quotationNumber,
        string $serviceRequestId,
        string $customerName,
        string $customerMobile,
        ?string $customerEmail,
        string $serviceName,
        int $currentVersion = 1,
        float $totalAmount = 0.0,
        string $status = 'sent',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->quotationNumber = $quotationNumber;
        $this->serviceRequestId = $serviceRequestId;
        $this->customerName = $customerName;
        $this->customerMobile = $customerMobile;
        $this->customerEmail = $customerEmail;
        $this->serviceName = $serviceName;
        $this->currentVersion = $currentVersion;
        $this->totalAmount = $totalAmount;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuotationNumber(): string
    {
        return $this->quotationNumber;
    }

    public function getServiceRequestId(): string
    {
        return $this->serviceRequestId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerMobile(): string
    {
        return $this->customerMobile;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
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
