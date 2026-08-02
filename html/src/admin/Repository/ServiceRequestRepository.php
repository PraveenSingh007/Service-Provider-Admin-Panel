<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\ServiceRequest;
use mysqli;
use Throwable;

/**
 * ServiceRequest Repository
 * Handles database operations for service_requests table and validation against service_areas.
 */
class ServiceRequestRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Retrieve all service requests.
     *
     * @return ServiceRequest[]
     */
    public function findAll(): array
    {
        $requests = [];
        try {
            $sql = 'SELECT * FROM service_requests ORDER BY id DESC';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $requests[] = $this->mapRowToEntity($row);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository findAll error: ' . $e->getMessage());
        }

        return $requests;
    }

    /**
     * Find a service request by ID.
     */
    public function findById(int $id): ?ServiceRequest
    {
        try {
            $sql = 'SELECT * FROM service_requests WHERE id = ? LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $stmt->close();
                    return $this->mapRowToEntity($row);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository findById error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if a given pincode is present in the service_areas table.
     * CRITICAL RULE: request_pincode must be present in service_areas table!
     */
    public function isValidPincode(string $pincode): bool
    {
        try {
            $sql = 'SELECT COUNT(*) as cnt FROM service_areas WHERE TRIM(pincode) = TRIM(?)';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('s', $pincode);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                return isset($row['cnt']) && ((int) $row['cnt']) > 0;
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository isValidPincode error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Retrieve all preset service area pincodes with details for form dropdowns.
     *
     * @return array<int, array{pincode: string, area_name: string, city: string, state: string}>
     */
    public function getAvailableServiceAreas(): array
    {
        $areas = [];
        try {
            $sql = 'SELECT DISTINCT pincode, area_name, city, state FROM service_areas ORDER BY pincode ASC, area_name ASC';
            $result = $this->connection->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $areas[] = [
                        'pincode' => (string) $row['pincode'],
                        'area_name' => (string) $row['area_name'],
                        'city' => (string) $row['city'],
                        'state' => (string) $row['state'],
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository getAvailableServiceAreas error: ' . $e->getMessage());
        }

        return $areas;
    }

    /**
     * Retrieve active employees for technician assignment.
     *
     * @return array<int, array{id: int, emp_name: string, emp_role: string, emp_mobile: string}>
     */
    public function getActiveEmployees(): array
    {
        $employees = [];
        try {
            $sql = 'SELECT id, emp_name, emp_role, emp_mobile FROM employees WHERE status = "active" ORDER BY emp_name ASC';
            $result = $this->connection->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $employees[] = [
                        'id' => (int) $row['id'],
                        'emp_name' => (string) $row['emp_name'],
                        'emp_role' => (string) $row['emp_role'],
                        'emp_mobile' => (string) $row['emp_mobile'],
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository getActiveEmployees error: ' . $e->getMessage());
        }

        return $employees;
    }

    /**
     * Retrieve all quotations from quotations table for searchable select dropdown.
     *
     * @return array<int, array{quotation_number: string, service_request_id: string, customer_name: string, customer_mobile: string, customer_email: string, total_amount: float, status: string}>
     */
    public function getAvailableQuotations(): array
    {
        $quotations = [];
        try {
            $sql = 'SELECT quotation_number, service_request_id, customer_name, customer_mobile, COALESCE(customer_email, "") as customer_email, total_amount, status FROM quotations ORDER BY id DESC';
            $result = $this->connection->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $quotations[] = [
                        'quotation_number' => (string) $row['quotation_number'],
                        'service_request_id' => (string) $row['service_request_id'],
                        'customer_name' => (string) $row['customer_name'],
                        'customer_mobile' => (string) $row['customer_mobile'],
                        'customer_email' => (string) $row['customer_email'],
                        'total_amount' => (float) $row['total_amount'],
                        'status' => (string) $row['status'],
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository getAvailableQuotations error: ' . $e->getMessage());
        }

        return $quotations;
    }

    /**
     * Retrieve all invoices from invoices table for searchable select dropdown.
     *
     * @return array<int, array{invoice_number: string, service_request_id: string, customer_name: string, customer_mobile: string, customer_email: string, total_amount: float, payment_status: string}>
     */
    public function getAvailableInvoices(): array
    {
        $invoices = [];
        try {
            $sql = 'SELECT invoice_number, COALESCE(service_request_id, "") as service_request_id, customer_name, customer_mobile, COALESCE(customer_email, "") as customer_email, total_amount, payment_status FROM invoices ORDER BY id DESC';
            $result = $this->connection->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $invoices[] = [
                        'invoice_number' => (string) $row['invoice_number'],
                        'service_request_id' => (string) $row['service_request_id'],
                        'customer_name' => (string) $row['customer_name'],
                        'customer_mobile' => (string) $row['customer_mobile'],
                        'customer_email' => (string) $row['customer_email'],
                        'total_amount' => (float) $row['total_amount'],
                        'payment_status' => (string) $row['payment_status'],
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository getAvailableInvoices error: ' . $e->getMessage());
        }

        return $invoices;
    }

    /**
     * Generate next service request number (e.g. REQ-2026-003).
     */
    public function generateNextRequestNo(): string
    {
        $year = date('Y');
        $prefix = "REQ-{$year}-";
        try {
            $sql = "SELECT service_request_no FROM service_requests WHERE service_request_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
            $result = $this->connection->query($sql);
            if ($result && $row = $result->fetch_assoc()) {
                $lastNo = (string) $row['service_request_no'];
                $parts = explode('-', $lastNo);
                $lastNum = (int) end($parts);
                return sprintf("REQ-%s-%03d", $year, $lastNum + 1);
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository generateNextRequestNo error: ' . $e->getMessage());
        }

        return sprintf("REQ-%s-001", $year);
    }

    /**
     * Create a new service request.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): bool
    {
        try {
            $sql = 'INSERT INTO service_requests (
                service_request_no, customer_name, request_by_mobile_no, customer_email,
                service_id, service_name, service_category, request_type, description, device_details,
                request_address, request_city, request_state, request_pincode, landmark,
                request_date, preferred_visit_date, preferred_time_slot, site_inspection_required,
                priority, request_status, request_status_notes, assign_to, assigned_employee_id,
                amc_contract_number, request_quotation_no, request_invoice_no
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $reqNo = (string) ($data['service_request_no'] ?? $this->generateNextRequestNo());
            $custName = (string) ($data['customer_name'] ?? '');
            $mobile = (string) ($data['request_by_mobile_no'] ?? '');
            $email = !empty($data['customer_email']) ? (string) $data['customer_email'] : null;
            $serviceId = !empty($data['service_id']) ? (int) $data['service_id'] : null;
            $serviceName = (string) ($data['service_name'] ?? '');
            $category = (string) ($data['service_category'] ?? 'other');
            $requestType = (string) ($data['request_type'] ?? 'repair_service');
            $description = !empty($data['description']) ? (string) $data['description'] : null;
            $deviceDetails = !empty($data['device_details']) ? (string) $data['device_details'] : null;
            $address = (string) ($data['request_address'] ?? '');
            $city = (string) ($data['request_city'] ?? 'Raipur');
            $state = (string) ($data['request_state'] ?? 'Chhattisgarh');
            $pincode = (string) ($data['request_pincode'] ?? '');
            $landmark = !empty($data['landmark']) ? (string) $data['landmark'] : null;
            $reqDate = !empty($data['request_date']) ? (string) $data['request_date'] : date('Y-m-d H:i:s');
            $visitDate = !empty($data['preferred_visit_date']) ? (string) $data['preferred_visit_date'] : null;
            $timeSlot = (string) ($data['preferred_time_slot'] ?? 'anytime');
            $siteInspection = !empty($data['site_inspection_required']) ? 1 : 0;
            $priority = (string) ($data['priority'] ?? 'medium');
            $status = (string) ($data['request_status'] ?? 'pending');
            $statusNotes = !empty($data['request_status_notes']) ? (string) $data['request_status_notes'] : null;
            $empId = !empty($data['assigned_employee_id']) ? (int) $data['assigned_employee_id'] : null;
            $assignTo = !empty($data['assign_to']) ? (string) $data['assign_to'] : null;
            if ($empId !== null && empty($assignTo)) {
                $employees = $this->getActiveEmployees();
                foreach ($employees as $emp) {
                    if ($emp['id'] === $empId) {
                        $assignTo = $emp['emp_name'] . ' (' . $emp['emp_role'] . ')';
                        break;
                    }
                }
            }
            $amcNo = !empty($data['amc_contract_number']) ? (string) $data['amc_contract_number'] : null;
            $quoNo = !empty($data['request_quotation_no']) ? (string) $data['request_quotation_no'] : null;
            $invNo = !empty($data['request_invoice_no']) ? (string) $data['request_invoice_no'] : null;

            $stmt->bind_param(
                'ssssisssssssssssssisssiisss',
                $reqNo, $custName, $mobile, $email,
                $serviceId, $serviceName, $category, $requestType, $description, $deviceDetails,
                $address, $city, $state, $pincode, $landmark,
                $reqDate, $visitDate, $timeSlot, $siteInspection,
                $priority, $status, $statusNotes, $assignTo, $empId,
                $amcNo, $quoNo, $invNo
            );

            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing service request.
     *
     * @param int $id
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        try {
            $completedAt = null;
            if (isset($data['request_status']) && $data['request_status'] === 'completed') {
                $completedAt = date('Y-m-d H:i:s');
            }

            $sql = 'UPDATE service_requests SET
                customer_name = ?, request_by_mobile_no = ?, customer_email = ?,
                service_id = ?, service_name = ?, service_category = ?, request_type = ?,
                description = ?, device_details = ?,
                request_address = ?, request_city = ?, request_state = ?, request_pincode = ?, landmark = ?,
                preferred_visit_date = ?, preferred_time_slot = ?, site_inspection_required = ?,
                priority = ?, request_status = ?, request_status_notes = ?,
                assign_to = ?, assigned_employee_id = ?, amc_contract_number = ?,
                request_quotation_no = ?, request_invoice_no = ?,
                completed_at = COALESCE(?, completed_at)
                WHERE id = ?';

            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $custName = (string) ($data['customer_name'] ?? '');
            $mobile = (string) ($data['request_by_mobile_no'] ?? '');
            $email = !empty($data['customer_email']) ? (string) $data['customer_email'] : null;
            $serviceId = !empty($data['service_id']) ? (int) $data['service_id'] : null;
            $serviceName = (string) ($data['service_name'] ?? '');
            $category = (string) ($data['service_category'] ?? 'other');
            $requestType = (string) ($data['request_type'] ?? 'repair_service');
            $description = !empty($data['description']) ? (string) $data['description'] : null;
            $deviceDetails = !empty($data['device_details']) ? (string) $data['device_details'] : null;
            $address = (string) ($data['request_address'] ?? '');
            $city = (string) ($data['request_city'] ?? 'Raipur');
            $state = (string) ($data['request_state'] ?? 'Chhattisgarh');
            $pincode = (string) ($data['request_pincode'] ?? '');
            $landmark = !empty($data['landmark']) ? (string) $data['landmark'] : null;
            $visitDate = !empty($data['preferred_visit_date']) ? (string) $data['preferred_visit_date'] : null;
            $timeSlot = (string) ($data['preferred_time_slot'] ?? 'anytime');
            $siteInspection = !empty($data['site_inspection_required']) ? 1 : 0;
            $priority = (string) ($data['priority'] ?? 'medium');
            $status = (string) ($data['request_status'] ?? 'pending');
            $statusNotes = !empty($data['request_status_notes']) ? (string) $data['request_status_notes'] : null;
            $empId = !empty($data['assigned_employee_id']) ? (int) $data['assigned_employee_id'] : null;
            $assignTo = !empty($data['assign_to']) ? (string) $data['assign_to'] : null;
            if ($empId !== null && empty($assignTo)) {
                $employees = $this->getActiveEmployees();
                foreach ($employees as $emp) {
                    if ($emp['id'] === $empId) {
                        $assignTo = $emp['emp_name'] . ' (' . $emp['emp_role'] . ')';
                        break;
                    }
                }
            }
            $amcNo = !empty($data['amc_contract_number']) ? (string) $data['amc_contract_number'] : null;
            $quoNo = !empty($data['request_quotation_no']) ? (string) $data['request_quotation_no'] : null;
            $invNo = !empty($data['request_invoice_no']) ? (string) $data['request_invoice_no'] : null;

            $stmt->bind_param(
                'sssissssssssssssississssisi',
                $custName, $mobile, $email,
                $serviceId, $serviceName, $category, $requestType,
                $description, $deviceDetails,
                $address, $city, $state, $pincode, $landmark,
                $visitDate, $timeSlot, $siteInspection,
                $priority, $status, $statusNotes,
                $assignTo, $empId, $amcNo,
                $quoNo, $invNo,
                $completedAt, $id
            );

            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign employee to a service request.
     */
    public function assignEmployee(int $requestId, ?int $employeeId, ?string $employeeName, ?string $statusNotes = null): bool
    {
        try {
            $status = 'assigned';
            $sql = 'UPDATE service_requests SET assigned_employee_id = ?, assign_to = ?, request_status = ?, request_status_notes = COALESCE(?, request_status_notes) WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('isssi', $employeeId, $employeeName, $status, $statusNotes, $requestId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository assignEmployee error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a service request.
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM service_requests WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve Quotation for a Service Request.
     */
    public function approveQuotation(int $id): bool
    {
        try {
            $now = date('Y-m-d H:i:s');
            $sql = "UPDATE service_requests SET is_quotation_approved = 1, quotation_approved_at = ?, request_status = 'quotation_sent' WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('si', $now, $id);
                $res = $stmt->execute();
                $stmt->close();
                return $res;
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository approveQuotation error: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Approve Invoice for a Service Request.
     */
    public function approveInvoice(int $id): bool
    {
        try {
            $now = date('Y-m-d H:i:s');
            $sql = "UPDATE service_requests SET is_invoice_approved = 1, invoice_approved_at = ?, request_status = 'completed' WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('si', $now, $id);
                $res = $stmt->execute();
                $stmt->close();
                return $res;
            }
        } catch (Throwable $e) {
            error_log('ServiceRequestRepository approveInvoice error: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Map database result row to ServiceRequest entity.
     *
     * @param array<string, mixed> $row
     */
    private function mapRowToEntity(array $row): ServiceRequest
    {
        return new ServiceRequest(
            (int) $row['id'],
            (string) $row['service_request_no'],
            (string) $row['customer_name'],
            (string) $row['request_by_mobile_no'],
            isset($row['customer_email']) ? (string) $row['customer_email'] : null,
            isset($row['service_id']) ? (int) $row['service_id'] : null,
            (string) $row['service_name'],
            (string) ($row['service_category'] ?? 'other'),
            (string) ($row['request_type'] ?? 'repair_service'),
            isset($row['description']) ? (string) $row['description'] : null,
            isset($row['device_details']) ? (string) $row['device_details'] : null,
            (string) $row['request_address'],
            (string) ($row['request_city'] ?? 'Raipur'),
            (string) ($row['request_state'] ?? 'Chhattisgarh'),
            (string) $row['request_pincode'],
            isset($row['landmark']) ? (string) $row['landmark'] : null,
            (string) $row['request_date'],
            isset($row['preferred_visit_date']) ? (string) $row['preferred_visit_date'] : null,
            isset($row['preferred_time_slot']) ? (string) $row['preferred_time_slot'] : null,
            (int) ($row['site_inspection_required'] ?? 0),
            (string) ($row['priority'] ?? 'medium'),
            (string) ($row['request_status'] ?? 'pending'),
            isset($row['request_status_notes']) ? (string) $row['request_status_notes'] : null,
            isset($row['assign_to']) ? (string) $row['assign_to'] : null,
            isset($row['assigned_employee_id']) ? (int) $row['assigned_employee_id'] : null,
            isset($row['amc_contract_number']) ? (string) $row['amc_contract_number'] : null,
            isset($row['request_quotation_no']) ? (string) $row['request_quotation_no'] : null,
            (int) ($row['is_quotation_approved'] ?? 0),
            isset($row['quotation_approved_at']) ? (string) $row['quotation_approved_at'] : null,
            isset($row['request_invoice_no']) ? (string) $row['request_invoice_no'] : null,
            (int) ($row['is_invoice_approved'] ?? 0),
            isset($row['invoice_approved_at']) ? (string) $row['invoice_approved_at'] : null,
            isset($row['created_at']) ? (string) $row['created_at'] : null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null,
            isset($row['completed_at']) ? (string) $row['completed_at'] : null
        );
    }
}
