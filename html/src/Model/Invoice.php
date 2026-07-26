<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Entity model for a Service Invoice.
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
    private float $subtotal;
    private float $discount;
    private float $tax;
    private float $totalAmount;
    private string $paymentStatus;
    private string $paymentMethod;
    private string $invoiceDate;
    private string $dueDate;
    private ?string $notes;
    private ?string $createdAt;
    private ?string $updatedAt;
    /** @var InvoiceItem[] */
    private array $items;

    public function __construct(
        ?int $id,
        string $invoiceNumber,
        ?int $quotationId,
        string $serviceRequestId,
        string $customerName,
        string $customerMobile,
        ?string $customerEmail,
        string $serviceName,
        float $subtotal,
        float $discount,
        float $tax,
        float $totalAmount,
        string $paymentStatus = 'unpaid',
        string $paymentMethod = 'Cash',
        string $invoiceDate = '',
        string $dueDate = '',
        ?string $notes = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        array $items = []
    ) {
        $this->id = $id;
        $this->invoiceNumber = $invoiceNumber;
        $this->quotationId = $quotationId;
        $this->serviceRequestId = $serviceRequestId;
        $this->customerName = $customerName;
        $this->customerMobile = $customerMobile;
        $this->customerEmail = $customerEmail;
        $this->serviceName = $serviceName;
        $this->subtotal = $subtotal;
        $this->discount = $discount;
        $this->tax = $tax;
        $this->totalAmount = $totalAmount;
        $this->paymentStatus = $paymentStatus;
        $this->paymentMethod = $paymentMethod;
        $this->invoiceDate = !empty($invoiceDate) ? $invoiceDate : date('Y-m-d');
        $this->dueDate = !empty($dueDate) ? $dueDate : date('Y-m-d', strtotime('+7 days'));
        $this->notes = $notes;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->items = $items;
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

    public function getNotes(): ?string
    {
        return $this->notes;
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
     * @return InvoiceItem[]
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
