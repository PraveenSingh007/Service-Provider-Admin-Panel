<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\DailyExpense;
use App\Admin\Repository\DailyExpenseRepository;

class DailyExpenseManagementService
{
    private DailyExpenseRepository $repository;

    public function __construct(DailyExpenseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get expenses filtered by Date Range.
     *
     * @return DailyExpense[]
     */
    public function getExpensesByDateRange(string $startDate, string $endDate): array
    {
        if (empty($startDate)) {
            $startDate = date('Y-m-01');
        }
        if (empty($endDate)) {
            $endDate = date('Y-m-d');
        }
        return $this->repository->findByDateRange($startDate, $endDate);
    }

    /**
     * Get expense by primary key ID.
     */
    public function getExpenseById(int $id): ?DailyExpense
    {
        return $this->repository->findById($id);
    }

    /**
     * Save (create or update) an expense.
     *
     * @param array<string, mixed> $data
     * @param string $user
     * @return array{success: bool, message: string}
     */
    public function saveExpense(array $data, string $user = 'Admin'): array
    {
        $id = isset($data['id']) && ((int)$data['id'] > 0) ? (int) $data['id'] : null;
        $type = trim((string) ($data['expense_type'] ?? ''));
        $employeeId = isset($data['employee_id']) && ((int)$data['employee_id'] > 0) ? (int) $data['employee_id'] : null;
        $amount = (float) ($data['amount'] ?? 0.0);
        $date = trim((string) ($data['expense_date'] ?? date('Y-m-d')));
        $notes = trim((string) ($data['notes'] ?? ''));

        if (empty($type)) {
            return ['success' => false, 'message' => 'Expense Type is required.'];
        }
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Expense Amount must be greater than zero.'];
        }
        if (empty($date)) {
            return ['success' => false, 'message' => 'Expense Date is required.'];
        }

        $expense = new DailyExpense(
            $id,
            $type,
            $employeeId,
            $amount,
            $date,
            $notes !== '' ? $notes : null,
            $user
        );

        if ($this->repository->save($expense)) {
            $actionWord = $id !== null ? 'updated' : 'created';
            return ['success' => true, 'message' => "Daily Expense record {$actionWord} successfully!"];
        }

        return ['success' => false, 'message' => 'Failed to save daily expense record in database.'];
    }

    /**
     * Delete an expense record.
     */
    public function deleteExpense(int $id): array
    {
        if ($this->repository->delete($id)) {
            return ['success' => true, 'message' => 'Daily Expense record deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete daily expense record.'];
    }
}
