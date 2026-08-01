<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Entity model for a specific Revision Version of an Invoice.
 * Each version contains its own financials, payment info, dates, and line items.
 */
class InvoiceVersion
{
    private ?int $id;
    private int $invoiceId;
    private int $versionNumber;
    private ?int $quotationVersion;
    private float $subtotal;
    private float $discount;
    private float $tax;
    private float $totalAmount;
    private string $paymentStatus;
    private string $paymentMethod;
    private string $invoiceDate;
    private string $dueDate;
    private ?string $revisionNotes;
    private ?string $createdBy;
    private ?string $createdAt;
    private ?string $updatedAt;
    /** @var InvoiceItem[] */
    private array $items;

    public function __construct(
        ?int $id,
        int $invoiceId,
        int $versionNumber,
        float $subtotal,
        float $discount,
        float $tax,
        float $totalAmount,
        string $paymentStatus = 'unpaid',
        string $paymentMethod = 'Cash',
        string $invoiceDate = '',
        string $dueDate = '',
        ?string $revisionNotes = null,
        ?string $createdBy = 'Admin',
        ?string $createdAt = null,
        ?string $updatedAt = null,
        array $items = [],
        ?int $quotationVersion = null
    ) {
        $this->id = $id;
        $this->invoiceId = $invoiceId;
        $this->versionNumber = $versionNumber;
        $this->quotationVersion = $quotationVersion;
        $this->subtotal = $subtotal;
        $this->discount = $discount;
        $this->tax = $tax;
        $this->totalAmount = $totalAmount;
        $this->paymentStatus = $paymentStatus;
        $this->paymentMethod = $paymentMethod;
        $this->invoiceDate = !empty($invoiceDate) ? $invoiceDate : date('Y-m-d');
        $this->dueDate = !empty($dueDate) ? $dueDate : date('Y-m-d', strtotime('+7 days'));
        $this->revisionNotes = $revisionNotes;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->items = $items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceId(): int
    {
        return $this->invoiceId;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function getQuotationVersion(): ?int
    {
        return $this->quotationVersion;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getTax(): float
    {
        return $this->tax;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getInvoiceDate(): string
    {
        return $this->invoiceDate;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function getRevisionNotes(): ?string
    {
        return $this->revisionNotes;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
