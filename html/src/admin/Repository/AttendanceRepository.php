<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Attendance;
use mysqli;
use Throwable;

/**
 * Attendance Repository
 * Manages database CRUD for employee_attendance table using prepared statements.
 */
class AttendanceRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Save or update daily attendance for an employee.
     */
    public function save(
        int $employeeId,
        string $date,
        string $status,
        ?string $checkInTime = null,
        ?string $checkOutTime = null,
        ?string $notes = null
    ): bool {
        try {
            $sql = 'INSERT INTO employee_attendance (employee_id, attendance_date, status, check_in_time, check_out_time, notes) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), check_in_time = VALUES(check_in_time), check_out_time = VALUES(check_out_time), notes = VALUES(notes)';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('isssss', $employeeId, $date, $status, $checkInTime, $checkOutTime, $notes);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('AttendanceRepository save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get attendance records for a specific date.
     *
     * @return array<int, Attendance> Keyed by employee_id
     */
    public function findByDate(string $date): array
    {
        $records = [];
        try {
            $sql = 'SELECT id, employee_id, attendance_date, status, check_in_time, check_out_time, notes, created_at, updated_at FROM employee_attendance WHERE attendance_date = ?';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('s', $date);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $empId = (int) $row['employee_id'];
                    $records[$empId] = new Attendance(
                        (int) $row['id'],
                        $empId,
                        (string) $row['attendance_date'],
                        (string) $row['status'],
                        isset($row['check_in_time']) ? (string) $row['check_in_time'] : null,
                        isset($row['check_out_time']) ? (string) $row['check_out_time'] : null,
                        isset($row['notes']) ? (string) $row['notes'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('AttendanceRepository findByDate error: ' . $e->getMessage());
        }

        return $records;
    }

    /**
     * Get monthly attendance summary for an employee.
     *
     * @return array{present: int, absent: int, half_day: int, leave: int}
     */
    public function getMonthlySummary(int $employeeId, string $yearMonth): array
    {
        $summary = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0];
        try {
            $startDate = $yearMonth . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $sql = 'SELECT status, COUNT(*) as count FROM employee_attendance WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? GROUP BY status';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('iss', $employeeId, $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $st = (string) $row['status'];
                    $cnt = (int) $row['count'];
                    if (isset($summary[$st])) {
                        $summary[$st] = $cnt;
                    }
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('AttendanceRepository getMonthlySummary error: ' . $e->getMessage());
        }

        return $summary;
    }
}
