<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\CallbackRequest;
use mysqli;

/**
 * Repository for managing callback_requests table in database.
 */
class CallbackRequestRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS callback_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            callback_no VARCHAR(50) NOT NULL UNIQUE,
            customer_name VARCHAR(150) NOT NULL,
            mobile_no VARCHAR(20) NOT NULL,
            service_category VARCHAR(100) DEFAULT 'other',
            preferred_time_slot VARCHAR(50) DEFAULT 'anytime',
            note TEXT NULL,
            status ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$this->db->query($sql);
    }

    /**
     * Fetch all callback requests ordered by newest first.
     *
     * @return CallbackRequest[]
     */
    public function findAll(?string $statusFilter = null): array
    {
        $records = [];
        if ($statusFilter !== null && in_array($statusFilter, ['pending', 'contacted', 'completed', 'cancelled'], true)) {
            $stmt = $this->db->prepare("SELECT * FROM callback_requests WHERE status = ? ORDER BY id DESC");
            if ($stmt) {
                $stmt->bind_param('s', $statusFilter);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $records[] = CallbackRequest::fromArray($row);
                }
                $stmt->close();
            }
        } else {
            $res = $this->db->query("SELECT * FROM callback_requests ORDER BY id DESC");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $records[] = CallbackRequest::fromArray($row);
                }
            }
        }
        return $records;
    }

    /**
     * Find callback request by ID.
     */
    public function findById(int $id): ?CallbackRequest
    {
        $stmt = $this->db->prepare("SELECT * FROM callback_requests WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            return $row ? CallbackRequest::fromArray($row) : null;
        }
        return null;
    }

    /**
     * Create a new callback request entry.
     */
    public function create(array $data): bool
    {
        $cbNo = (string) ($data['callback_no'] ?? ('CB-' . date('Ymd') . '-' . rand(1000, 9999)));
        $name = trim((string) ($data['customer_name'] ?? ''));
        $mobile = trim((string) ($data['mobile_no'] ?? ''));
        $cat = trim((string) ($data['service_category'] ?? 'other'));
        $time = trim((string) ($data['preferred_time_slot'] ?? 'anytime'));
        $note = isset($data['note']) ? trim((string) $data['note']) : null;
        $status = (string) ($data['status'] ?? 'pending');

        $stmt = $this->db->prepare("INSERT INTO callback_requests 
            (callback_no, customer_name, mobile_no, service_category, preferred_time_slot, note, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sssssss', $cbNo, $name, $mobile, $cat, $time, $note, $status);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    /**
     * Update status of callback request.
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'contacted', 'completed', 'cancelled'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE callback_requests SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $status, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    /**
     * Delete callback request by ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM callback_requests WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    /**
     * Count pending callback requests.
     */
    public function countPending(): int
    {
        $res = $this->db->query("SELECT COUNT(*) as cnt FROM callback_requests WHERE status = 'pending'");
        if ($res) {
            $row = $res->fetch_assoc();
            return (int) ($row['cnt'] ?? 0);
        }
        return 0;
    }
}
