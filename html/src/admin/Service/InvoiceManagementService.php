<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\Invoice;
use App\Admin\Model\InvoiceVersion;
use App\Admin\Repository\InvoiceRepository;
use App\Admin\Repository\QuotationRepository;

class InvoiceManagementService
{
    private InvoiceRepository $repository;
    private QuotationRepository $quotationRepository;

    public function __construct(InvoiceRepository $repository, QuotationRepository $quotationRepository)
    {
        $this->repository = $repository;
        $this->quotationRepository = $quotationRepository;
    }

    /**
     * Get list of all invoices (with versions loaded).
     *
     * @return Invoice[]
     */
    public function getAllInvoices(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Get invoice by primary key ID.
     */
    public function getInvoiceById(int $id): ?Invoice
    {
        return $this->repository->findById($id);
    }

    /**
     * Get existing invoice linked to a quotation.
     */
    public function getInvoiceByQuotationId(int $quotationId): ?Invoice
    {
        return $this->repository->findByQuotationId($quotationId);
    }

    /**
     * Get all version records for an invoice.
     *
     * @return InvoiceVersion[]
     */
    public function getInvoiceVersions(int $invoiceId): array
    {
        return $this->repository->findVersionsByInvoiceId($invoiceId);
    }

    /**
     * Automatically convert/generate an invoice from a specific quotation version.
     * Creates a new invoice with Version 1, or adds a new revision if invoice already exists.
     *
     * @return array{success: bool, message: string, invoice_id?: int}
     */
    public function createInvoiceFromQuotation(int $quotationId, ?int $quotationVersionNum = null): array
    {
        $quotation = $this->quotationRepository->findById($quotationId);
        if ($quotation === null) {
            return ['success' => false, 'message' => 'Quotation not found.'];
        }

        // Get quotation versions
        $versions = $this->quotationRepository->findVersionsByQuotationId($quotationId);
        if (empty($versions)) {
            return ['success' => false, 'message' => 'No quotation version history found.'];
        }

        // Select the specified version or the latest
        $selectedVersion = null;
        if ($quotationVersionNum !== null) {
            foreach ($versions as $v) {
                if ($v->getVersionNumber() === $quotationVersionNum) {
                    $selectedVersion = $v;
                    break;
                }
            }
        }
        if ($selectedVersion === null) {
            $selectedVersion = end($versions);
        }

        $items = $selectedVersion->getItems();
        $itemsData = [];
        foreach ($items as $item) {
            $itemsData[] = [
                'description' => $item->getItemDescription(),
                'quantity' => $item->getQuantity(),
                'unit_price' => $item->getUnitPrice(),
                'total_price' => $item->getTotalPrice(),
            ];
        }

        // Check if invoice already exists for this quotation
        $existingInvoice = $this->repository->findByQuotationId($quotationId);

        if ($existingInvoice !== null) {
            // Add a new revision to the existing invoice
            $newVersionNumber = $existingInvoice->getCurrentVersion() + 1;

            $version = new InvoiceVersion(
                null,
                $existingInvoice->getId(),
                $newVersionNumber,
                $selectedVersion->getSubtotal(),
                $selectedVersion->getDiscount(),
                $selectedVersion->getTax(),
                $selectedVersion->getTotalAmount(),
                'unpaid',
                'Cash',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+7 days')),
                "Invoice revision from Quotation {$quotation->getQuotationNumber()} (Version {$selectedVersion->getVersionNumber()})",
                'Admin',
                null,
                null,
                [],
                $selectedVersion->getVersionNumber()
            );

            $versionId = $this->repository->saveVersion($version, $itemsData);
            if ($versionId <= 0) {
                return ['success' => false, 'message' => 'Failed to save invoice version.'];
            }

            // Update invoice header
            $updatedHeader = new Invoice(
                $existingInvoice->getId(),
                $existingInvoice->getInvoiceNumber(),
                $quotationId,
                $quotation->getServiceRequestId(),
                $quotation->getCustomerName(),
                $quotation->getCustomerMobile(),
                $quotation->getCustomerEmail(),
                $quotation->getServiceName(),
                $newVersionNumber,
                'revised'
            );
            $this->repository->saveInvoiceHeader($updatedHeader);

            return [
                'success' => true,
                'message' => "Invoice {$existingInvoice->getInvoiceNumber()} updated with revision Version {$newVersionNumber} from Quotation (Version {$selectedVersion->getVersionNumber()})!",
                'invoice_id' => $existingInvoice->getId(),
            ];
        }

        // Create brand new invoice with Version 1
        $invNumber = $this->repository->findNextInvoiceNumber();

        $invoiceHeader = new Invoice(
            null,
            $invNumber,
            $quotationId,
            $quotation->getServiceRequestId(),
            $quotation->getCustomerName(),
            $quotation->getCustomerMobile(),
            $quotation->getCustomerEmail(),
            $quotation->getServiceName(),
            1,
            'active'
        );

        $invoiceId = $this->repository->saveInvoiceHeader($invoiceHeader);
        if ($invoiceId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice header.'];
        }

        $version = new InvoiceVersion(
            null,
            $invoiceId,
            1,
            $selectedVersion->getSubtotal(),
            $selectedVersion->getDiscount(),
            $selectedVersion->getTax(),
            $selectedVersion->getTotalAmount(),
            'unpaid',
            'Cash',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+7 days')),
            "Tax Invoice generated from Quotation {$quotation->getQuotationNumber()} (Version {$selectedVersion->getVersionNumber()})",
            'Admin',
            null,
            null,
            [],
            $selectedVersion->getVersionNumber()
        );

        $versionId = $this->repository->saveVersion($version, $itemsData);
        if ($versionId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice version.'];
        }

        return [
            'success' => true,
            'message' => "Invoice {$invNumber} generated successfully from Quotation {$quotation->getQuotationNumber()} (Version {$selectedVersion->getVersionNumber()})!",
            'invoice_id' => $invoiceId,
        ];
    }

    /**
     * Save/Create a new invoice directly (not from quotation) with Version 1.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     * @return array{success: bool, message: string, id?: int}
     */
    public function saveInvoice(array $data, array $items): array
    {
        $id = isset($data['id']) && ((int)$data['id'] > 0) ? (int) $data['id'] : null;
        $serviceReqId = trim((string) ($data['service_request_id'] ?? ''));

        if (empty($serviceReqId)) {
            return ['success' => false, 'message' => 'Service Request ID is required.'];
        }

        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerMobile = trim((string) ($data['customer_mobile'] ?? ''));
        $customerEmail = trim((string) ($data['customer_email'] ?? ''));
        $serviceName = trim((string) ($data['service_name'] ?? ''));
        $subtotal = (float) ($data['subtotal'] ?? 0.0);
        $discount = (float) ($data['discount'] ?? 0.0);
        $tax = (float) ($data['tax'] ?? 0.0);
        $pStatus = trim((string) ($data['payment_status'] ?? 'unpaid'));
        $pMethod = trim((string) ($data['payment_method'] ?? 'Cash'));
        $invDate = trim((string) ($data['invoice_date'] ?? date('Y-m-d')));
        $dueDate = trim((string) ($data['due_date'] ?? date('Y-m-d', strtotime('+7 days'))));
        $notes = trim((string) ($data['notes'] ?? ''));
        $totalAmount = round(($subtotal - $discount) + $tax, 2);
        $qId = !empty($data['quotation_id']) ? (int)$data['quotation_id'] : null;
        $qVer = !empty($data['quotation_version']) ? (int)$data['quotation_version'] : null;

        if ($id === null && (empty($qId) || $qId <= 0)) {
            return ['success' => false, 'message' => 'A valid Quotation Number is required to create an invoice. Please search and select a quotation.'];
        }

        if (empty($serviceReqId) || empty($customerName) || empty($customerMobile) || empty($serviceName)) {
            return ['success' => false, 'message' => 'Service Request ID, Customer Name, Mobile Number, and Service Name are required.'];
        }

        if ($id !== null) {
            // Editing existing invoice — update the latest version
            $existing = $this->repository->findById($id);
            if ($existing === null) {
                return ['success' => false, 'message' => 'Invoice not found.'];
            }

            // Update header
            $updatedHeader = new Invoice(
                $id,
                $existing->getInvoiceNumber(),
                $qId ?? $existing->getQuotationId(),
                $serviceReqId,
                $customerName,
                $customerMobile,
                $customerEmail !== '' ? $customerEmail : null,
                $serviceName,
                $existing->getCurrentVersion(),
                $existing->getStatus()
            );
            $this->repository->saveInvoiceHeader($updatedHeader);

            // Delete old items and version for the current version, then re-save
            $this->repository->deleteVersion($id, $existing->getCurrentVersion());

            $version = new InvoiceVersion(
                null,
                $id,
                $existing->getCurrentVersion(),
                $subtotal,
                $discount,
                $tax,
                $totalAmount,
                $pStatus,
                $pMethod,
                $invDate,
                $dueDate,
                $notes,
                'Admin',
                null,
                null,
                [],
                $qVer
            );

            $versionId = $this->repository->saveVersion($version, $items);
            if ($versionId <= 0) {
                return ['success' => false, 'message' => 'Failed to save invoice version.'];
            }

            return [
                'success' => true,
                'message' => "Invoice {$existing->getInvoiceNumber()} updated successfully!",
                'id' => $id,
            ];
        }

        // Creating a new invoice
        $invNumber = !empty($data['invoice_number']) ? trim((string)$data['invoice_number']) : $this->repository->findNextInvoiceNumber();

        $invoiceHeader = new Invoice(
            null,
            $invNumber,
            $qId,
            $serviceReqId,
            $customerName,
            $customerMobile,
            $customerEmail !== '' ? $customerEmail : null,
            $serviceName,
            1,
            'active'
        );

        $invoiceId = $this->repository->saveInvoiceHeader($invoiceHeader);
        if ($invoiceId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice header.'];
        }

        $version = new InvoiceVersion(
            null,
            $invoiceId,
            1,
            $subtotal,
            $discount,
            $tax,
            $totalAmount,
            $pStatus,
            $pMethod,
            $invDate,
            $dueDate,
            $notes,
            'Admin',
            null,
            null,
            [],
            $qVer
        );

        $versionId = $this->repository->saveVersion($version, $items);
        if ($versionId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice version.'];
        }

        return [
            'success' => true,
            'message' => "Invoice {$invNumber} created successfully!",
            'id' => $invoiceId,
        ];
    }

    /**
     * Add a new revision to an existing invoice (Version N+1).
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     * @return array{success: bool, message: string, version_number?: int}
     */
    public function addInvoiceRevision(int $invoiceId, array $data, array $items): array
    {
        $existing = $this->repository->findById($invoiceId);
        if ($existing === null) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'At least one line item is required for the revised invoice.'];
        }

