<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$srNo = trim((string) ($_GET['sr'] ?? ''));
if (empty($srNo)) {
    echo json_encode(['success' => false, 'message' => 'Service Request No is required.']);
    exit;
}

try {
    $dbConn = \App\Admin\Database\DatabaseConnection::createFromEnv()->getConnection();
    $stmt = $dbConn->prepare("SELECT service_request_no, customer_name, request_by_mobile_no, customer_email, service_name FROM service_requests WHERE service_request_no = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception($dbConn->error);
    }

    $stmt->bind_param('s', $srNo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'service_request' => [
                'service_request_no' => (string)$row['service_request_no'],
                'customer_name' => (string)($row['customer_name'] ?? ''),
                'customer_mobile' => (string)($row['request_by_mobile_no'] ?? ''),
                'customer_email' => (string)($row['customer_email'] ?? ''),
                'service_name' => (string)($row['service_name'] ?? ''),
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => "Service Request '{$srNo}' not found."]);
    }
    $stmt->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
