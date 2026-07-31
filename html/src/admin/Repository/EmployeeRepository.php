<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Employee;
use mysqli;
use Throwable;

/**
 * Employee Repository
 * Handles database operations for employees table.
 */
class EmployeeRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get all employees.
     *
     * @return Employee[]
     */
    public function findAll(): array
    {
        $employees = [];
        try {
            $sql = 'SELECT id, emp_code, emp_name, emp_email, emp_mobile, emp_address, emp_role, emp_salary, emp_photo, emp_aadhar, emp_pan, joining_date, status, status_change_date, created_at, updated_at FROM employees ORDER BY id ASC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $employees[] = new Employee(
                        (int) $row['id'],
                        (string) $row['emp_code'],
                        (string) $row['emp_name'],
                        (string) $row['emp_email'],
                        (string) $row['emp_mobile'],
                        (string) $row['emp_address'],
                        (string) $row['emp_role'],
                        (float) $row['emp_salary'],
                        isset($row['emp_photo']) ? (string) $row['emp_photo'] : null,
                        isset($row['emp_aadhar']) ? (string) $row['emp_aadhar'] : null,
                        isset($row['emp_pan']) ? (string) $row['emp_pan'] : null,
                        (string) $row['joining_date'],
                        (string) ($row['status'] ?? 'active'),
                        isset($row['status_change_date']) ? (string) $row['status_change_date'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('EmployeeRepository findAll error: ' . $e->getMessage());
        }

        return $employees;
    }

    /**
     * Get active employees only.
     *
     * @return Employee[]
     */
    public function findActive(): array
    {
        $employees = [];
        try {
            $sql = "SELECT id, emp_code, emp_name, emp_email, emp_mobile, emp_address, emp_role, emp_salary, emp_photo, emp_aadhar, emp_pan, joining_date, status, status_change_date, created_at, updated_at FROM employees WHERE status = 'active' ORDER BY emp_name ASC";
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $employees[] = new Employee(
                        (int) $row['id'],
                        (string) $row['emp_code'],
                        (string) $row['emp_name'],
                        (string) $row['emp_email'],
                        (string) $row['emp_mobile'],
                        (string) $row['emp_address'],
                        (string) $row['emp_role'],
                        (float) $row['emp_salary'],
                        isset($row['emp_photo']) ? (string) $row['emp_photo'] : null,
                        isset($row['emp_aadhar']) ? (string) $row['emp_aadhar'] : null,
                        isset($row['emp_pan']) ? (string) $row['emp_pan'] : null,
                        (string) $row['joining_date'],
                        (string) ($row['status'] ?? 'active'),
                        isset($row['status_change_date']) ? (string) $row['status_change_date'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('EmployeeRepository findActive error: ' . $e->getMessage());
        }

        return $employees;
    }

    /**
     * Find employee by ID.
     */
    public function findById(int $id): ?Employee
    {
        try {
            $sql = 'SELECT id, emp_code, emp_name, emp_email, emp_mobile, emp_address, emp_role, emp_salary, emp_photo, emp_aadhar, emp_pan, joining_date, status, status_change_date, created_at, updated_at FROM employees WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return new Employee(
                        (int) $row['id'],
                        (string) $row['emp_code'],
                        (string) $row['emp_name'],
                        (string) $row['emp_email'],
                        (string) $row['emp_mobile'],
                        (string) $row['emp_address'],
                        (string) $row['emp_role'],
                        (float) $row['emp_salary'],
                        isset($row['emp_photo']) ? (string) $row['emp_photo'] : null,
                        isset($row['emp_aadhar']) ? (string) $row['emp_aadhar'] : null,
                        isset($row['emp_pan']) ? (string) $row['emp_pan'] : null,
                        (string) $row['joining_date'],
                        (string) ($row['status'] ?? 'active'),
                        isset($row['status_change_date']) ? (string) $row['status_change_date'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('EmployeeRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find employee by code or email.
     */
    public function findByCodeOrEmail(string $empCode, string $empEmail): ?Employee
    {
        try {
            $sql = 'SELECT id, emp_code, emp_name, emp_email, emp_mobile, emp_address, emp_role, emp_salary, emp_photo, emp_aadhar, emp_pan, joining_date, status, status_change_date, created_at, updated_at FROM employees WHERE emp_code = ? OR emp_email = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('ss', $empCode, $empEmail);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return new Employee(
                        (int) $row['id'],
                        (string) $row['emp_code'],
                        (string) $row['emp_name'],
                        (string) $row['emp_email'],
                        (string) $row['emp_mobile'],
                        (string) $row['emp_address'],
                        (string) $row['emp_role'],
                        (float) $row['emp_salary'],
                        isset($row['emp_photo']) ? (string) $row['emp_photo'] : null,
                        isset($row['emp_aadhar']) ? (string) $row['emp_aadhar'] : null,
                        isset($row['emp_pan']) ? (string) $row['emp_pan'] : null,
                        (string) $row['joining_date'],
                        (string) ($row['status'] ?? 'active'),
                        isset($row['status_change_date']) ? (string) $row['status_change_date'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('EmployeeRepository findByCodeOrEmail error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Create a new employee.
     */
    public function create(
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
        string $status,
        ?string $statusChangeDate = null
    ): bool {
        try {
            $sql = 'INSERT INTO employees (emp_code, emp_name, emp_email, emp_mobile, emp_address, emp_role, emp_salary, emp_photo, emp_aadhar, emp_pan, joining_date, status, status_change_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('ssssssdssssss', $empCode, $empName, $empEmail, $empMobile, $empAddress, $empRole, $empSalary, $empPhoto, $empAadhar, $empPan, $joiningDate, $status, $statusChangeDate);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('EmployeeRepository create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an employee.
     */
    public function update(
        int $id,
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
        string $status,
        ?string $statusChangeDate = null
    ): bool {
        try {
            $sql = 'UPDATE employees SET emp_code = ?, emp_name = ?, emp_email = ?, emp_mobile = ?, emp_address = ?, emp_role = ?, emp_salary = ?, emp_photo = ?, emp_aadhar = ?, emp_pan = ?, joining_date = ?, status = ?, status_change_date = ? WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('ssssssdssssssi', $empCode, $empName, $empEmail, $empMobile, $empAddress, $empRole, $empSalary, $empPhoto, $empAadhar, $empPan, $joiningDate, $status, $statusChangeDate, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('EmployeeRepository update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an employee.
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM employees WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('EmployeeRepository delete error: ' . $e->getMessage());
            return false;
        }
    }
}
