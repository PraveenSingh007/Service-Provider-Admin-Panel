<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Repository\AttendanceRepository;
use App\Admin\Repository\EmployeeRepository;

/**
 * Attendance Management Service
 * Business logic for marking and fetching attendance records.
 */
class AttendanceManagementService
{
    private AttendanceRepository $attendanceRepository;
    private EmployeeRepository $employeeRepository;

    public function __construct(AttendanceRepository $attendanceRepository, EmployeeRepository $employeeRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Mark attendance for multiple employees for a specific date.
     *
     * @param string $date Y-m-d
     * @param array<int, string> $attendanceData Keyed by employee_id => status
     * @param array<int, string> $checkInData Keyed by employee_id => check_in_time
     * @param array<int, string> $checkOutData Keyed by employee_id => check_out_time
     * @param array<int, string> $notesData Keyed by employee_id => notes
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function markBulkAttendance(
        string $date,
        array $attendanceData,
        array $checkInData = [],
        array $checkOutData = [],
        array $notesData = []
    ): array {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $employees = $this->employeeRepository->findAll();
        $successCount = 0;

        foreach ($employees as $emp) {
            $empId = $emp->getId();
            if ($empId === null || !$this->isEligibleForAttendance($emp, $date)) {
                continue;
            }
            $status = (string) ($attendanceData[$empId] ?? 'present');
            $checkIn = !empty($checkInData[$empId]) ? (string) $checkInData[$empId] : null;
            $checkOut = !empty($checkOutData[$empId]) ? (string) $checkOutData[$empId] : null;
            $notes = !empty($notesData[$empId]) ? (string) $notesData[$empId] : null;

            if ($this->attendanceRepository->save($empId, $date, $status, $checkIn, $checkOut, $notes)) {
                $successCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Attendance saved successfully for {$successCount} employee(s).",
            'errors' => [],
        ];
    }

    /**
     * Get attendance map for a specific date.
     *
     * @return array<int, \App\Model\Attendance>
     */
    public function getAttendanceByDate(string $date): array
    {
        return $this->attendanceRepository->findByDate($date);
    }

    /**
     * Check if an employee is eligible for attendance on a specific date.
     */
    public function isEligibleForAttendance(\App\Model\Employee $emp, string $date): bool
    {
        if ($emp->getStatus() === 'active') {
            return true;
        }

        $changeDate = $emp->getStatusChangeDate();
        if ($changeDate !== null && $date <= $changeDate) {
            return true;
        }

        return false;
    }
}
