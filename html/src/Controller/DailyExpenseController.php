<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DailyExpenseManagementService;

class DailyExpenseController
{
    private DailyExpenseManagementService $service;

    public function __construct(DailyExpenseManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle POST actions for Daily Expenses.
     *
     * @param array<string, mixed> $postData
     * @param string $user
     * @return array{success: bool, message: string}
     */
    public function handleRequest(array $postData, string $user = 'Admin'): array
    {
        $action = (string) ($postData['action'] ?? '');

        if ($action === 'save') {
            return $this->service->saveExpense($postData, $user);
        }

        if ($action === 'delete') {
            $id = (int) ($postData['id'] ?? 0);
            return $this->service->deleteExpense($id);
        }

        return ['success' => false, 'message' => 'Invalid action for Daily Expenses.'];
    }
}
