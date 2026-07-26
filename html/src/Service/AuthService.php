<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;

/**
 * Authentication Service
 * Encapsulates authentication business logic and validation rules for admin_login.
 */
class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Authenticate admin user credentials against admin_login table.
     *
     * @return array{success: bool, message: string, user: ?User, errors: string[]}
     */
    public function authenticate(string $username, string $password): array
    {
        $username = trim($username);

        if (empty($username)) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'user' => null,
                'errors' => ['Username or email is required.'],
            ];
        }

        if (empty($password)) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'user' => null,
                'errors' => ['Password is required.'],
            ];
        }

        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Authentication failure',
                'user' => null,
                'errors' => ['Invalid username or password.'],
            ];
        }

        $storedPassword = $user->getPasswordHash();
        $isPasswordValid = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

        if (!$isPasswordValid) {
            return [
                'success' => false,
                'message' => 'Authentication failure',
                'user' => null,
                'errors' => ['Invalid username or password.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Authentication successful.',
            'user' => $user,
            'errors' => [],
        ];
    }
}
