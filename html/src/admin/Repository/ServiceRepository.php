<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Service;
use mysqli;
use Throwable;

/**
 * Service Repository
 * Handles database operations for services table using prepared statements.
 */
class ServiceRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Retrieve all services.
     *
     * @return Service[]
     */
    public function findAll(): array
    {
        $services = [];
        try {
            $sql = 'SELECT id, service_name, service_image, created_at, updated_at FROM services ORDER BY id ASC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $services[] = new Service(
                        (int) $row['id'],
                        (string) $row['service_name'],
                        $row['service_image'] !== null ? (string) $row['service_image'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('ServiceRepository findAll error: ' . $e->getMessage());
        }

        return $services;
    }

    /**
     * Find a service by ID.
     */
    public function findById(int $id): ?Service
    {
        try {
            $sql = 'SELECT id, service_name, service_image, created_at, updated_at FROM services WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return new Service(
                        (int) $row['id'],
                        (string) $row['service_name'],
                        $row['service_image'] !== null ? (string) $row['service_image'] : null,
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('ServiceRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Insert a new service.
     */
    public function create(string $serviceName, ?string $serviceImage): bool
    {
        try {
            $sql = 'INSERT INTO services (service_name, service_image) VALUES (?, ?)';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('ss', $serviceName, $serviceImage);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRepository create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing service.
     */
    public function update(int $id, string $serviceName, ?string $serviceImage): bool
    {
        try {
            if ($serviceImage !== null) {
                $sql = 'UPDATE services SET service_name = ?, service_image = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) {
                    return false;
                }
                $stmt->bind_param('ssi', $serviceName, $serviceImage, $id);
            } else {
                $sql = 'UPDATE services SET service_name = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) {
                    return false;
                }
                $stmt->bind_param('si', $serviceName, $id);
            }

            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRepository update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a service by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM services WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRepository delete error: ' . $e->getMessage());
            return false;
        }
    }
}
