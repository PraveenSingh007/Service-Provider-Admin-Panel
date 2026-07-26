<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AdminManagementService;

/**
 * Admin Controller
 * Handles HTTP requests for managing admin users.
 */
class AdminController
{
    private AdminManagementService $service;

    public function __construct(AdminManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all admin users list.
     *
     * @return array{status: int, response: array<string, mixed>}
     */
    public function index(): array
    {
        $admins = $this->service->getAllAdmins();
        $dataList = [];

        foreach ($admins as $admin) {
            $dataList[] = $admin->toArray();
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => 'Admin users retrieved successfully',
                'data' => [
                    'admins' => $dataList,
                ],
            ],
        ];
    }

    /**
     * Store new admin request.
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

        $username = (string) ($postData['admin_username'] ?? '');
        $password = (string) ($postData['admin_password'] ?? '');
        $role = (string) ($postData['admin_role'] ?? 'admin');

        $result = $this->service->createAdmin($username, $password, $role);

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
                'redirect' => 'admins.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Update admin request.
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

        $username = (string) ($postData['admin_username'] ?? '');
        $password = (string) ($postData['admin_password'] ?? '');
        $role = (string) ($postData['admin_role'] ?? 'admin');

        $result = $this->service->updateAdmin($id, $username, $password, $role);

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
                'redirect' => 'admins.php',
                'data' => [],
            ],
        ];
    }

    /**
     * Delete admin request.
     *
     * @param array<string, mixed> $postData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function destroy(int $id, array $postData, string $csrfTokenFromSession, int $currentUserId): array
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

        $result = $this->service->deleteAdmin($id, $currentUserId);

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
