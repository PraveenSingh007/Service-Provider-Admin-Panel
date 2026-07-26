<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ServiceManagementService;

/**
 * Service Controller
 * Receives HTTP requests, validates inputs & CSRF, delegates to ServiceManagementService.
 */
class ServiceController
{
    private ServiceManagementService $serviceManagementService;

    public function __construct(ServiceManagementService $serviceManagementService)
    {
        $this->serviceManagementService = $serviceManagementService;
    }

    /**
     * Get all services list.
     *
     * @return array{status: int, response: array<string, mixed>}
     */
    public function index(): array
    {
        $services = $this->serviceManagementService->getAllServices();
        $dataList = [];

        foreach ($services as $service) {
            $dataList[] = $service->toArray();
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => 'Services retrieved successfully',
                'data' => [
                    'services' => $dataList,
                ],
            ],
        ];
    }

    /**
     * Store new service request.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed>|null $filesData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function store(array $postData, ?array $filesData, string $csrfTokenFromSession): array
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

        $serviceName = (string) ($postData['service_name'] ?? '');
        $serviceImage = isset($filesData['service_image']) ? (array) $filesData['service_image'] : null;

        $result = $this->serviceManagementService->createService($serviceName, $serviceImage);

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
                'redirect' => 'services.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Update service request.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed>|null $filesData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function update(int $id, array $postData, ?array $filesData, string $csrfTokenFromSession): array
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

        $serviceName = (string) ($postData['service_name'] ?? '');
        $serviceImage = isset($filesData['service_image']) ? (array) $filesData['service_image'] : null;

        $result = $this->serviceManagementService->updateService($id, $serviceName, $serviceImage);

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
                'redirect' => 'services.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Delete service request.
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

        $result = $this->serviceManagementService->deleteService($id);

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