        $newVersionNumber = $existing->getCurrentVersion() + 1;
        $subtotal = (float) ($data['subtotal'] ?? 0.0);
        $discount = (float) ($data['discount'] ?? 0.0);
        $tax = (float) ($data['tax'] ?? 0.0);
        $totalAmount = round(($subtotal - $discount) + $tax, 2);
        $pStatus = trim((string) ($data['payment_status'] ?? 'unpaid'));
        $pMethod = trim((string) ($data['payment_method'] ?? 'Cash'));
        $invDate = trim((string) ($data['invoice_date'] ?? date('Y-m-d')));
        $dueDate = trim((string) ($data['due_date'] ?? date('Y-m-d', strtotime('+7 days'))));
        $notes = trim((string) ($data['revision_notes'] ?? "Invoice revised to Version {$newVersionNumber}"));
        $creator = trim((string) ($data['created_by'] ?? 'Admin'));
        $qVer = !empty($data['quotation_version']) ? (int)$data['quotation_version'] : null;

        // Update header customer info if provided
        $updatedHeader = new Invoice(
            $existing->getId(),
            $existing->getInvoiceNumber(),
            $existing->getQuotationId(),
            $existing->getServiceRequestId(),
            !empty($data['customer_name']) ? (string)$data['customer_name'] : $existing->getCustomerName(),
            !empty($data['customer_mobile']) ? (string)$data['customer_mobile'] : $existing->getCustomerMobile(),
            !empty($data['customer_email']) ? (string)$data['customer_email'] : $existing->getCustomerEmail(),
            !empty($data['service_name']) ? (string)$data['service_name'] : $existing->getServiceName(),
            $newVersionNumber,
            'revised'
        );

