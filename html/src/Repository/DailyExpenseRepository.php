<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\DailyExpense;
use mysqli;
use Throwable;

class DailyExpenseRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetch all daily expenses ordered by ID ascending.
     *
     * @return DailyExpense[]
     */
    public function findAll(): array
    {
        $expenses = [];
        try {
            $sql = 'SELECT d.id, d.expense_type, d.employee_id, d.amount, d.expense_date, d.notes, d.created_by, d.created_at, d.updated_at, e.emp_name as employee_name FROM daily_expenses d LEFT JOIN employees e ON d.employee_id = e.id ORDER BY d.id ASC';
            $result = $this->connection->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $expenses[] = new DailyExpense(
                        (int) $row['id'],
                        (string) $row['expense_type'],
                        $row['employee_id'] !== null ? (int) $row['employee_id'] : null,
                        (float) $row['amount'],
                        (string) $row['expense_date'],
                        $row['notes'] !== null ? (string) $row['notes'] : null,
                        $row['created_by'] !== null ? (string) $row['created_by'] : null,
                        $row['employee_name'] !== null ? (string) $row['employee_name'] : null,
                        (string) $row['created_at'],
                        (string) $row['updated_at']
                    );
                }
                $result->free();
            }
        } catch (Throwable $e) {
            error_log('DailyExpenseRepository findAll error: ' . $e->getMessage());
        }

        return $expenses;
    }

    /**
     * Fetch daily expenses within a date range (between startDate and endDate).
     *
     * @return DailyExpense[]
     */
    public function findByDateRange(string $startDate, string $endDate): array
    {
        $expenses = [];
        try {
            $stmt = $this->connection->prepare('SELECT d.id, d.expense_type, d.employee_id, d.amount, d.expense_date, d.notes, d.created_by, d.created_at, d.updated_at, e.emp_name as employee_name FROM daily_expenses d LEFT JOIN employees e ON d.employee_id = e.id WHERE d.expense_date BETWEEN ? AND ? ORDER BY d.expense_date DESC, d.id DESC');
            if ($stmt) {
                $stmt->bind_param('ss', $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $expenses[] = new DailyExpense(
                        (int) $row['id'],
                        (string) $row['expense_type'],
                        $row['employee_id'] !== null ? (int) $row['employee_id'] : null,
                        (float) $row['amount'],
                        (string) $row['expense_date'],
                        $row['notes'] !== null ? (string) $row['notes'] : null,
                        $row['created_by'] !== null ? (string) $row['created_by'] : null,
                        $row['employee_name'] !== null ? (string) $row['employee_name'] : null,
                        (string) $row['created_at'],
                        (string) $row['updated_at']
                    );
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('DailyExpenseRepository findByDateRange error: ' . $e->getMessage());
        }

        return $expenses;
    }

    /**
     * Find daily expense by primary key ID.
     */
    public function findById(int $id): ?DailyExpense
    {
        try {
            $stmt = $this->connection->prepare('SELECT d.id, d.expense_type, d.employee_id, d.amount, d.expense_date, d.notes, d.created_by, d.created_at, d.updated_at, e.emp_name as employee_name FROM daily_expenses d LEFT JOIN employees e ON d.employee_id = e.id WHERE d.id = ? LIMIT 1');
            if (!$stmt) {
                return null;
            }

            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $expense = new DailyExpense(
                    (int) $row['id'],
                    (string) $row['expense_type'],
                    $row['employee_id'] !== null ? (int) $row['employee_id'] : null,
                    (float) $row['amount'],
                    (string) $row['expense_date'],
                    $row['notes'] !== null ? (string) $row['notes'] : null,
                    $row['created_by'] !== null ? (string) $row['created_by'] : null,
                    $row['employee_name'] !== null ? (string) $row['employee_name'] : null,
                    (string) $row['created_at'],
                    (string) $row['updated_at']
                );
                $stmt->close();
                return $expense;
            }
            $stmt->close();
        } catch (Throwable $e) {
            error_log('DailyExpenseRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Save (insert or update) a daily expense.
     */
    public function save(DailyExpense $expense): bool
    {
        try {
            if ($expense->getId() !== null && $expense->getId() > 0) {
                $stmt = $this->connection->prepare('UPDATE daily_expenses SET expense_type = ?, employee_id = ?, amount = ?, expense_date = ?, notes = ?, created_by = ? WHERE id = ?');
                if (!$stmt) {
                    return false;
                }
                $type = $expense->getExpenseType();
                $empId = $expense->getEmployeeId();
                $amt = $expense->getAmount();
                $date = $expense->getExpenseDate();
                $notes = $expense->getNotes();
                $createdBy = $expense->getCreatedBy();
                $id = $expense->getId();

                $stmt->bind_param('sidsssi', $type, $empId, $amt, $date, $notes, $createdBy, $id);
                $success = $stmt->execute();
                $stmt->close();
                return $success;
            } else {
                $stmt = $this->connection->prepare('INSERT INTO daily_expenses (expense_type, employee_id, amount, expense_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                if (!$stmt) {
                    return false;
                }
                $type = $expense->getExpenseType();
                $empId = $expense->getEmployeeId();
                $amt = $expense->getAmount();
                $date = $expense->getExpenseDate();
                $notes = $expense->getNotes();
                $createdBy = $expense->getCreatedBy();

                $stmt->bind_param('sidsss', $type, $empId, $amt, $date, $notes, $createdBy);
                $success = $stmt->execute();
                $stmt->close();
                return $success;
            }
        } catch (Throwable $e) {
            error_log('DailyExpenseRepository save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete daily expense record.
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM daily_expenses WHERE id = ?');
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('DailyExpenseRepository delete error: ' . $e->getMessage());
            return false;
        }
    }
}
