<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;
use mysqli;
use Throwable;

/**
 * User Repository
 * Executes database operations on admin_login table using prepared statements.
 */
class UserRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Retrieve all admin users.
     *
     * @return User[]
     */
    public function findAll(): array
    {
        $users = [];
        try {
            $sql = 'SELECT id, admin_username, admin_password, admin_role FROM admin_login ORDER BY id ASC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $users[] = new User(
                        (int) $row['id'],
                        (string) $row['admin_username'],
                        (string) $row['admin_password'],
                        (string) ($row['admin_role'] ?? 'admin')
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
     * Find an admin user by ID.
     */
    public function findById(int $id): ?User
    {
        try {
            $sql = 'SELECT id, admin_username, admin_password, admin_role FROM admin_login WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return new User(
                        (int) $row['id'],
                        (string) $row['admin_username'],
                        (string) $row['admin_password'],
                        (string) ($row['admin_role'] ?? 'admin')
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('UserRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find an admin user by username / email in admin_login table.
     */
    public function findByUsername(string $username): ?User
    {
        $username = trim($username);

        try {
            $sql = 'SELECT id, admin_username, admin_password, admin_role FROM admin_login WHERE admin_username = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return null;
            }

            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return new User(
                    (int) $row['id'],
                    (string) $row['admin_username'],
                    (string) $row['admin_password'],
                    (string) ($row['admin_role'] ?? 'admin')
                );
            }

            $stmt->close();
        } catch (Throwable $e) {
            error_log('UserRepository findByUsername error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Create a new admin user.
     */
    public function create(string $username, string $passwordHash, string $role): bool
    {
        try {
            $sql = 'INSERT INTO admin_login (admin_username, admin_password, admin_role) VALUES (?, ?, ?)';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('sss', $username, $passwordHash, $role);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('UserRepository create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an admin user.
     */
    public function update(int $id, string $username, ?string $passwordHash, string $role): bool
    {
        try {
            if ($passwordHash !== null && $passwordHash !== '') {
                $sql = 'UPDATE admin_login SET admin_username = ?, admin_password = ?, admin_role = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);

                if (!$stmt) {
                    return false;
                }

                $stmt->bind_param('sssi', $username, $passwordHash, $role, $id);
            } else {
                $sql = 'UPDATE admin_login SET admin_username = ?, admin_role = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);

                if (!$stmt) {
                    return false;
                }

                $stmt->bind_param('ssi', $username, $role, $id);
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
     * Delete an admin user by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM admin_login WHERE id = ?';
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
}
