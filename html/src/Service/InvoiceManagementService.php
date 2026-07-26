<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Invoice;
use App\Repository\InvoiceRepository;
use App\Repository\QuotationRepository;

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
     * Get list of all invoices.
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
     * Automatically convert/generate an invoice from the latest version of a quotation!
     * If an invoice already exists for this service_request_id, it updates the existing invoice.
     *
     * @return array{success: bool, message: string, invoice_id?: int}
     */
    public function createInvoiceFromQuotation(int $quotationId): array
    {
        $quotation = $this->quotationRepository->findById($quotationId);
        if ($quotation === null) {
            return ['success' => false, 'message' => 'Quotation not found.'];
        }

        // Get latest quotation version and items
        $versions = $this->quotationRepository->findVersionsByQuotationId($quotationId);
        if (empty($versions)) {
            return ['success' => false, 'message' => 'No quotation version history found.'];
        }

        $latestVersion = end($versions);
        $items = $latestVersion->getItems();

        $itemsData = [];
        foreach ($items as $item) {
            $itemsData[] = [
                'description' => $item->getItemDescription(),
                'quantity' => $item->getQuantity(),
                'unit_price' => $item->getUnitPrice(),
                'total_price' => $item->getTotalPrice(),
            ];
        }

        // Check if invoice already exists for this service_request_id or quotation_id
        $existingInvoice = $this->repository->findByServiceRequestId($quotation->getServiceRequestId());
        if ($existingInvoice === null) {
            $existingInvoice = $this->repository->findByQuotationId($quotationId);
        }

        if ($existingInvoice !== null) {
            // UPDATE existing invoice with latest quotation details
            $updatedInvoice = new Invoice(
                $existingInvoice->getId(),
                $existingInvoice->getInvoiceNumber(),
                $quotationId,
                $quotation->getServiceRequestId(),
                $quotation->getCustomerName(),
                $quotation->getCustomerMobile(),
                $quotation->getCustomerEmail(),
                $quotation->getServiceName(),
                $latestVersion->getSubtotal(),
                $latestVersion->getDiscount(),
                $latestVersion->getTax(),
                $latestVersion->getTotalAmount(),
                $existingInvoice->getPaymentStatus(),
                $existingInvoice->getPaymentMethod(),
                $existingInvoice->getInvoiceDate(),
                $existingInvoice->getDueDate(),
                "Updated with latest final Quotation {$quotation->getQuotationNumber()} (Version {$latestVersion->getVersionNumber()})"
            );

            $this->repository->saveInvoice($updatedInvoice, $itemsData);

            return [
                'success' => true,
                'message' => "Invoice {$existingInvoice->getInvoiceNumber()} updated with latest final quotation details for {$quotation->getServiceRequestId()} (Version {$latestVersion->getVersionNumber()})!",
                'invoice_id' => $existingInvoice->getId(),
            ];
        }

        // Generate unique Invoice number INV-2026-XXXX
        $invNumber = 'INV-' . date('Y') . '-' . str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT);

        $invoice = new Invoice(
            null,
            $invNumber,
            $quotationId,
            $quotation->getServiceRequestId(),
            $quotation->getCustomerName(),
            $quotation->getCustomerMobile(),
            $quotation->getCustomerEmail(),
            $quotation->getServiceName(),
            $latestVersion->getSubtotal(),
            $latestVersion->getDiscount(),
            $latestVersion->getTax(),
            $latestVersion->getTotalAmount(),
            'unpaid',
            'Cash',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+7 days')),
            "Tax Invoice generated from Quotation {$quotation->getQuotationNumber()} (Version {$latestVersion->getVersionNumber()})"
        );

        $invoiceId = $this->repository->saveInvoice($invoice, $itemsData);
        if ($invoiceId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice in database.'];
        }

        return [
            'success' => true,
            'message' => "Invoice {$invNumber} generated successfully from Quotation {$quotation->getQuotationNumber()} (Version {$latestVersion->getVersionNumber()})!",
            'invoice_id' => $invoiceId,
        ];
    }

    /**
     * Save/Update invoice directly.
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

        // If creating a new invoice, check if an invoice already exists for this service_request_id
        if ($id === null) {
            $existingInv = $this->repository->findByServiceRequestId($serviceReqId);
            if ($existingInv !== null) {
                $id = $existingInv->getId();
                $data['invoice_number'] = $existingInv->getInvoiceNumber();
            }
        }

        $invNumber = !empty($data['invoice_number']) ? trim((string)$data['invoice_number']) : ('INV-' . date('Y') . '-' . rand(1000, 9999));
        $qId = !empty($data['quotation_id']) ? (int)$data['quotation_id'] : null;
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

        if (empty($serviceReqId) || empty($customerName) || empty($customerMobile) || empty($serviceName)) {
            return ['success' => false, 'message' => 'Service Request ID, Customer Name, Mobile Number, and Service Name are required.'];
        }

        $invoice = new Invoice(
            $id,
            $invNumber,
            $qId,
            $serviceReqId,
            $customerName,
            $customerMobile,
            $customerEmail !== '' ? $customerEmail : null,
            $serviceName,
            $subtotal,
            $discount,
            $tax,
            $totalAmount,
            $pStatus,
            $pMethod,
            $invDate,
            $dueDate,
            $notes
        );

        $savedId = $this->repository->saveInvoice($invoice, $items);
        if ($savedId <= 0) {
            return ['success' => false, 'message' => 'Failed to save invoice.'];
        }

        return [
            'success' => true,
            'message' => "Invoice {$invNumber} saved successfully!",
            'id' => $savedId,
        ];
    }

    /**
     * Mark an invoice as paid.
     */
    public function markInvoicePaid(int $id, string $method = 'UPI'): array
    {
        if ($this->repository->markPaid($id, $method)) {
            return ['success' => true, 'message' => 'Invoice marked as PAID successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to update invoice payment status.'];
    }

    /**
     * Delete an invoice.
     */
    public function deleteInvoice(int $id): array
    {
        if ($this->repository->delete($id)) {
            return ['success' => true, 'message' => 'Invoice deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete invoice.'];
    }
}
