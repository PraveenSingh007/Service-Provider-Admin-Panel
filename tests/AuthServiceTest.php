<?php

declare(strict_types=1);

namespace Tests;

use App\Model\User;
use App\Repository\UserRepository;
use App\Service\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials(): void
    {
        $password = 'Secret123!';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User(1, 'admin@example.com', $hashedPassword, '2026-01-01 00:00:00');

        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findByEmail')
            ->with('admin@example.com')
            ->willReturn($user);

        $authService = new AuthService($repositoryMock);
        $result = $authService->authenticate('admin@example.com', $password);

        $this->assertTrue($result['success']);
        $this->assertEquals('Authentication successful.', $result['message']);
        $this->assertNotNull($result['user']);
        $this->assertEmpty($result['errors']);
    }

    public function test_invalid_email_returns_error(): void
    {
        $repositoryMock = $this->createMock(UserRepository::class);
        $authService = new AuthService($repositoryMock);

        $result = $authService->authenticate('invalid-email', 'somepassword');

        $this->assertFalse($result['success']);
        $this->assertEquals('Validation error', $result['message']);
        $this->assertContains('A valid email address is required.', $result['errors']);
    }

    public function test_empty_password_returns_error(): void
    {
        $repositoryMock = $this->createMock(UserRepository::class);
        $authService = new AuthService($repositoryMock);

        $result = $authService->authenticate('user@example.com', '');

        $this->assertFalse($result['success']);
        $this->assertEquals('Validation error', $result['message']);
        $this->assertContains('Password is required.', $result['errors']);
    }

    public function test_incorrect_password_returns_failure(): void
    {
        $hashedPassword = password_hash('RightPassword', PASSWORD_BCRYPT);
        $user = new User(1, 'user@example.com', $hashedPassword);

        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->method('findByEmail')->willReturn($user);

        $authService = new AuthService($repositoryMock);
        $result = $authService->authenticate('user@example.com', 'WrongPassword');

        $this->assertFalse($result['success']);
        $this->assertEquals('Authentication failure', $result['message']);
        $this->assertContains('Invalid email or password.', $result['errors']);
    }

    public function test_non_existent_user_returns_failure(): void
    {
        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->method('findByEmail')->willReturn(null);

        $authService = new AuthService($repositoryMock);
        $result = $authService->authenticate('notfound@example.com', 'password');

        $this->assertFalse($result['success']);
        $this->assertEquals('Authentication failure', $result['message']);
        $this->assertContains('Invalid email or password.', $result['errors']);
    }
}
