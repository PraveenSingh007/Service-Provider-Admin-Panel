<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * ServiceRequest Entity Model
 * Represents a record from the service_requests table.
 */
final class ServiceRequest
{
    private ?int $id;
    private string $serviceRequestNo;
    private string $customerName;
    private string $requestByMobileNo;
    private ?string $customerEmail;
    private ?int $serviceId;
    private string $serviceName;
    private string $serviceCategory;
    private string $requestType;
    private ?string $description;
    private ?string $deviceDetails;
    private string $requestAddress;
    private string $requestCity;
    private string $requestState;
    private string $requestPincode;
    private ?string $landmark;
    private string $requestDate;
    private ?string $preferredVisitDate;
    private ?string $preferredTimeSlot;
    private int $siteInspectionRequired;
    private string $priority;
    private string $requestStatus;
    private ?string $requestStatusNotes;
    private ?string $assignTo;
    private ?int $assignedEmployeeId;
    private ?string $amcContractNumber;
    private ?string $requestQuotationNo;
    private int $isQuotationApproved;
    private ?string $quotationApprovedAt;
    private ?string $requestInvoiceNo;
    private int $isInvoiceApproved;
    private ?string $invoiceApprovedAt;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?string $completedAt;

    public function __construct(
        ?int $id,
        string $serviceRequestNo,
        string $customerName,
        string $requestByMobileNo,
        ?string $customerEmail,
        ?int $serviceId,
        string $serviceName,
        string $serviceCategory,
        string $requestType,
        ?string $description,
        ?string $deviceDetails,
        string $requestAddress,
        string $requestCity,
        string $requestState,
        string $requestPincode,
        ?string $landmark,
        string $requestDate,
        ?string $preferredVisitDate,
        ?string $preferredTimeSlot,
        int $siteInspectionRequired,
        string $priority,
        string $requestStatus,
        ?string $requestStatusNotes,
        ?string $assignTo,
        ?int $assignedEmployeeId,
        ?string $amcContractNumber,
        ?string $requestQuotationNo,
        int $isQuotationApproved = 0,
        ?string $quotationApprovedAt = null,
        ?string $requestInvoiceNo = null,
        int $isInvoiceApproved = 0,
        ?string $invoiceApprovedAt = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $completedAt = null
    ) {
        $this->id = $id;
        $this->serviceRequestNo = $serviceRequestNo;
        $this->customerName = $customerName;
        $this->requestByMobileNo = $requestByMobileNo;
        $this->customerEmail = $customerEmail;
        $this->serviceId = $serviceId;
        $this->serviceName = $serviceName;
        $this->serviceCategory = $serviceCategory;
        $this->requestType = $requestType;
        $this->description = $description;
        $this->deviceDetails = $deviceDetails;
        $this->requestAddress = $requestAddress;
        $this->requestCity = $requestCity;
        $this->requestState = $requestState;
        $this->requestPincode = $requestPincode;
        $this->landmark = $landmark;
        $this->requestDate = $requestDate;
        $this->preferredVisitDate = $preferredVisitDate;
        $this->preferredTimeSlot = $preferredTimeSlot;
        $this->siteInspectionRequired = $siteInspectionRequired;
        $this->priority = $priority;
        $this->requestStatus = $requestStatus;
        $this->requestStatusNotes = $requestStatusNotes;
        $this->assignTo = $assignTo;
        $this->assignedEmployeeId = $assignedEmployeeId;
        $this->amcContractNumber = $amcContractNumber;
        $this->requestQuotationNo = $requestQuotationNo;
        $this->isQuotationApproved = $isQuotationApproved;
        $this->quotationApprovedAt = $quotationApprovedAt;
        $this->requestInvoiceNo = $requestInvoiceNo;
        $this->isInvoiceApproved = $isInvoiceApproved;
        $this->invoiceApprovedAt = $invoiceApprovedAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->completedAt = $completedAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getServiceRequestNo(): string { return $this->serviceRequestNo; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getRequestByMobileNo(): string { return $this->requestByMobileNo; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function getServiceId(): ?int { return $this->serviceId; }
    public function getServiceName(): string { return $this->serviceName; }
    public function getServiceCategory(): string { return $this->serviceCategory; }
    public function getRequestType(): string { return $this->requestType; }
    public function getDescription(): ?string { return $this->description; }
    public function getDeviceDetails(): ?string { return $this->deviceDetails; }
    public function getRequestAddress(): string { return $this->requestAddress; }
    public function getRequestCity(): string { return $this->requestCity; }
    public function getRequestState(): string { return $this->requestState; }
    public function getRequestPincode(): string { return $this->requestPincode; }
    public function getLandmark(): ?string { return $this->landmark; }
    public function getRequestDate(): string { return $this->requestDate; }
    public function getPreferredVisitDate(): ?string { return $this->preferredVisitDate; }
    public function getPreferredTimeSlot(): ?string { return $this->preferredTimeSlot; }
    public function getSiteInspectionRequired(): int { return $this->siteInspectionRequired; }
    public function getPriority(): string { return $this->priority; }
    public function getRequestStatus(): string { return $this->requestStatus; }
    public function getRequestStatusNotes(): ?string { return $this->requestStatusNotes; }
    public function getAssignTo(): ?string { return $this->assignTo; }
    public function getAssignedEmployeeId(): ?int { return $this->assignedEmployeeId; }
    public function getAmcContractNumber(): ?string { return $this->amcContractNumber; }
    public function getRequestQuotationNo(): ?string { return $this->requestQuotationNo; }
    public function isQuotationApproved(): bool { return $this->isQuotationApproved === 1; }
    public function getQuotationApprovedAt(): ?string { return $this->quotationApprovedAt; }
    public function getRequestInvoiceNo(): ?string { return $this->requestInvoiceNo; }
    public function isInvoiceApproved(): bool { return $this->isInvoiceApproved === 1; }
    public function getInvoiceApprovedAt(): ?string { return $this->invoiceApprovedAt; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function getCompletedAt(): ?string { return $this->completedAt; }

    /**
     * Export object as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service_request_no' => $this->serviceRequestNo,
            'customer_name' => $this->customerName,
            'request_by_mobile_no' => $this->requestByMobileNo,
            'customer_email' => $this->customerEmail,
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'service_category' => $this->serviceCategory,
            'request_type' => $this->requestType,
            'description' => $this->description,
            'device_details' => $this->deviceDetails,
            'request_address' => $this->requestAddress,
            'request_city' => $this->requestCity,
            'request_state' => $this->requestState,
            'request_pincode' => $this->requestPincode,
            'landmark' => $this->landmark,
            'request_date' => $this->requestDate,
            'preferred_visit_date' => $this->preferredVisitDate,
            'preferred_time_slot' => $this->preferredTimeSlot,
            'site_inspection_required' => $this->siteInspectionRequired,
            'priority' => $this->priority,
            'request_status' => $this->requestStatus,
            'request_status_notes' => $this->requestStatusNotes,
            'assign_to' => $this->assignTo,
            'assigned_employee_id' => $this->assignedEmployeeId,
            'amc_contract_number' => $this->amcContractNumber,
            'request_quotation_no' => $this->requestQuotationNo,
            'is_quotation_approved' => $this->isQuotationApproved,
            'quotation_approved_at' => $this->quotationApprovedAt,
            'request_invoice_no' => $this->requestInvoiceNo,
            'is_invoice_approved' => $this->isInvoiceApproved,
            'invoice_approved_at' => $this->invoiceApprovedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'completed_at' => $this->completedAt,
        ];
    }
}
