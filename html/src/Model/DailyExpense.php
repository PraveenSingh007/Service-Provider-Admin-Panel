<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Entity model for a Daily Expense record.
 */
class DailyExpense
{
    private ?int $id;
    private string $expenseType;
    private float $amount;
    private string $expenseDate;
    private ?string $notes;
    private ?string $createdBy;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $expenseType,
        float $amount,
        string $expenseDate,
        ?string $notes = null,
        ?string $createdBy = 'Admin',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->expenseType = $expenseType;
        $this->amount = $amount;
        $this->expenseDate = !empty($expenseDate) ? $expenseDate : date('Y-m-d');
        $this->notes = $notes;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpenseType(): string
    {
        return $this->expenseType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getExpenseDate(): string
    {
        return $this->expenseDate;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
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
}
