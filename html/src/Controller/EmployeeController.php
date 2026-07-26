<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EmployeeManagementService;

/**
 * Employee Controller
 * Manages HTTP requests for Employee operations.
 */
class EmployeeController
{
    private EmployeeManagementService $service;

    public function __construct(EmployeeManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all employees list.
     *
     * @return array{status: int, response: array<string, mixed>}
     */
    public function index(): array
    {
        $employees = $this->service->getAllEmployees();
        $dataList = [];

        foreach ($employees as $emp) {
            $dataList[] = $emp->toArray();
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => 'Employees retrieved successfully',
                'data' => [
                    'employees' => $dataList,
                ],
            ],
        ];
    }

    /**
     * Store new employee request.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed>|null $fileData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function store(array $postData, ?array $fileData, string $csrfTokenFromSession): array
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

        $result = $this->service->createEmployee($postData, $fileData);

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
                'redirect' => 'employees.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Update employee request.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed>|null $fileData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function update(int $id, array $postData, ?array $fileData, string $csrfTokenFromSession): array
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

        $result = $this->service->updateEmployee($id, $postData, $fileData);

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
                'redirect' => 'employees.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Delete employee request.
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

        $result = $this->service->deleteEmployee($id);

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
