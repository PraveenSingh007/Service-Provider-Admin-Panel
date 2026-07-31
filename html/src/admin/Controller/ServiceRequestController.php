<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Service\ServiceRequestManagementService;

/**
 * Service Request Controller
 * Handles requests for listing, creating, updating, assigning, and deleting service requests.
 */
class ServiceRequestController
{
    private ServiceRequestManagementService $service;

    public function __construct(ServiceRequestManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all service requests list.
     *
     * @return array{status: int, response: array<string, mixed>}
     */
    public function index(): array
    {
        $requests = $this->service->getAllServiceRequests();
        $dataList = [];

        foreach ($requests as $req) {
            $dataList[] = $req->toArray();
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => 'Service requests retrieved successfully',
                'data' => [
                    'requests' => $dataList,
                ],
            ],
        ];
    }

    /**
     * Store a new service request.
     *
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function store(array $postData, string $csrfTokenFromSession): array
    {
        if (!$this->validateCsrf($postData, $csrfTokenFromSession)) {
            return $this->csrfError();
        }

        $result = $this->service->createServiceRequest($postData);

        return [
            'status' => $result['success'] ? 201 : 422,
            'response' => $result,
        ];
    }

    /**
     * Update an existing service request.
     *
     * @param int $id
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function update(int $id, array $postData, string $csrfTokenFromSession): array
    {
        if (!$this->validateCsrf($postData, $csrfTokenFromSession)) {
            return $this->csrfError();
        }

        $result = $this->service->updateServiceRequest($id, $postData);

        return [
            'status' => $result['success'] ? 200 : 422,
            'response' => $result,
        ];
    }

    /**
     * Assign employee to service request.
     *
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function assign(array $postData, string $csrfTokenFromSession): array
    {
        if (!$this->validateCsrf($postData, $csrfTokenFromSession)) {
            return $this->csrfError();
        }

        $requestId = (int) ($postData['request_id'] ?? 0);
        $empIdRaw = $postData['assigned_employee_id'] ?? null;
        $employeeId = ($empIdRaw !== null && $empIdRaw !== '') ? (int) $empIdRaw : null;
        $notes = !empty($postData['request_status_notes']) ? (string) $postData['request_status_notes'] : null;

        $result = $this->service->assignEmployee($requestId, $employeeId, $notes);

        return [
            'status' => $result['success'] ? 200 : 400,
            'response' => $result,
        ];
    }

    /**
     * Delete service request.
     *
     * @param int $id
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function destroy(int $id, array $postData, string $csrfTokenFromSession): array
    {
        if (!$this->validateCsrf($postData, $csrfTokenFromSession)) {
            return $this->csrfError();
        }

        $result = $this->service->deleteServiceRequest($id);

        return [
            'status' => $result['success'] ? 200 : 400,
            'response' => $result,
        ];
    }

    /**
     * Helper to validate CSRF token.
     *
     * @param array<string, mixed> $postData
     */
    private function validateCsrf(array $postData, string $csrfTokenFromSession): bool
    {
        $submittedToken = (string) ($postData['csrf_token'] ?? '');
        return !empty($submittedToken) && hash_equals($csrfTokenFromSession, $submittedToken);
    }

    /**
     * Standard CSRF error response.
     *
     * @return array{status: int, response: array<string, mixed>}
     */
    private function csrfError(): array
    {
        return [
            'status' => 403,
            'response' => [
                'success' => false,
                'message' => 'CSRF validation failed',
                'errors' => ['Invalid security token. Please refresh and try again.'],
            ],
        ];
    }
}
