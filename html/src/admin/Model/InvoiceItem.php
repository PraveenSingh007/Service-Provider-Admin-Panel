<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Entity model for an Invoice Line Item.
 * Items are linked to invoice_versions (via version_id), not directly to invoices.
 */
class InvoiceItem
{
    private ?int $id;
    private int $versionId;
    private string $itemDescription;
    private int $quantity;
    private float $unitPrice;
    private float $discountPercent;
    private float $gstPercent;
    private float $totalPrice;

    public function __construct(
        ?int $id,
        int $versionId,
        string $itemDescription,
        int $quantity,
        float $unitPrice,
        float $totalPrice,
        float $discountPercent = 0.0,
        float $gstPercent = 0.0
    ) {
        $this->id = $id;
        $this->versionId = $versionId;
        $this->itemDescription = $itemDescription;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
        $this->discountPercent = $discountPercent;
        $this->gstPercent = $gstPercent;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function getItemDescription(): string
    {
        return $this->itemDescription;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getDiscountPercent(): float
    {
        return $this->discountPercent;
    }

    public function getGstPercent(): float
    {
        return $this->gstPercent;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }
}
