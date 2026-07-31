<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\User;
use mysqli;
use Throwable;

/**
 * User Repository
 * Executes database operations on employees table for system users & authentication.
 */
class UserRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Helper to split emp_name into first_name and last_name.
     *
     * @return array{0: string, 1: string}
     */
    private function parseName(string $empName): array
    {
        $parts = explode(' ', trim($empName), 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * Retrieve all users/employees.
     *
     * @return User[]
     */
    public function findAll(): array
    {
        $users = [];
        try {
            $sql = 'SELECT id, emp_name, emp_email, password_hash, emp_role FROM employees ORDER BY id ASC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    [$firstName, $lastName] = $this->parseName((string) $row['emp_name']);
                    $users[] = new User(
                        (int) $row['id'],
                        $firstName !== '' ? $firstName : null,
                        $lastName !== '' ? $lastName : null,
                        (string) $row['emp_email'],
                        (string) ($row['password_hash'] ?? ''),
                        (string) ($row['emp_role'] ?? 'admin')
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('UserRepository findAll error: ' . $e->getMessage());
        }

        return $users;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?User
    {
        try {
            $sql = 'SELECT id, emp_name, emp_email, password_hash, emp_role FROM employees WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    [$firstName, $lastName] = $this->parseName((string) $row['emp_name']);
                    $user = new User(
                        (int) $row['id'],
                        $firstName !== '' ? $firstName : null,
                        $lastName !== '' ? $lastName : null,
                        (string) $row['emp_email'],
                        (string) ($row['password_hash'] ?? ''),
                        (string) ($row['emp_role'] ?? 'admin')
                    );
                    $stmt->close();
                    return $user;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('UserRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find a user by username / email in employees table.
     */
    public function findByUsername(string $username): ?User
    {
        $username = strtolower(trim($username));

        try {
            // 1. Exact email or emp_code match (case-insensitive)
            $sql = 'SELECT id, emp_name, emp_email, password_hash, emp_role FROM employees WHERE LOWER(emp_email) = ? OR LOWER(emp_code) = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('ss', $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    [$firstName, $lastName] = $this->parseName((string) $row['emp_name']);
                    $user = new User(
                        (int) $row['id'],
                        $firstName !== '' ? $firstName : null,
                        $lastName !== '' ? $lastName : null,
                        (string) $row['emp_email'],
                        (string) ($row['password_hash'] ?? ''),
                        (string) ($row['emp_role'] ?? 'admin')
                    );
                    $stmt->close();
                    return $user;
                }
                $stmt->close();
            }

            // 2. Fallback: Match prefix before @ or emp_role
            $sql = 'SELECT id, emp_name, emp_email, password_hash, emp_role FROM employees WHERE LOWER(SUBSTRING_INDEX(emp_email, "@", 1)) = ? OR LOWER(emp_role) = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('ss', $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    [$firstName, $lastName] = $this->parseName((string) $row['emp_name']);
                    $user = new User(
                        (int) $row['id'],
                        $firstName !== '' ? $firstName : null,
                        $lastName !== '' ? $lastName : null,
                        (string) $row['emp_email'],
                        (string) ($row['password_hash'] ?? ''),
                        (string) ($row['emp_role'] ?? 'admin')
                    );
                    $stmt->close();
                    return $user;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('UserRepository findByUsername error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Create a new user in employees table.
     */
    public function create(string $firstName, string $lastName, string $username, string $passwordHash, string $role): bool
    {
        try {
            $fullName = trim($firstName . ' ' . $lastName);
            $empCode = 'EMP-' . rand(1000, 9999);
            $sql = 'INSERT INTO employees (emp_code, emp_name, emp_email, password_hash, emp_mobile, emp_address, emp_role, emp_salary, joining_date, status) VALUES (?, ?, ?, ?, "0000000000", "N/A", ?, 0.00, CURDATE(), "active")';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('sssss', $empCode, $fullName, $username, $passwordHash, $role);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('UserRepository create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a user in employees table.
     */
    public function update(int $id, string $firstName, string $lastName, string $username, ?string $passwordHash, string $role): bool
    {
        try {
            $fullName = trim($firstName . ' ' . $lastName);
            if ($passwordHash !== null && $passwordHash !== '') {
                $sql = 'UPDATE employees SET emp_name = ?, emp_email = ?, password_hash = ?, emp_role = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);

                if (!$stmt) {
                    return false;
                }

                $stmt->bind_param('ssssi', $fullName, $username, $passwordHash, $role, $id);
            } else {
                $sql = 'UPDATE employees SET emp_name = ?, emp_email = ?, emp_role = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);

                if (!$stmt) {
                    return false;
                }

                $stmt->bind_param('sssi', $fullName, $username, $role, $id);
            }

            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('UserRepository update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user in employees table.
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
            error_log('UserRepository delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user password in employees table.
     */
    public function updatePassword(int $id, string $newPasswordHash): bool
    {
        try {
            $sql = 'UPDATE employees SET password_hash = ? WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('si', $newPasswordHash, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('UserRepository updatePassword error: ' . $e->getMessage());
            return false;
        }
    }
}
