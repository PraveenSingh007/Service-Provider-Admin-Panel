<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Service\InvoiceManagementService;

class InvoiceController
{
    private InvoiceManagementService $service;

    public function __construct(InvoiceManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle POST actions for invoices.
     *
     * @param array<string, mixed> $postData
     * @return array{success: bool, message: string, invoice_id?: int}
     */
    public function handleRequest(array $postData): array
    {
        $action = (string) ($postData['action'] ?? '');

        if ($action === 'create_from_quotation') {
            $quotationId = (int) ($postData['quotation_id'] ?? 0);
            $quotationVersion = !empty($postData['quotation_version']) ? (int) $postData['quotation_version'] : null;
            return $this->service->createInvoiceFromQuotation($quotationId, $quotationVersion);
        }

        if ($action === 'save') {
            $items = (array) ($postData['items'] ?? []);
            return $this->service->saveInvoice($postData, $items);
        }

        if ($action === 'add_revision') {
            $invoiceId = (int) ($postData['invoice_id'] ?? 0);
            $items = (array) ($postData['items'] ?? []);
            return $this->service->addInvoiceRevision($invoiceId, $postData, $items);
        }

        if ($action === 'mark_paid') {
            $invoiceId = (int) ($postData['id'] ?? 0);
            $method = (string) ($postData['payment_method'] ?? 'UPI');
            $versionNumber = !empty($postData['version_number']) ? (int) $postData['version_number'] : 0;
            
            if ($versionNumber > 0) {
                return $this->service->markVersionPaid($invoiceId, $versionNumber, $method);
            }
            return $this->service->markInvoicePaid($invoiceId, $method);
        }

        if ($action === 'delete_version') {
            $invoiceId = (int) ($postData['invoice_id'] ?? 0);
            $versionNumber = (int) ($postData['version_number'] ?? 0);
            return $this->service->deleteInvoiceVersion($invoiceId, $versionNumber);
        }

        if ($action === 'delete') {
            $invoiceId = (int) ($postData['id'] ?? 0);
            return $this->service->deleteInvoice($invoiceId);
        }

        return ['success' => false, 'message' => 'Invalid invoice action.'];
    }
}
