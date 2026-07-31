<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Entity model for a Quotation Revision Version.
 */
class QuotationVersion
{
    private ?int $id;
    private int $quotationId;
    private int $versionNumber;
    private float $subtotal;
    private float $discount;
    private float $tax;
    private float $totalAmount;
    private ?string $revisionNotes;
    private ?string $createdBy;
    private ?string $createdAt;
    /** @var QuotationItem[] */
    private array $items;

    public function __construct(
        ?int $id,
        int $quotationId,
        int $versionNumber,
        float $subtotal,
        float $discount,
        float $tax,
        float $totalAmount,
        ?string $revisionNotes = null,
        ?string $createdBy = null,
        ?string $createdAt = null,
        array $items = []
    ) {
        $this->id = $id;
        $this->quotationId = $quotationId;
        $this->versionNumber = $versionNumber;
        $this->subtotal = $subtotal;
        $this->discount = $discount;
        $this->tax = $tax;
        $this->totalAmount = $totalAmount;
        $this->revisionNotes = $revisionNotes;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->items = $items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuotationId(): int
    {
        return $this->quotationId;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
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

    /**
     * @return QuotationItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
