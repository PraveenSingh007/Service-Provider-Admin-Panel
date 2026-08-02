<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\Salary;
use App\Admin\Repository\AttendanceRepository;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Repository\SalaryRepository;

/**
 * Salary Management Service
 * Auto-calculates salary based on attendance and generates monthly payslips.
 */
class SalaryManagementService
{
    private SalaryRepository $salaryRepository;
    private EmployeeRepository $employeeRepository;
    private AttendanceRepository $attendanceRepository;

    public function __construct(
        SalaryRepository $salaryRepository,
        EmployeeRepository $employeeRepository,
        AttendanceRepository $attendanceRepository
    ) {
        $this->salaryRepository = $salaryRepository;
        $this->employeeRepository = $employeeRepository;
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Generate monthly salaries for all active employees based on attendance.
     *
     * @param string $month Y-m (e.g. 2026-07)
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function generateMonthlySalaries(string $month): array
    {
        if (empty($month)) {
            $month = date('Y-m');
        }

        $startDate = strtotime($month . '-01');
        $daysInMonth = (int) date('t', $startDate);
        $year = (int) date('Y', $startDate);
        $m = (int) date('m', $startDate);

        $sundaysCount = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if ((int) date('N', mktime(0, 0, 0, $m, $d, $year)) === 7) {
                $sundaysCount++;
            }
        }

        $workingDays = $daysInMonth - $sundaysCount;
        $employees = $this->employeeRepository->findAll();

        $monthStartDate = date('Y-m-01', $startDate);

        $generatedCount = 0;
        foreach ($employees as $emp) {
            $empId = $emp->getId();
            if ($empId === null || !$this->isEligibleForSalary($emp, $month)) {
                continue;
            }

            $baseSalary = $emp->getEmpSalary();
            $summary = $this->attendanceRepository->getMonthlySummary($empId, $month);

            $presentDays = $summary['present'];
            $absentDays = $summary['absent'];
            $halfDays = $summary['half_day'];
            $leaveDays = $summary['leave'];

            // Effective paid working days = Present + Paid Leaves + (Half Days * 0.5)
            $effectiveDays = $presentDays + $leaveDays + ($halfDays * 0.5);

            // If no attendance marked for the month yet, default to full working days for preview
            $recordedDays = $presentDays + $absentDays + $halfDays + $leaveDays;
            if ($recordedDays === 0) {
                $effectiveDays = $workingDays;
                $presentDays = $workingDays;
            }

            $dailyRate = $workingDays > 0 ? ($baseSalary / $workingDays) : 0.0;
            $calculatedSalary = round($dailyRate * $effectiveDays, 2);
            $bonus = 0.0;
            $deductions = 0.0;
            $netSalary = round($calculatedSalary + $bonus - $deductions, 2);

            $notes = "Excluding {$sundaysCount} Sunday(s) ({$workingDays} working days out of {$daysInMonth} total days)";

            $saved = $this->salaryRepository->save(
                $empId,
                $month,
                $baseSalary,
                $workingDays,
                $presentDays,
                $absentDays,
                $halfDays,
                $leaveDays,
                $calculatedSalary,
                $bonus,
                $deductions,
                $netSalary,
                'pending',
                null,
                $notes
            );

            if ($saved) {
                $generatedCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Salaries generated successfully for {$generatedCount} employee(s) for {$month}.",
            'errors' => [],
        ];
    }

    /**
     * Get generated salary list for a month.
     *
     * @return Salary[]
     */
    public function getSalariesByMonth(string $month): array
    {
        return $this->salaryRepository->findByMonth($month);
    }

    /**
     * Mark salary as paid.
     */
    public function markSalaryPaid(int $salaryId): array
    {
        $paid = $this->salaryRepository->markAsPaid($salaryId, date('Y-m-d'));
        if (!$paid) {
            return ['success' => false, 'message' => 'Database error', 'errors' => ['Failed to update payment status.']];
        }
        return ['success' => true, 'message' => 'Salary marked as Paid.', 'errors' => []];
    }

    /**
     * Update salary record manually (Days Present, Leaves, Bonus, Deductions).
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateSalaryRecord(
        int $salaryId,
        int $presentDays,
        int $halfDays,
        int $leaveDays,
        float $bonus,
        float $deductions,
        ?string $notes
    ): array {
        $sal = $this->salaryRepository->findById($salaryId);
        if ($sal === null) {
            return ['success' => false, 'message' => 'Salary record not found.', 'errors' => []];
        }

        $totalDays = $sal->getTotalDays();
        $baseSalary = $sal->getBaseSalary();
        $absentDays = max(0, $totalDays - ($presentDays + $leaveDays + $halfDays));

        // Effective paid working days = Present + Paid Leaves + (Half Days * 0.5)
        $effectiveDays = $presentDays + $leaveDays + ($halfDays * 0.5);

        $dailyRate = $totalDays > 0 ? ($baseSalary / $totalDays) : 0.0;
        $calculatedSalary = round($dailyRate * $effectiveDays, 2);
        
        // Auto-calculate leave/unpaid absence deduction if not manually specified
        $leaveDeduction = round($dailyRate * $absentDays, 2);
        if ($deductions <= 0 && $absentDays > 0) {
            $deductions = $leaveDeduction;
        }

        $netSalary = max(0.0, round($calculatedSalary + $bonus - $deductions, 2));

        $updated = $this->salaryRepository->updateSalaryDetails(
            $salaryId,
            $presentDays,
            $leaveDays,
            $halfDays,
            $absentDays,
            $calculatedSalary,
            $bonus,
            $deductions,
            $netSalary,
            $notes
        );

        if (!$updated) {
            return ['success' => false, 'message' => 'Failed to update salary details in database.', 'errors' => []];
        }

        return ['success' => true, 'message' => 'Salary details updated successfully.', 'errors' => []];
    }

    /**
     * Check if salary has already been generated for all eligible employees for a given month.
     */
    public function isSalaryGeneratedForAllEligibleEmployees(string $month): bool
    {
        $allEmployees = $this->employeeRepository->findAll();
        $eligibleEmpIds = [];
        foreach ($allEmployees as $emp) {
            if ($emp->getId() !== null && $this->isEligibleForSalary($emp, $month)) {
                $eligibleEmpIds[] = $emp->getId();
            }
        }

        if (empty($eligibleEmpIds)) {
            return false;
        }

        $generatedSalaries = $this->salaryRepository->findByMonth($month);
        $generatedEmpIds = [];
        foreach ($generatedSalaries as $sal) {
            $generatedEmpIds[] = $sal->getEmployeeId();
        }

        foreach ($eligibleEmpIds as $empId) {
            if (!in_array($empId, $generatedEmpIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an employee is eligible for salary generation in a given month.
     */
    public function isEligibleForSalary(\App\Admin\Model\Employee $emp, string $month): bool
    {
        if ($emp->getStatus() === 'active') {
            return true;
        }

        $changeDate = $emp->getStatusChangeDate();
        if ($changeDate !== null) {
            $monthStartDate = date('Y-m-01', strtotime($month . '-01'));
            if ($changeDate >= $monthStartDate) {
                return true;
            }
        }

        return false;
    }
}
