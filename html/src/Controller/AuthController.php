<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;

/**
 * Authentication Controller
 * Handles authentication requests for admin_login.
 */
class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user login request.
     *
     * @param array<string, mixed> $requestData
     * @return array{status: int, response: array<string, mixed>}
     */
    public function login(array $requestData, string $csrfTokenFromSession): array
    {
        $submittedToken = (string) ($requestData['csrf_token'] ?? '');
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

        $username = trim((string) ($requestData['username'] ?? $requestData['email'] ?? ''));
        $password = (string) ($requestData['password'] ?? '');

        $result = $this->authService->authenticate($username, $password);

        if (!$result['success']) {
            $statusCode = $result['message'] === 'Validation error' ? 422 : 401;
            return [
                'status' => $statusCode,
                'response' => [
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'],
                ],
            ];
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $user = $result['user'];
        if ($user !== null && isset($_SESSION)) {
            $_SESSION['user'] = $user->toArray();
        }

        return [
            'status' => 200,
            'response' => [
                'success' => true,
                'message' => 'Login successful',
                'redirect' => 'dashboard.php',
                'data' => [
                    'user' => $user !== null ? $user->toArray() : null,
                ],
            ],
        ];
    }
}
