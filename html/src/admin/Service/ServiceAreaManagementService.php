<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\ServiceArea;
use App\Admin\Repository\ServiceAreaRepository;

/**
 * Service Area Management Service
 * Contains business logic and validation rules for Service Areas.
 */
class ServiceAreaManagementService
{
    private ServiceAreaRepository $repository;

    public function __construct(ServiceAreaRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all service areas.
     *
     * @return ServiceArea[]
     */
    public function getAllAreas(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Get available service areas as array of arrays.
     *
     * @return array<int, array<string, string>>
     */
    public function getAvailableServiceAreas(): array
    {
        $areas = $this->repository->findAll();
        $result = [];
        foreach ($areas as $area) {
            $result[] = [
                'id' => (string) $area->getId(),
                'area_name' => $area->getAreaName(),
                'pincode' => $area->getPincode(),
                'city' => $area->getCity(),
                'state' => $area->getState(),
            ];
        }
        return $result;
    }

    /**
     * Find area by ID.
     */
    public function getAreaById(int $id): ?ServiceArea
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new service area.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function createArea(string $areaName, string $pincode, string $city, string $state): array
    {
        $areaName = trim($areaName);
        $pincode = trim($pincode);
        $city = trim($city);
        $state = trim($state);

        $errors = [];
        if (empty($areaName)) {
            $errors[] = 'Area Name is required.';
        }
        if (empty($pincode)) {
            $errors[] = 'Pin Code is required.';
        }
        if (empty($city)) {
            $errors[] = 'City is required.';
        }
        if (empty($state)) {
            $errors[] = 'State is required.';
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => $errors,
            ];
        }

        $created = $this->repository->create($areaName, $pincode, $city, $state);

        if (!$created) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to save service area to database.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Service Area created successfully.',
            'errors' => [],
        ];
    }

    /**
     * Update an existing service area.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateArea(int $id, string $areaName, string $pincode, string $city, string $state): array
    {
        $areaName = trim($areaName);
        $pincode = trim($pincode);
        $city = trim($city);
        $state = trim($state);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Invalid Service Area ID.'],
            ];
        }

        $errors = [];
        if (empty($areaName)) {
            $errors[] = 'Area Name is required.';
        }
        if (empty($pincode)) {
            $errors[] = 'Pin Code is required.';
        }
        if (empty($city)) {
            $errors[] = 'City is required.';
        }
        if (empty($state)) {
            $errors[] = 'State is required.';
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => $errors,
            ];
        }

        $existing = $this->repository->findById($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Not found',
                'errors' => ['Service Area not found.'],
            ];
        }

        $updated = $this->repository->update($id, $areaName, $pincode, $city, $state);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to update service area.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Service Area updated successfully.',
            'errors' => [],
        ];
    }

    /**
     * Delete a service area by ID.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function deleteArea(int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Invalid Service Area ID.'],
            ];
        }

        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to delete service area.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Service Area deleted successfully.',
            'errors' => [],
        ];
    }
}
