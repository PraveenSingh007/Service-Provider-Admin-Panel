<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Salary;
use mysqli;
use Throwable;

/**
 * Salary Repository
 * Manages database operations for employee_salaries table using prepared statements.
 */
class SalaryRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Save or update salary record.
     */
    public function save(
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
        ?string $notes = null
    ): bool {
        try {
            $sql = 'INSERT INTO employee_salaries (employee_id, salary_month, base_salary, total_days, present_days, absent_days, half_days, leave_days, calculated_salary, bonus, deductions, net_salary, payment_status, payment_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE base_salary = VALUES(base_salary), total_days = VALUES(total_days), present_days = VALUES(present_days), absent_days = VALUES(absent_days), half_days = VALUES(half_days), leave_days = VALUES(leave_days), calculated_salary = VALUES(calculated_salary), bonus = VALUES(bonus), deductions = VALUES(deductions), net_salary = VALUES(net_salary), notes = VALUES(notes)';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('isdiiiiiddddsss', $employeeId, $salaryMonth, $baseSalary, $totalDays, $presentDays, $absentDays, $halfDays, $leaveDays, $calculatedSalary, $bonus, $deductions, $netSalary, $paymentStatus, $paymentDate, $notes);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('SalaryRepository save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get salary records for a month.
     *
     * @return Salary[]
     */
    public function findByMonth(string $salaryMonth): array
    {
        $salaries = [];
        try {
            $sql = 'SELECT id, employee_id, salary_month, base_salary, total_days, present_days, absent_days, half_days, leave_days, calculated_salary, bonus, deductions, net_salary, payment_status, payment_date, notes, created_at, updated_at FROM employee_salaries WHERE salary_month = ? ORDER BY id ASC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('s', $salaryMonth);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $salaries[] = new Salary(
                        (int) $row['id'],
                        (int) $row['employee_id'],
                        (string) $row['salary_month'],
                        (float) $row['base_salary'],
                        (int) $row['total_days'],
                        (int) $row['present_days'],
                        (int) $row['absent_days'],
                        (int) $row['half_days'],
                        (int) $row['leave_days'],
                        (float) $row['calculated_salary'],
                        (float) $row['bonus'],
                        (float) $row['deductions'],
                        (float) $row['net_salary'],
                        (string) ($row['payment_status'] ?? 'pending'),
                        isset($row['payment_date']) ? (string) $row['payment_date'] : null,
                        isset($row['notes']) ? (string) $row['notes'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('SalaryRepository findByMonth error: ' . $e->getMessage());
        }

        return $salaries;
    }

    /**
     * Update payment status.
     */
    public function markAsPaid(int $salaryId, string $paymentDate): bool
    {
        try {
            $sql = 'UPDATE employee_salaries SET payment_status = "paid", payment_date = ? WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('si', $paymentDate, $salaryId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('SalaryRepository markAsPaid error: ' . $e->getMessage());
            return false;
        }
    }
}
