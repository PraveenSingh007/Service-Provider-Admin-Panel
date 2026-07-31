<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Model\Customer;
use mysqli;
use Throwable;

/**
 * CustomerRepository
 * Database repository for customer authentication and profile management.
 */
class CustomerRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find customer by email, or create new record if not exists.
     */
    public function findOrCreateByEmail(string $email): ?Customer
    {
        $email = strtolower(trim($email));
        try {
            $sql = 'SELECT * FROM customer WHERE LOWER(email) = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return $this->mapRowToEntity($row);
                }
                $stmt->close();
            }

            // Create new customer record with empty profile
            $insertSql = 'INSERT INTO customer (email, is_profile_completed) VALUES (?, 0)';
            $insertStmt = $this->connection->prepare($insertSql);
            if ($insertStmt) {
                $insertStmt->bind_param('s', $email);
                $insertStmt->execute();
                $newId = (int) $insertStmt->insert_id;
                $insertStmt->close();

                return new Customer($newId, $email, null, null, null, null, 0);
            }
        } catch (Throwable $e) {
            error_log('CustomerRepository findOrCreateByEmail error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find customer by ID.
     */
    public function findById(int $id): ?Customer
    {
        try {
            $sql = 'SELECT * FROM customer WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return $this->mapRowToEntity($row);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('CustomerRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Store a generated OTP code for email verification.
     */
    public function createOtp(string $email, string $otpCode, string $expiresAt): bool
    {
        try {
            // Invalidate existing active OTPs for this email
            $updateSql = 'UPDATE customer_otp SET is_used = 1 WHERE email = ? AND is_used = 0';
            $upStmt = $this->connection->prepare($updateSql);
            if ($upStmt) {
                $upStmt->bind_param('s', $email);
                $upStmt->execute();
                $upStmt->close();
            }

            // Insert new OTP
            $sql = 'INSERT INTO customer_otp (email, otp_code, expires_at, is_used) VALUES (?, ?, ?, 0)';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('sss', $email, $otpCode, $expiresAt);
                $success = $stmt->execute();
                $stmt->close();
                return $success;
            }
        } catch (Throwable $e) {
            error_log('CustomerRepository createOtp error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Verify OTP code for given email.
     */
    public function verifyOtp(string $email, string $otpCode): bool
    {
        try {
            $email = strtolower(trim($email));
            $otpCode = trim($otpCode);
            $now = date('Y-m-d H:i:s');

            $sql = 'SELECT id FROM customer_otp WHERE LOWER(email) = ? AND otp_code = ? AND expires_at >= ? AND is_used = 0 ORDER BY id DESC LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('sss', $email, $otpCode, $now);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $otpId = (int) $row['id'];
                    $stmt->close();

                    // Mark OTP as used
                    $markSql = 'UPDATE customer_otp SET is_used = 1 WHERE id = ?';
                    $markStmt = $this->connection->prepare($markSql);
                    if ($markStmt) {
                        $markStmt->bind_param('i', $otpId);
                        $markStmt->execute();
                        $markStmt->close();
                    }

                    return true;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('CustomerRepository verifyOtp error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Update customer personal profile (first_name, last_name, mobile_no, address).
     *
     * @param int $id
     * @param array<string, mixed> $data
     */
    public function updateProfile(int $id, array $data): bool
    {
        try {
            $sql = 'UPDATE customer SET
                first_name = ?, last_name = ?, mobile_no = ?, address = ?,
                is_profile_completed = 1
                WHERE id = ?';

            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $firstName = (string) ($data['first_name'] ?? '');
            $lastName = (string) ($data['last_name'] ?? '');
            $mobile = (string) ($data['mobile_no'] ?? '');
            $address = (string) ($data['address'] ?? '');

            $stmt->bind_param('ssssi', $firstName, $lastName, $mobile, $address, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('CustomerRepository updateProfile error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Map database row to Customer entity.
     *
     * @param array<string, mixed> $row
     */
    private function mapRowToEntity(array $row): Customer
    {
        return new Customer(
            (int) $row['id'],
            (string) $row['email'],
            isset($row['first_name']) ? (string) $row['first_name'] : null,
            isset($row['last_name']) ? (string) $row['last_name'] : null,
            isset($row['mobile_no']) ? (string) $row['mobile_no'] : null,
            isset($row['address']) ? (string) $row['address'] : null,
            (int) ($row['is_profile_completed'] ?? 0),
            isset($row['created_at']) ? (string) $row['created_at'] : null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null
        );
    }
}
