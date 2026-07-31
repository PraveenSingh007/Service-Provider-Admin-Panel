<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Employee Entity Model
 * Represents an employee record in the employees table.
 */
final class Employee
{
    private ?int $id;
    private string $empCode;
    private string $empName;
    private string $empEmail;
    private string $empMobile;
    private string $empAddress;
    private string $empRole;
    private float $empSalary;
    private ?string $empPhoto;
    private ?string $empAadhar;
    private ?string $empPan;
    private string $joiningDate;
    private string $status;
    private ?string $statusChangeDate;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $empCode,
        string $empName,
        string $empEmail,
        string $empMobile,
        string $empAddress,
        string $empRole,
        float $empSalary,
        ?string $empPhoto,
        ?string $empAadhar,
        ?string $empPan,
        string $joiningDate,
        string $status = 'active',
        ?string $statusChangeDate = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->empCode = $empCode;
        $this->empName = $empName;
        $this->empEmail = $empEmail;
        $this->empMobile = $empMobile;
        $this->empAddress = $empAddress;
        $this->empRole = $empRole;
        $this->empSalary = $empSalary;
        $this->empPhoto = $empPhoto;
        $this->empAadhar = $empAadhar;
        $this->empPan = $empPan;
        $this->joiningDate = $joiningDate;
        $this->status = $status;
        $this->statusChangeDate = $statusChangeDate;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmpCode(): string
    {
        return $this->empCode;
    }

    public function getEmpName(): string
    {
        return $this->empName;
    }

    public function getEmpEmail(): string
    {
        return $this->empEmail;
    }

    public function getEmpMobile(): string
    {
        return $this->empMobile;
    }

    public function getEmpAddress(): string
    {
        return $this->empAddress;
    }

    public function getEmpRole(): string
    {
        return $this->empRole;
    }

    public function getEmpSalary(): float
    {
        return $this->empSalary;
    }

    public function getEmpPhoto(): ?string
    {
        return $this->empPhoto;
    }

    public function getEmpAadhar(): ?string
    {
        return $this->empAadhar;
    }

    public function getEmpPan(): ?string
    {
        return $this->empPan;
    }

    public function getJoiningDate(): string
    {
        return $this->joiningDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusChangeDate(): ?string
    {
        return $this->statusChangeDate;
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
            'emp_code' => $this->empCode,
            'emp_name' => $this->empName,
            'emp_email' => $this->empEmail,
            'emp_mobile' => $this->empMobile,
            'emp_address' => $this->empAddress,
            'emp_role' => $this->empRole,
            'emp_salary' => $this->empSalary,
            'emp_photo' => $this->empPhoto,
            'emp_aadhar' => $this->empAadhar,
            'emp_pan' => $this->empPan,
            'joining_date' => $this->joiningDate,
            'status' => $this->status,
            'status_change_date' => $this->statusChangeDate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
