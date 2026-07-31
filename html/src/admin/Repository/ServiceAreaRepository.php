<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\ServiceArea;
use mysqli;
use Throwable;

/**
 * Service Area Repository
 * Handles database operations for service_areas table using prepared statements.
 */
class ServiceAreaRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Retrieve all service areas.
     *
     * @return ServiceArea[]
     */
    public function findAll(): array
    {
        $areas = [];
        try {
            $sql = 'SELECT id, area_name, pincode, city, state, created_at, updated_at FROM service_areas ORDER BY id ASC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $areas[] = new ServiceArea(
                        (int) $row['id'],
                        (string) $row['area_name'],
                        (string) $row['pincode'],
                        (string) $row['city'],
                        (string) $row['state'],
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('ServiceAreaRepository findAll error: ' . $e->getMessage());
        }

        return $areas;
    }

    /**
     * Find a service area by ID.
     */
    public function findById(int $id): ?ServiceArea
    {
        try {
            $sql = 'SELECT id, area_name, pincode, city, state, created_at, updated_at FROM service_areas WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return new ServiceArea(
                        (int) $row['id'],
                        (string) $row['area_name'],
                        (string) $row['pincode'],
                        (string) $row['city'],
                        (string) $row['state'],
                        isset($row['created_at']) ? (string) $row['created_at'] : null,
                        isset($row['updated_at']) ? (string) $row['updated_at'] : null
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('ServiceAreaRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Insert a new service area.
     */
    public function create(string $areaName, string $pincode, string $city, string $state): bool
    {
        try {
            $sql = 'INSERT INTO service_areas (area_name, pincode, city, state) VALUES (?, ?, ?, ?)';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('ssss', $areaName, $pincode, $city, $state);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceAreaRepository create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing service area.
     */
    public function update(int $id, string $areaName, string $pincode, string $city, string $state): bool
    {
        try {
            $sql = 'UPDATE service_areas SET area_name = ?, pincode = ?, city = ?, state = ? WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('ssssi', $areaName, $pincode, $city, $state, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceAreaRepository update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a service area by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM service_areas WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceAreaRepository delete error: ' . $e->getMessage());
            return false;
        }
    }
}
