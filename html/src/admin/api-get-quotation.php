<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Quotation.php';
require_once __DIR__ . '/Model/QuotationVersion.php';
require_once __DIR__ . '/Model/QuotationItem.php';
require_once __DIR__ . '/Repository/QuotationRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\QuotationRepository;

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$qNum = trim((string) ($_GET['q'] ?? ''));
if (empty($qNum)) {
    echo json_encode(['success' => false, 'message' => 'Quotation number is required.']);
    exit;
}

try {
    $dbConn = DatabaseConnection::createFromEnv()->getConnection();
    $quoRepo = new QuotationRepository($dbConn);

    $quotation = $quoRepo->findByQuotationNumber($qNum);

    // If not found by exact string, try looking up by ID if integer passed
    if ($quotation === null && is_numeric($qNum)) {
        $quotation = $quoRepo->findById((int) $qNum);
    }

    if ($quotation === null) {
        echo json_encode(['success' => false, 'message' => "Quotation '{$qNum}' not found."]);
        exit;
    }

    $versions = $quoRepo->findVersionsByQuotationId($quotation->getId());
    $versionNum = isset($_GET['version']) ? (int) $_GET['version'] : null;

    $selectedVersion = null;
    if ($versionNum !== null) {
        foreach ($versions as $v) {
            if ($v->getVersionNumber() === $versionNum) {
                $selectedVersion = $v;
                break;
            }
        }
    }

    if ($selectedVersion === null && !empty($versions)) {
        $selectedVersion = end($versions);
    }

    if ($selectedVersion === null) {
        echo json_encode(['success' => false, 'message' => "No version details found for Quotation '{$qNum}'."]);
        exit;
    }

    $itemsData = [];
    foreach ($selectedVersion->getItems() as $item) {
        $itemsData[] = [
            'description' => $item->getItemDescription(),
            'quantity' => $item->getQuantity(),
            'unit_price' => $item->getUnitPrice(),
            'total_price' => $item->getTotalPrice(),
        ];
    }

    $allVersionsList = [];
    foreach ($versions as $v) {
        $allVersionsList[] = [
            'version_number' => $v->getVersionNumber(),
            'total_amount' => $v->getTotalAmount(),
            'created_at' => $v->getCreatedAt(),
        ];
    }

    echo json_encode([
        'success' => true,
        'quotation' => [
            'id' => $quotation->getId(),
            'quotation_number' => $quotation->getQuotationNumber(),
            'service_request_id' => $quotation->getServiceRequestId(),
            'customer_name' => $quotation->getCustomerName(),
            'customer_mobile' => $quotation->getCustomerMobile(),
            'customer_email' => $quotation->getCustomerEmail() ?? '',
            'service_name' => $quotation->getServiceName(),
            'current_version' => $quotation->getCurrentVersion(),
            'status' => $quotation->getStatus(),
        ],
        'version' => [
            'version_number' => $selectedVersion->getVersionNumber(),
            'subtotal' => $selectedVersion->getSubtotal(),
            'discount' => $selectedVersion->getDiscount(),
            'tax' => $selectedVersion->getTax(),
            'total_amount' => $selectedVersion->getTotalAmount(),
            'items' => $itemsData,
        ],
        'available_versions' => $allVersionsList,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
