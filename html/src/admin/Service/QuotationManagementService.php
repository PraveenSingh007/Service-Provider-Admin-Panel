<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\Quotation;
use App\Admin\Model\QuotationVersion;
use App\Admin\Repository\QuotationRepository;

class QuotationManagementService
{
    private QuotationRepository $repository;

    public function __construct(QuotationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list of all quotations.
     *
     * @return Quotation[]
     */
    public function getAllQuotations(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Get quotation header by ID.
     */
    public function getQuotationById(int $id): ?Quotation
    {
        return $this->repository->findById($id);
    }

    /**
     * Get all version records for a quotation.
     *
     * @return QuotationVersion[]
     */
    public function getQuotationVersions(int $quotationId): array
    {
        return $this->repository->findVersionsByQuotationId($quotationId);
    }

    /**
     * Create a brand new quotation (Version 1).
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     * @return array{success: bool, message: string, id?: int}
     */
    public function createQuotation(array $data, array $items): array
    {
        $serviceReqId = trim((string) ($data['service_request_id'] ?? ''));
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerMobile = trim((string) ($data['customer_mobile'] ?? ''));
        $customerEmail = trim((string) ($data['customer_email'] ?? ''));
        $serviceName = trim((string) ($data['service_name'] ?? ''));
        $subtotal = (float) ($data['subtotal'] ?? 0.0);
        $discount = (float) ($data['discount'] ?? 0.0);
        $tax = (float) ($data['tax'] ?? 0.0);
        $notes = trim((string) ($data['notes'] ?? 'Initial quotation'));
        $creator = trim((string) ($data['created_by'] ?? 'Admin'));

        if (empty($serviceReqId) || empty($customerName) || empty($customerMobile) || empty($serviceName)) {
            return ['success' => false, 'message' => 'Service Request ID, Customer Name, Mobile Number, and Service Name are required.'];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'At least one line item is required to create a quotation.'];
        }

        // Generate Unique Quotation Number (e.g. QUO-2026-XXXX)
        $qNumber = 'QUO-' . date('Y') . '-' . str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT);
        $totalAmount = round(($subtotal - $discount) + $tax, 2);

        $quotation = new Quotation(
            null,
            $qNumber,
            $serviceReqId,
            $customerName,
            $customerMobile,
            $customerEmail !== '' ? $customerEmail : null,
            $serviceName,
            1, // Initial Version 1
            $totalAmount,
            'sent'
        );

        $quotationId = $this->repository->saveQuotation($quotation);
        if ($quotationId <= 0) {
            return ['success' => false, 'message' => 'Failed to save quotation header in database.'];
        }

        // Save Version 1
        $version = new QuotationVersion(
            null,
            $quotationId,
            1,
            $subtotal,
            $discount,
            $tax,
            $totalAmount,
            $notes,
            $creator
        );

        $versionId = $this->repository->saveVersion($version, $items);
        if ($versionId <= 0) {
            return ['success' => false, 'message' => 'Failed to save quotation line items version.'];
        }

        return [
            'success' => true,
            'message' => "Quotation {$qNumber} (Version 1) created successfully!",
            'id' => $quotationId,
        ];
    }

    /**
     * Save an updated quotation revision (Version N+1).
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     * @return array{success: bool, message: string, version_number?: int}
     */
    public function addQuotationRevision(int $quotationId, array $data, array $items): array
    {
        $existing = $this->repository->findById($quotationId);
        if ($existing === null) {
            return ['success' => false, 'message' => 'Quotation not found.'];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'At least one line item is required for the updated quotation.'];
        }

        $newVersionNumber = $existing->getCurrentVersion() + 1;
        $subtotal = (float) ($data['subtotal'] ?? 0.0);
        $discount = (float) ($data['discount'] ?? 0.0);
        $tax = (float) ($data['tax'] ?? 0.0);
        $revisionNotes = trim((string) ($data['revision_notes'] ?? "Updated quotation version {$newVersionNumber}"));
        $creator = trim((string) ($data['created_by'] ?? 'Admin'));
        $totalAmount = round(($subtotal - $discount) + $tax, 2);

        // Update main quotation header with current_version and latest total_amount
        $updatedQuotation = new Quotation(
            $existing->getId(),
            $existing->getQuotationNumber(),
            $existing->getServiceRequestId(),
            !empty($data['customer_name']) ? (string)$data['customer_name'] : $existing->getCustomerName(),
            !empty($data['customer_mobile']) ? (string)$data['customer_mobile'] : $existing->getCustomerMobile(),
            !empty($data['customer_email']) ? (string)$data['customer_email'] : $existing->getCustomerEmail(),
            !empty($data['service_name']) ? (string)$data['service_name'] : $existing->getServiceName(),
            $newVersionNumber,
            $totalAmount,
            'revised'
        );

        $this->repository->saveQuotation($updatedQuotation);

        // Save new Version record
        $version = new QuotationVersion(
            null,
            $quotationId,
            $newVersionNumber,
            $subtotal,
            $discount,
            $tax,
            $totalAmount,
            $revisionNotes,
            $creator
        );

        $versionId = $this->repository->saveVersion($version, $items);
        if ($versionId <= 0) {
            return ['success' => false, 'message' => 'Failed to save updated quotation version.'];
        }

        return [
            'success' => true,
            'message' => "Quotation {$existing->getQuotationNumber()} updated successfully to Version {$newVersionNumber}!",
            'version_number' => $newVersionNumber,
        ];
    }

    /**
     * Delete quotation.
     */
    public function deleteQuotation(int $id): array
    {
        if ($this->repository->delete($id)) {
            return ['success' => true, 'message' => 'Quotation deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete quotation.'];
    }
}
