<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Entity model for a Service Invoice (Header).
 * Financial details are now stored in InvoiceVersion records.
 */
class Invoice
{
    private ?int $id;
    private string $invoiceNumber;
    private ?int $quotationId;
    private string $serviceRequestId;
    private string $customerName;
    private string $customerMobile;
    private ?string $customerEmail;
    private string $serviceName;
    private int $currentVersion;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;
    /** @var InvoiceVersion[] */
    private array $versions;

    public function __construct(
        ?int $id,
        string $invoiceNumber,
        ?int $quotationId,
        string $serviceRequestId,
        string $customerName,
        string $customerMobile,
        ?string $customerEmail,
        string $serviceName,
        int $currentVersion = 1,
        string $status = 'active',
        ?string $createdAt = null,
        ?string $updatedAt = null,
        array $versions = []
    ) {
        $this->id = $id;
        $this->invoiceNumber = $invoiceNumber;
        $this->quotationId = $quotationId;
        $this->serviceRequestId = $serviceRequestId;
        $this->customerName = $customerName;
        $this->customerMobile = $customerMobile;
        $this->customerEmail = $customerEmail;
        $this->serviceName = $serviceName;
        $this->currentVersion = $currentVersion;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->versions = $versions;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function getQuotationId(): ?int
    {
        return $this->quotationId;
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

    /**
     * @return InvoiceVersion[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    public function setVersions(array $versions): void
    {
        $this->versions = $versions;
    }

    /**
     * Get the latest (current) version object.
     */
    public function getLatestVersion(): ?InvoiceVersion
    {
        if (empty($this->versions)) {
            return null;
        }
        return end($this->versions);
    }

    /**
     * Get a specific version by version number.
     */
    public function getVersion(int $versionNumber): ?InvoiceVersion
    {
        foreach ($this->versions as $v) {
            if ($v->getVersionNumber() === $versionNumber) {
                return $v;
            }
        }
        return null;
    }

    // Convenience accessors that delegate to the latest version for backward compatibility
    public function getTotalAmount(): float
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getTotalAmount() : 0.0;
    }

    public function getSubtotal(): float
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getSubtotal() : 0.0;
    }

    public function getDiscount(): float
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getDiscount() : 0.0;
    }

    public function getTax(): float
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getTax() : 0.0;
    }

    public function getPaymentStatus(): string
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getPaymentStatus() : 'unpaid';
    }

    public function getPaymentMethod(): string
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getPaymentMethod() : 'Cash';
    }

    public function getInvoiceDate(): string
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getInvoiceDate() : date('Y-m-d');
    }

    public function getDueDate(): string
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getDueDate() : date('Y-m-d', strtotime('+7 days'));
    }

    public function getNotes(): ?string
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getRevisionNotes() : null;
    }

    /**
     * @return InvoiceItem[]
     */
    public function getItems(): array
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getItems() : [];
    }

    public function getQuotationVersion(): ?int
    {
        $latest = $this->getLatestVersion();
        return $latest !== null ? $latest->getQuotationVersion() : null;
    }
}
