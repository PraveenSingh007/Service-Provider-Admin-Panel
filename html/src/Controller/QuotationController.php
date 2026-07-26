<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\QuotationManagementService;

class QuotationController
{
    private QuotationManagementService $service;

    public function __construct(QuotationManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle POST actions for quotation creation, revision, and deletion.
     *
     * @param array<string, mixed> $postData
     * @return array{success: bool, message: string}
     */
    public function handleRequest(array $postData): array
    {
        $action = (string) ($postData['action'] ?? '');

        if ($action === 'create') {
            $items = (array) ($postData['items'] ?? []);
            return $this->service->createQuotation($postData, $items);
        }

        if ($action === 'revise') {
            $quotationId = (int) ($postData['quotation_id'] ?? 0);
            $items = (array) ($postData['items'] ?? []);
            return $this->service->addQuotationRevision($quotationId, $postData, $items);
        }

        if ($action === 'delete') {
            $quotationId = (int) ($postData['id'] ?? 0);
            return $this->service->deleteQuotation($quotationId);
        }

        return ['success' => false, 'message' => 'Invalid action requested.'];
    }
}
