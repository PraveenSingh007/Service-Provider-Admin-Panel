<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Salary Entity Model
 * Represents a record from employee_salaries table.
 */
final class Salary
{
    private ?int $id;
    private int $employeeId;
    private string $salaryMonth;
    private float $baseSalary;
    private int $totalDays;
    private int $presentDays;
    private int $absentDays;
    private int $halfDays;
    private int $leaveDays;
    private float $calculatedSalary;
    private float $bonus;
    private float $deductions;
    private float $netSalary;
    private string $paymentStatus;
    private ?string $paymentDate;
    private ?string $notes;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        int $employeeId,
        string $salaryMonth,
        float $baseSalary,
        int $totalDays,
        int $presentDays,
        int $absentDays,
        int $halfDays,
        int $leaveDays,
        float $calculatedSalary,
        float $bonus,
        float $deductions,
        float $netSalary,
        string $paymentStatus = 'pending',
        ?string $paymentDate = null,
        ?string $notes = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->employeeId = $employeeId;
        $this->salaryMonth = $salaryMonth;
        $this->baseSalary = $baseSalary;
        $this->totalDays = $totalDays;
        $this->presentDays = $presentDays;
        $this->absentDays = $absentDays;
        $this->halfDays = $halfDays;
        $this->leaveDays = $leaveDays;
        $this->calculatedSalary = $calculatedSalary;
        $this->bonus = $bonus;
        $this->deductions = $deductions;
        $this->netSalary = $netSalary;
        $this->paymentStatus = $paymentStatus;
        $this->paymentDate = $paymentDate;
        $this->notes = $notes;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getSalaryMonth(): string
    {
        return $this->salaryMonth;
    }

    public function getBaseSalary(): float
    {
        return $this->baseSalary;
    }

    public function getTotalDays(): int
    {
        return $this->totalDays;
    }

    public function getPresentDays(): int
    {
        return $this->presentDays;
    }

    public function getAbsentDays(): int
    {
        return $this->absentDays;
    }

    public function getHalfDays(): int
    {
        return $this->halfDays;
    }

    public function getLeaveDays(): int
    {
        return $this->leaveDays;
    }

    public function getCalculatedSalary(): float
    {
        return $this->calculatedSalary;
    }

    public function getBonus(): float
    {
        return $this->bonus;
    }

    public function getDeductions(): float
    {
        return $this->deductions;
    }

    public function getNetSalary(): float
    {
        return $this->netSalary;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getPaymentDate(): ?string
    {
        return $this->paymentDate;
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
     * Export object as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employeeId,
            'salary_month' => $this->salaryMonth,
            'base_salary' => $this->baseSalary,
            'total_days' => $this->totalDays,
            'present_days' => $this->presentDays,
            'absent_days' => $this->absentDays,
            'half_days' => $this->halfDays,
            'leave_days' => $this->leaveDays,
            'calculated_salary' => $this->calculatedSalary,
            'bonus' => $this->bonus,
            'deductions' => $this->deductions,
            'net_salary' => $this->netSalary,
            'payment_status' => $this->paymentStatus,
            'payment_date' => $this->paymentDate,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
