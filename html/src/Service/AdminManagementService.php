<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;

/**
 * Admin Management Service
 * Business logic and validation rules for Admin user management.
 */
class AdminManagementService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Retrieve all admin users.
     *
     * @return User[]
     */
    public function getAllAdmins(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Find admin user by ID.
     */
    public function getAdminById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Create a new admin user.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function createAdmin(string $firstName, string $lastName, string $username, string $password, string $role): array
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $username = trim($username);
        $password = (string) $password;
        $role = trim($role);

        $errors = [];
        if (empty($username)) {
            $errors[] = 'Admin Username is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        if (empty($role)) {
            $role = 'admin';
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => $errors,
            ];
        }

        $existing = $this->userRepository->findByUsername($username);
        if ($existing !== null) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['An admin with this username already exists.'],
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $created = $this->userRepository->create($firstName, $lastName, $username, $passwordHash, $role);

        if (!$created) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to create new admin.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Admin created successfully.',
            'errors' => [],
        ];
    }

    /**
     * Update an existing admin user.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateAdmin(int $id, string $firstName, string $lastName, string $username, string $password, string $role): array
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $username = trim($username);
        $role = trim($role);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Invalid Admin ID.'],
            ];
        }

        if (empty($username)) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Admin Username is required.'],
            ];
        }

        if (empty($role)) {
            $role = 'admin';
        }

        $existing = $this->userRepository->findById($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Not found',
                'errors' => ['Admin not found.'],
            ];
        }

        // Check for duplicate username if changed
        if (strtolower($existing->getUsername()) !== strtolower($username)) {
            $duplicate = $this->userRepository->findByUsername($username);
            if ($duplicate !== null) {
                return [
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => ['An admin with this username already exists.'],
                ];
            }
        }

        $passwordHash = null;
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        $updated = $this->userRepository->update($id, $firstName, $lastName, $username, $passwordHash, $role);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to update admin.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Admin updated successfully.',
            'errors' => [],
        ];
    }

    /**
     * Delete an admin user.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function deleteAdmin(int $id, int $currentUserId): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Invalid Admin ID.'],
            ];
        }

        if ($id === $currentUserId) {
            return [
                'success' => false,
                'message' => 'Permission denied',
                'errors' => ['You cannot delete your own logged-in admin account.'],
            ];
        }

        $deleted = $this->userRepository->delete($id);
        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to delete admin account.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Admin deleted successfully.',
            'errors' => [],
        ];
    }
}
