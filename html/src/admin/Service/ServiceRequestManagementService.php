<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\ServiceRequest;
use App\Admin\Repository\ServiceRequestRepository;

/**
 * ServiceRequest Management Service
 * Handles business logic, input validation, and pincode enforcement against service_areas.
 */
class ServiceRequestManagementService
{
    private ServiceRequestRepository $repository;

    public function __construct(ServiceRequestRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve all service requests.
     *
     * @return ServiceRequest[]
     */
    public function getAllServiceRequests(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Retrieve a service request by ID.
     */
    public function getServiceRequestById(int $id): ?ServiceRequest
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->findById($id);
    }

    /**
     * Get available service area pincodes for select dropdown.
     *
     * @return array<int, array{pincode: string, area_name: string, city: string, state: string}>
     */
    public function getAvailableServiceAreas(): array
    {
        return $this->repository->getAvailableServiceAreas();
    }

    /**
     * Get list of active employees for technician assignment.
     *
     * @return array<int, array{id: int, emp_name: string, emp_role: string, emp_mobile: string}>
     */
    public function getActiveEmployees(): array
    {
        return $this->repository->getActiveEmployees();
    }

    /**
     * Get available quotations for searchable dropdown.
     *
     * @return array<int, array{quotation_number: string, service_request_id: string, customer_name: string, customer_mobile: string, customer_email: string, total_amount: float, status: string}>
     */
    public function getAvailableQuotations(): array
    {
        return $this->repository->getAvailableQuotations();
    }

    /**
     * Get available invoices for searchable dropdown.
     *
     * @return array<int, array{invoice_number: string, service_request_id: string, customer_name: string, customer_mobile: string, customer_email: string, total_amount: float, payment_status: string}>
     */
    public function getAvailableInvoices(): array
    {
        return $this->repository->getAvailableInvoices();
    }

    /**
     * Create a new service request with strict pincode validation.
     *
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function createServiceRequest(array $data): array
    {
        $errors = $this->validateInput($data);

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ];
        }

        $created = $this->repository->create($data);

        if ($created) {
            return [
                'success' => true,
                'message' => 'Service request booked successfully.',
                'errors' => [],
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to save service request due to a database error.',
            'errors' => ['Database insertion failed.'],
        ];
    }

    /**
     * Update an existing service request with strict pincode validation.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateServiceRequest(int $id, array $data): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid service request ID.',
                'errors' => ['Invalid ID.'],
            ];
        }

        $existing = $this->repository->findById($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Service request not found.',
                'errors' => ['Request does not exist.'],
            ];
        }

        $errors = $this->validateInput($data);

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ];
        }

        $updated = $this->repository->update($id, $data);

        if ($updated) {
            return [
                'success' => true,
                'message' => 'Service request updated successfully.',
                'errors' => [],
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update service request.',
            'errors' => ['Database update failed.'],
        ];
    }

    /**
     * Assign employee to service request.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function assignEmployee(int $requestId, ?int $employeeId, ?string $notes = null): array
    {
        if ($requestId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid service request ID.',
                'errors' => ['Invalid request ID.'],
            ];
        }

        $employeeName = null;
        if ($employeeId !== null && $employeeId > 0) {
            $employees = $this->repository->getActiveEmployees();
            foreach ($employees as $emp) {
                if ($emp['id'] === $employeeId) {
                    $employeeName = $emp['emp_name'] . ' (' . $emp['emp_role'] . ')';
                    break;
                }
            }
        }

        $success = $this->repository->assignEmployee($requestId, $employeeId, $employeeName, $notes);

        if ($success) {
            return [
                'success' => true,
                'message' => $employeeId ? "Service request assigned to {$employeeName}." : "Assignment cleared.",
                'errors' => [],
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to assign technician.',
            'errors' => ['Assignment update failed.'],
        ];
    }

    /**
     * Delete service request.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function deleteServiceRequest(int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid service request ID.',
                'errors' => ['Invalid ID.'],
            ];
        }

        $deleted = $this->repository->delete($id);

        if ($deleted) {
            return [
                'success' => true,
                'message' => 'Service request deleted successfully.',
                'errors' => [],
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to delete service request.',
            'errors' => ['Database deletion failed.'],
        ];
    }

    /**
     * Validate user input fields.
     *
     * @param array<string, mixed> $data
     * @return string[]
     */
    private function validateInput(array $data): array
    {
        $errors = [];

        $customerName = trim((string) ($data['customer_name'] ?? ''));
        if ($customerName === '') {
            $errors[] = 'Customer name is required.';
        }

        $mobile = trim((string) ($data['request_by_mobile_no'] ?? ''));
        if ($mobile === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $mobile)) {
            $errors[] = 'Valid customer mobile number is required.';
        }

        $serviceName = trim((string) ($data['service_name'] ?? ''));
        if ($serviceName === '') {
            $errors[] = 'Service name is required.';
        }

        $address = trim((string) ($data['request_address'] ?? ''));
        if ($address === '') {
            $errors[] = 'Request address is required.';
        }

        // CRITICAL PINCODE VALIDATION AGAINST SERVICE_AREAS TABLE
        $pincode = trim((string) ($data['request_pincode'] ?? ''));
        if ($pincode === '') {
            $errors[] = 'Pincode is required.';
        } elseif (!$this->repository->isValidPincode($pincode)) {
            $errors[] = "Invalid Pincode ({$pincode})! Service is not available in this pincode. Please select a valid pincode from our registered service areas.";
        }

        return $errors;
    }
}
