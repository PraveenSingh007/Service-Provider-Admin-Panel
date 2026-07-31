<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Attendance Entity Model
 * Represents a record from employee_attendance table.
 */
final class Attendance
{
    private ?int $id;
    private int $employeeId;
    private string $attendanceDate;
    private string $status;
    private ?string $checkInTime;
    private ?string $checkOutTime;
    private ?string $notes;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        int $employeeId,
        string $attendanceDate,
        string $status = 'present',
        ?string $checkInTime = null,
        ?string $checkOutTime = null,
        ?string $notes = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->employeeId = $employeeId;
        $this->attendanceDate = $attendanceDate;
        $this->status = $status;
        $this->checkInTime = $checkInTime;
        $this->checkOutTime = $checkOutTime;
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

    public function getAttendanceDate(): string
    {
        return $this->attendanceDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCheckInTime(): ?string
    {
        return $this->checkInTime;
    }

    public function getCheckOutTime(): ?string
    {
        return $this->checkOutTime;
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
            'attendance_date' => $this->attendanceDate,
            'status' => $this->status,
            'check_in_time' => $this->checkInTime,
            'check_out_time' => $this->checkOutTime,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