        $this->repository->saveInvoiceHeader($updatedHeader);

        // Save new Version record
        $version = new InvoiceVersion(
            null,
            $invoiceId,
            $newVersionNumber,
            $subtotal,
            $discount,
            $tax,
            $totalAmount,
            $pStatus,
            $pMethod,
            $invDate,
            $dueDate,
            $notes,
            $creator,
            null,
            null,
            [],
            $qVer
        );

        $versionId = $this->repository->saveVersion($version, $items);
        if ($versionId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice revision.'];
        }

        return [
            'success' => true,
            'message' => "Invoice {$existing->getInvoiceNumber()} revised to Version {$newVersionNumber}!",
            'version_number' => $newVersionNumber,
        ];
    }

    /**
     * Mark a specific invoice version as paid.
     */
    public function markVersionPaid(int $invoiceId, int $versionNumber, string $method = 'UPI'): array
    {
        $invoice = $this->repository->findById($invoiceId);
        if ($invoice === null) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        $version = $invoice->getVersion($versionNumber);
        if ($version === null) {
            return ['success' => false, 'message' => "Version {$versionNumber} not found."];
        }

        if ($this->repository->markVersionPaid($version->getId(), $method)) {
            return ['success' => true, 'message' => "Invoice Version {$versionNumber} marked as PAID ({$method})."];
        }
        return ['success' => false, 'message' => 'Failed to update payment status.'];
    }

    /**
     * Mark an invoice as paid (marks latest version as paid for backward compat).
     */
    public function markInvoicePaid(int $id, string $method = 'UPI'): array
    {
        $invoice = $this->repository->findById($id);
        if ($invoice === null) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        $latest = $invoice->getLatestVersion();
        if ($latest === null) {
            return ['success' => false, 'message' => 'No invoice version found.'];
        }

        if ($this->repository->markVersionPaid($latest->getId(), $method)) {
            return ['success' => true, 'message' => 'Invoice marked as PAID successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to update invoice payment status.'];
    }

    /**
     * Delete a specific version of an invoice.
     */
    public function deleteInvoiceVersion(int $invoiceId, int $versionNumber): array
    {
        $success = $this->repository->deleteVersion($invoiceId, $versionNumber);
        if ($success) {
            $remaining = $this->repository->findVersionsByInvoiceId($invoiceId);
            return [
                'success' => true,
                'message' => "Invoice Version {$versionNumber} deleted successfully!",
                'invoice_deleted' => empty($remaining),
            ];
        }

        return ['success' => false, 'message' => 'Failed to delete invoice version.'];
    }

    /**
     * Delete an entire invoice (all versions).
     */
    public function deleteInvoice(int $id): array
    {
        if ($this->repository->delete($id)) {
            return ['success' => true, 'message' => 'Invoice deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete invoice.'];
    }
}
