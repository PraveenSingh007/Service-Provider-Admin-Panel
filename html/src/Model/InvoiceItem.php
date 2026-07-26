<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Entity model for an Invoice Line Item.
 */
class InvoiceItem
{
    private ?int $id;
    private int $invoiceId;
    private string $itemDescription;
    private int $quantity;
    private float $unitPrice;
    private float $totalPrice;

    public function __construct(
        ?int $id,
        int $invoiceId,
        string $itemDescription,
        int $quantity,
        float $unitPrice,
        float $totalPrice
    ) {
        $this->id = $id;
        $this->invoiceId = $invoiceId;
        $this->itemDescription = $itemDescription;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceId(): int
    {
        return $this->invoiceId;
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

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }
}
