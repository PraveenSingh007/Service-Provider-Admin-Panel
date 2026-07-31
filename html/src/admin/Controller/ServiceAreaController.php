<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Service\ServiceAreaManagementService;

/**
 * Service Area Controller
 * Receives HTTP requests, validates inputs & CSRF, delegates to ServiceAreaManagementService.
 */
class ServiceAreaController
{
    private ServiceAreaManagementService $service;

    public function __construct(ServiceAreaManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all service areas list.
     *
     * @return array{status: int, response: array<string, mixed>}
     */
    public function index(): array
    {
        $areas = $this->service->getAllAreas();
        $dataList = [];

        foreach ($areas as $area) {
            $dataList[] = $area->toArray();
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => 'Service Areas retrieved successfully',
                'data' => [
                    'areas' => $dataList,
                ],
            ],
        ];
    }

    /**
     * Store new service area request.
     *
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function store(array $postData, string $csrfTokenFromSession): array
    {
        $submittedToken = (string) ($postData['csrf_token'] ?? '');
        if (empty($submittedToken) || !hash_equals($csrfTokenFromSession, $submittedToken)) {
            return [
                'status' => 403,
                'response' => [
                    'success' => false,
                    'message' => 'CSRF validation failed',
                    'errors' => ['Invalid security token. Please refresh and try again.'],
                ],
            ];
        }

        $areaName = (string) ($postData['area_name'] ?? '');
        $pincode = (string) ($postData['pincode'] ?? '');
        $city = (string) ($postData['city'] ?? '');
        $state = (string) ($postData['state'] ?? '');

        $result = $this->service->createArea($areaName, $pincode, $city, $state);

        if (!$result['success']) {
            return [
                'status' => 422,
                'response' => [
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'],
                ],
            ];
        }

        return [
            'status' => 201,
            'response' => [
                'success' => true,
                'message' => $result['message'],
                'redirect' => 'service-areas.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Update service area request.
     *
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function update(int $id, array $postData, string $csrfTokenFromSession): array
    {
        $submittedToken = (string) ($postData['csrf_token'] ?? '');
        if (empty($submittedToken) || !hash_equals($csrfTokenFromSession, $submittedToken)) {
            return [
                'status' => 403,
                'response' => [
                    'success' => false,
                    'message' => 'CSRF validation failed',
                    'errors' => ['Invalid security token. Please refresh and try again.'],
                ],
            ];
        }

        $areaName = (string) ($postData['area_name'] ?? '');
        $pincode = (string) ($postData['pincode'] ?? '');
        $city = (string) ($postData['city'] ?? '');
        $state = (string) ($postData['state'] ?? '');

        $result = $this->service->updateArea($id, $areaName, $pincode, $city, $state);

        if (!$result['success']) {
            return [
                'status' => 422,
                'response' => [
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'],
                ],
            ];
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => $result['message'],
                'redirect' => 'service-areas.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Delete service area request.
     *
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function destroy(int $id, array $postData, string $csrfTokenFromSession): array
    {
        $submittedToken = (string) ($postData['csrf_token'] ?? '');
        if (empty($submittedToken) || !hash_equals($csrfTokenFromSession, $submittedToken)) {
            return [
                'status' => 403,
                'response' => [
                    'success' => false,
                    'message' => 'CSRF validation failed',
                    'errors' => ['Invalid security token. Please refresh and try again.'],
                ],
            ];
        }

        $result = $this->service->deleteArea($id);

        return [
            'status' => $result['success'] ? 200 : 400,
            'response' => [
                'success' => $result['success'],
                'message' => $result['message'],
                'errors' => $result['errors'],
            ],
        ];
    }
}
