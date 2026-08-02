<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\Employee;
use App\Admin\Repository\EmployeeRepository;
use Throwable;

/**
 * Employee Management Service
 * Business logic and photo upload handling for Employees.
 */
class EmployeeManagementService
{
    private EmployeeRepository $repository;
    private string $uploadDir;

    public function __construct(EmployeeRepository $repository)
    {
        $this->repository = $repository;
        $this->uploadDir = dirname(__DIR__, 3) . '/uploads/employees/';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
            @chmod($this->uploadDir, 0777);
        }
    }

    /**
     * Get all employees.
     *
     * @return Employee[]
     */
    public function getAllEmployees(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Find employee by ID.
     */
    public function getEmployeeById(int $id): ?Employee
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new employee.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed>|null $fileData
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function createEmployee(array $postData, ?array $fileData): array
    {
        $empCode = trim((string) ($postData['emp_code'] ?? ''));
        $empName = trim((string) ($postData['emp_name'] ?? ''));
        $empEmail = trim((string) ($postData['emp_email'] ?? ''));
        $empMobile = trim((string) ($postData['emp_mobile'] ?? ''));
        $empAddress = trim((string) ($postData['emp_address'] ?? ''));
        $empRole = trim((string) ($postData['emp_role'] ?? ''));
        $empSalary = (float) ($postData['emp_salary'] ?? 0.0);
        $joiningDate = trim((string) ($postData['joining_date'] ?? date('Y-m-d')));
        $status = trim((string) ($postData['status'] ?? 'active'));
        $statusChangeDate = !empty($postData['status_change_date']) ? trim((string)$postData['status_change_date']) : null;
        if ($status !== 'active' && empty($statusChangeDate)) {
            $statusChangeDate = date('Y-m-d');
        } elseif ($status === 'active') {
            $statusChangeDate = null;
        }

        $errors = [];
        if (empty($empCode)) {
            $errors[] = 'Employee ID / Code is required.';
        }
        if (empty($empName)) {
            $errors[] = 'Employee Name is required.';
        }
        if (empty($empEmail) || !filter_var($empEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid Employee Email is required.';
        }
        if (empty($empMobile)) {
            $errors[] = 'Employee Mobile Number is required.';
        }
        if (empty($empAddress)) {
            $errors[] = 'Employee Address is required.';
        }
        if (empty($empRole)) {
            $errors[] = 'Employee Role is required.';
        }
        if ($empSalary <= 0) {
            $errors[] = 'Employee Base Salary must be greater than zero.';
        }

        if (count($errors) > 0) {
            return ['success' => false, 'message' => 'Validation error', 'errors' => $errors];
        }

        $existing = $this->repository->findByCodeOrEmail($empCode, $empEmail);
        if ($existing !== null) {
            return ['success' => false, 'message' => 'Validation error', 'errors' => ['An employee with this Code or Email already exists.']];
        }

        $empPhoto = null;
        $empAadhar = !empty($postData['emp_aadhar']) ? trim((string)$postData['emp_aadhar']) : null;
        $empPan = !empty($postData['emp_pan']) ? trim((string)$postData['emp_pan']) : null;

        if ($fileData !== null && isset($fileData['emp_photo']) && $fileData['emp_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $this->processImageUpload($fileData['emp_photo']);
            if ($uploadedPath !== null) {
                $empPhoto = $uploadedPath;
            }
        }

        $created = $this->repository->create($empCode, $empName, $empEmail, $empMobile, $empAddress, $empRole, $empSalary, $empPhoto, $empAadhar, $empPan, $joiningDate, $status, $statusChangeDate);

        if (!$created) {
            return ['success' => false, 'message' => 'Database error', 'errors' => ['Failed to save employee to database.']];
        }

        return ['success' => true, 'message' => 'Employee created successfully.', 'errors' => []];
    }

    /**
     * Update an employee.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed>|null $fileData
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateEmployee(int $id, array $postData, ?array $fileData): array
    {
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            return ['success' => false, 'message' => 'Not found', 'errors' => ['Employee not found.']];
        }

        $empCode = trim((string) ($postData['emp_code'] ?? ''));
        $empName = trim((string) ($postData['emp_name'] ?? ''));
        $empEmail = trim((string) ($postData['emp_email'] ?? ''));
        $empMobile = trim((string) ($postData['emp_mobile'] ?? ''));
        $empAddress = trim((string) ($postData['emp_address'] ?? ''));
        $empRole = trim((string) ($postData['emp_role'] ?? ''));
        $empSalary = (float) ($postData['emp_salary'] ?? 0.0);
        $empAadhar = !empty($postData['emp_aadhar']) ? trim((string)$postData['emp_aadhar']) : null;
        $empPan = !empty($postData['emp_pan']) ? trim((string)$postData['emp_pan']) : null;
        $joiningDate = trim((string) ($postData['joining_date'] ?? date('Y-m-d')));
        $status = trim((string) ($postData['status'] ?? 'active'));
        $statusChangeDate = !empty($postData['status_change_date']) ? trim((string)$postData['status_change_date']) : null;

        if ($status !== 'active' && empty($statusChangeDate)) {
            // Keep existing status change date if available, or set to today
            $statusChangeDate = $existing->getStatusChangeDate() ?? date('Y-m-d');
        } elseif ($status === 'active') {
            $statusChangeDate = null;
        }

        $errors = [];
        if (empty($empCode)) {
            $errors[] = 'Employee ID / Code is required.';
        }
        if (empty($empName)) {
            $errors[] = 'Employee Name is required.';
        }
        if (empty($empEmail) || !filter_var($empEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid Employee Email is required.';
        }
        if (empty($empMobile)) {
            $errors[] = 'Employee Mobile Number is required.';
        }
        if ($empSalary <= 0) {
            $errors[] = 'Employee Base Salary must be greater than zero.';
        }

        if (count($errors) > 0) {
            return ['success' => false, 'message' => 'Validation error', 'errors' => $errors];
        }

        $empPhoto = $existing->getEmpPhoto();
        if ($fileData !== null && isset($fileData['emp_photo']) && $fileData['emp_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $this->processImageUpload($fileData['emp_photo']);
            if ($uploadedPath !== null) {
                $empPhoto = $uploadedPath;
            }
        }

        $updated = $this->repository->update($id, $empCode, $empName, $empEmail, $empMobile, $empAddress, $empRole, $empSalary, $empPhoto, $empAadhar, $empPan, $joiningDate, $status, $statusChangeDate);

        if (!$updated) {
            return ['success' => false, 'message' => 'Database error', 'errors' => ['Failed to update employee.']];
        }

        return ['success' => true, 'message' => 'Employee updated successfully.', 'errors' => []];
    }

    /**
     * Delete an employee.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function deleteEmployee(int $id): array
    {
        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            return ['success' => false, 'message' => 'Database error', 'errors' => ['Failed to delete employee.']];
        }
        return ['success' => true, 'message' => 'Employee deleted successfully.', 'errors' => []];
    }

    /**
     * Process uploaded employee photo.
     *
     * @param array<string, mixed> $file
     */
    private function processImageUpload(array $file): ?string
    {
        try {
            $filename = (string) ($file['name'] ?? '');
            $tmpName = (string) ($file['tmp_name'] ?? '');

            if (empty($filename) || empty($tmpName) || !is_uploaded_file($tmpName)) {
                return null;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowedExts, true)) {
                return null;
            }

            $newFilename = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $this->uploadDir . $newFilename;

            if (move_uploaded_file($tmpName, $destPath) || copy($tmpName, $destPath)) {
                @chmod($destPath, 0644);
                return '../../uploads/employees/' . $newFilename;
            }
        } catch (Throwable $e) {
            error_log('Employee photo upload error: ' . $e->getMessage());
        }

        return null;
    }
}
