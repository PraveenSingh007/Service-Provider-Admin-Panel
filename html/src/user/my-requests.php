<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/dbConnection.php';
require_once __DIR__ . '/../admin/Model/ServiceRequest.php';
require_once __DIR__ . '/../admin/Repository/ServiceRequestRepository.php';
require_once __DIR__ . '/../admin/Model/Quotation.php';
require_once __DIR__ . '/../admin/Repository/QuotationRepository.php';
require_once __DIR__ . '/../admin/Model/Invoice.php';
require_once __DIR__ . '/../admin/Model/InvoiceItem.php';
require_once __DIR__ . '/../admin/Repository/InvoiceRepository.php';
require_once __DIR__ . '/../admin/Model/Company.php';
require_once __DIR__ . '/../admin/Repository/CompanyRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\CompanyRepository;
use App\Admin\Repository\InvoiceRepository;
use App\Admin\Repository\QuotationRepository;
use App\Admin\Repository\ServiceRequestRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = !empty($_SESSION['customer_user']) ? (array) $_SESSION['customer_user'] : null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$serviceReqRepo = new ServiceRequestRepository($dbConn);
$quotationRepo = new QuotationRepository($dbConn);
$invoiceRepo = new InvoiceRepository($dbConn);
$companyRepo = new CompanyRepository($dbConn);

$companyProfile = $companyRepo->getCompany();

$successMsg = null;
$errorMsg = null;

// Handle Quotation & Invoice Approvals
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!empty($submittedToken) && hash_equals($csrfToken, $submittedToken)) {
        $action = (string) $_POST['action'];
        $reqId = (int) ($_POST['request_id'] ?? 0);

        if ($action === 'approve_quotation' && $reqId > 0) {
            $approved = $serviceReqRepo->approveQuotation($reqId);
            if ($approved) {
                $successMsg = 'Quotation approved successfully!';
            } else {
                $errorMsg = 'Failed to approve quotation. Please try again.';
            }
        } elseif ($action === 'approve_invoice' && $reqId > 0) {
            $approved = $serviceReqRepo->approveInvoice($reqId);
            if ($approved) {
                $successMsg = 'Invoice approved successfully! Payment QR Code & details are now unlocked below.';
            } else {
                $errorMsg = 'Failed to approve invoice. Please try again.';
            }
        }
    } else {
        $errorMsg = 'Security validation failed. Please refresh the page.';
    }
}

$mobileQuery = trim((string) ($_GET['mobile'] ?? ($currentUser['mobile_no'] ?? '')));
$customerRequests = [];

if ($mobileQuery !== '') {
    $allRequests = $serviceReqRepo->findAll();
    foreach ($allRequests as $req) {
        if (trim($req->getRequestByMobileNo()) === $mobileQuery) {
            $customerRequests[] = $req;
        }
    }
}

// Map Quotations and Invoices indexed by number
$allQuotations = [];
foreach ($quotationRepo->findAll() as $q) {
    $allQuotations[$q->getQuotationNumber()] = $q;
}

$allInvoices = [];
foreach ($invoiceRepo->findAll() as $inv) {
    $allInvoices[$inv->getInvoiceNumber()] = $inv;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Service Requests | Customer Portal</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  
  <style>
    body { background-color: #f5f5f9; font-family: 'Public Sans', sans-serif; }
    .max-w-500 { max-width: 500px; }
  </style>
</head>
<body class="bg-light">

  <!-- Navigation Header -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4 d-flex align-items-center me-3" href="index.php">
        <img src="../../../assets/img/logo.png" alt="tech-xpert" style="height: 38px; width: auto; object-fit: contain; border-radius: 6px; background: #fff; padding: 2px;" class="me-2" />
        <span>tech-</span><span style="color: #696cff;">xpert</span>
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item me-3"><a class="nav-link" href="index.php">Home</a></li>
          <li class="nav-item me-3"><a class="nav-link" href="book-service.php">Book Service</a></li>
          <li class="nav-item me-3"><a class="nav-link active" href="my-requests.php">Track Request</a></li>
          <li class="nav-item me-3">
            <a href="index.php#requestCallbackModal" class="btn btn-warning btn-sm fw-bold text-dark rounded-pill px-3 shadow-sm" onclick="window.location.href='index.php?callback=1'; return false;">
              <i class="bx bx-phone-call me-1"></i> Request Call Back
            </a>
          </li>
          
          <?php if ($currentUser !== null): ?>
            <li class="nav-item me-2">
              <a href="personal-info.php" class="btn btn-sm btn-outline-light"><i class="bx bx-user me-1"></i><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></a>
            </li>
            <li class="nav-item">
              <a href="logout.php" class="btn btn-sm btn-danger"><i class="bx bx-log-out me-1"></i> Log Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="login.php" class="btn btn-primary btn-sm fw-bold"><i class="bx bx-log-in-circle me-1"></i> Sign In</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-4">
    <div class="row justify-content-center">
      <div class="col-lg-12">

        <?php if ($successMsg !== null): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bx bx-check-circle me-1 fs-5 align-middle"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($errorMsg !== null): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bx bx-error-circle me-1 fs-5 align-middle"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($mobileQuery === ''): ?>
          <!-- Prompt sign-in if no mobile number is active -->
          <div class="card shadow-sm p-4 text-center my-4">
            <div class="card-body py-5">
              <i class="bx bx-user-pin text-primary display-3 mb-3"></i>
              <h3 class="fw-bold mb-2">View Your Service Requests</h3>
              <p class="text-muted mb-4 max-w-500 mx-auto">Please sign in to view all your booked requests, track live technician assignment, approve quotations, and make online payments.</p>
              <a href="login.php" class="btn btn-primary btn-lg fw-bold px-4 rounded-pill shadow-sm">
                <i class="bx bx-log-in-circle me-1"></i> Sign In to View Requests
              </a>
            </div>
          </div>
        <?php else: ?>
          <!-- Service Requests Clean Table Container -->
          <div class="card shadow-sm p-4">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold mb-0">
                  <i class="bx bx-list-check me-2 text-primary"></i>Service Requests for Mobile: <span class="text-primary"><?= htmlspecialchars($mobileQuery, ENT_QUOTES, 'UTF-8') ?></span>
                </h5>
                <a href="book-service.php" class="btn btn-sm btn-primary fw-bold rounded-pill">
                  <i class="bx bx-plus me-1"></i> Book New Service
                </a>
              </div>

              <?php if (count($customerRequests) === 0): ?>
                <div class="alert alert-warning mb-0 p-4 text-center">
                  <i class="bx bx-info-circle fs-3 mb-2 d-block text-warning"></i>
                  No service requests found for mobile number "<strong><?= htmlspecialchars($mobileQuery, ENT_QUOTES, 'UTF-8') ?></strong>".
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                      <tr>
                        <th>Req No</th>
                        <th>Request Date</th>
                        <th>Completion Date</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($customerRequests as $reqObj): ?>
                        <?php
                        $req = $reqObj->toArray();
                        $reqId = (int) $req['id'];
                        $st = (string) ($req['request_status'] ?? 'pending');
                        
                        $reqDateRaw = (string)($req['request_date'] ?? $req['created_at'] ?? '');
                        $reqDateFormatted = $reqDateRaw !== '' ? date('d M Y, h:i A', strtotime($reqDateRaw)) : 'N/A';

                        $compDateRaw = (string)($req['completed_at'] ?? '');
                        $compDateFormatted = ($compDateRaw !== '' && $st === 'completed') ? date('d M Y, h:i A', strtotime($compDateRaw)) : null;

                        $badge = 'bg-warning';
                        if ($st === 'assigned') { $badge = 'bg-info'; }
                        elseif ($st === 'in_progress') { $badge = 'bg-primary'; }
                        elseif ($st === 'quotation_sent') { $badge = 'bg-secondary'; }
                        elseif ($st === 'completed') { $badge = 'bg-success'; }

                        $quoNo = (string) ($req['request_quotation_no'] ?? '');
                        $invNo = (string) ($req['request_invoice_no'] ?? '');
                        $isQuoApproved = !empty($req['is_quotation_approved']);
                        $isInvApproved = !empty($req['is_invoice_approved']);
                        ?>
                        <tr>
                          <!-- Req No -->
                          <td><strong><?= htmlspecialchars((string)$req['service_request_no'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                          
                          <!-- Request Date -->
                          <td>
                            <div class="fw-semibold text-dark fs-7">
                              <i class="bx bx-calendar me-1 text-primary"></i><?= htmlspecialchars($reqDateFormatted, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                          </td>

                          <!-- Completion Date -->
                          <td>
                            <?php if ($compDateFormatted !== null): ?>
                              <div class="fw-semibold text-success fs-7">
                                <i class="bx bx-check-circle me-1"></i><?= htmlspecialchars($compDateFormatted, ENT_QUOTES, 'UTF-8') ?>
                              </div>
                            <?php else: ?>
                              <span class="badge bg-label-warning text-dark"><i class="bx bx-time me-1"></i> In Progress</span>
                            <?php endif; ?>
                          </td>

                          <!-- Service -->
                          <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars((string)$req['service_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted"><?= htmlspecialchars(strtoupper((string)$req['service_category']), ENT_QUOTES, 'UTF-8') ?></small>
                          </td>

                          <!-- Status -->
                          <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $st)), ENT_QUOTES, 'UTF-8') ?></span></td>

                          <!-- Action: Details Button -->
                          <td class="text-center">
                            <button type="button" class="btn btn-primary btn-sm fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestDetailModal<?= $reqId ?>">
                              <i class="bx bx-info-circle me-1"></i> Details
                            </button>

                            <!-- Comprehensive Details Modal -->
                            <div class="modal fade text-start" id="requestDetailModal<?= $reqId ?>" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content rounded-4 border-0 shadow-lg">
                                  <div class="modal-header bg-dark text-white p-4">
                                    <div>
                                      <h5 class="modal-title fw-bold text-white mb-1">
                                        <i class="bx bx-receipt text-primary me-2"></i>Request Details: #<?= htmlspecialchars((string)$req['service_request_no'], ENT_QUOTES, 'UTF-8') ?>
                                      </h5>
                                      <span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $st)), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>

                                  <div class="modal-body p-4">
                                    
                                    <!-- Section 1: Overview & Service Info -->
                                    <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                                      <div class="row g-3">
                                        <div class="col-md-6">
                                          <span class="text-muted fs-7 d-block">Service Name</span>
                                          <strong class="text-dark fs-6"><?= htmlspecialchars((string)$req['service_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="col-md-6">
                                          <span class="text-muted fs-7 d-block">Category & Type</span>
                                          <span class="badge bg-label-primary"><?= htmlspecialchars(strtoupper((string)$req['service_category']), ENT_QUOTES, 'UTF-8') ?></span>
                                          <span class="badge bg-label-secondary"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', (string)$req['request_type'])), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="col-md-6">
                                          <span class="text-muted fs-7 d-block">Request Date</span>
                                          <strong class="text-dark"><i class="bx bx-calendar me-1 text-primary"></i><?= htmlspecialchars($reqDateFormatted, ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="col-md-6">
                                          <span class="text-muted fs-7 d-block">Completion Date</span>
                                          <?php if ($compDateFormatted !== null): ?>
                                            <strong class="text-success"><i class="bx bx-check-circle me-1"></i><?= htmlspecialchars($compDateFormatted, ENT_QUOTES, 'UTF-8') ?></strong>
                                          <?php else: ?>
                                            <span class="badge bg-label-warning text-dark"><i class="bx bx-time me-1"></i> In Progress</span>
                                          <?php endif; ?>
                                        </div>
                                      </div>
                                    </div>

                                    <!-- Section 2: Technician & Visit Location -->
                                    <div class="row g-3 mb-4">
                                      <div class="col-md-6">
                                        <div class="border rounded-3 p-3 h-100 bg-white">
                                          <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-user-check text-primary me-2"></i>Assigned Technician & Schedule</h6>
                                          <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars((string)($req['assign_to'] ?: 'Pending Assignment'), ENT_QUOTES, 'UTF-8') ?></div>
                                          <div class="text-muted fs-7 mt-2"><i class="bx bx-calendar-event me-1 text-primary"></i><strong>Visit Date:</strong> <?= !empty($req['preferred_visit_date']) ? htmlspecialchars((string)$req['preferred_visit_date'], ENT_QUOTES, 'UTF-8') : 'Flexible / TBD' ?></div>
                                          <div class="text-muted fs-7 mt-1"><i class="bx bx-time-five me-1 text-primary"></i><strong>Time Slot:</strong> <?= htmlspecialchars(ucfirst((string)($req['preferred_time_slot'] ?? 'anytime')), ENT_QUOTES, 'UTF-8') ?></div>
                                          <div class="mt-2">
                                            <?php if (!empty($req['site_inspection_required'])): ?>
                                              <span class="badge bg-warning text-dark"><i class="bx bx-wrench me-1"></i>Site Inspection Required</span>
                                            <?php else: ?>
                                              <span class="badge bg-light text-muted">No Site Inspection</span>
                                            <?php endif; ?>
                                          </div>
                                        </div>
                                      </div>
                                      <div class="col-md-6">
                                        <div class="border rounded-3 p-3 h-100 bg-white">
                                          <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-map-pin text-primary me-2"></i>Service Address</h6>
                                          <div class="fs-7 text-dark fw-semibold"><?= htmlspecialchars((string)$req['request_address'], ENT_QUOTES, 'UTF-8') ?></div>
                                          <?php if (!empty($req['landmark'])): ?>
                                            <div class="fs-7 text-dark"><i class="bx bx-building-house me-1 text-muted"></i><strong>Landmark:</strong> <?= htmlspecialchars((string)$req['landmark'], ENT_QUOTES, 'UTF-8') ?></div>
                                          <?php endif; ?>
                                          <div class="fs-7 text-muted mt-1"><?= htmlspecialchars((string)$req['request_city'] . ', ' . (string)$req['request_state'] . ' - ' . (string)$req['request_pincode'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                      </div>
                                    </div>

                                    <?php if (!empty($req['description']) || !empty($req['device_details'])): ?>
                                      <div class="border rounded-3 p-3 mb-4 bg-white">
                                        <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-detail text-primary me-2"></i>Description & Device Details</h6>
                                        <?php if (!empty($req['description'])): ?>
                                          <p class="fs-7 mb-1 text-dark"><strong>Notes:</strong> <?= htmlspecialchars((string)$req['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($req['device_details'])): ?>
                                          <p class="fs-7 mb-0 text-muted"><strong>Device Info:</strong> <?= htmlspecialchars((string)$req['device_details'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                      </div>
                                    <?php endif; ?>

                                    <!-- Section 3: Quotation & Invoice Approvals -->
                                    <div class="row g-3 mb-4">
                                      <!-- Quotation Box -->
                                      <div class="col-md-6">
                                        <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                          <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-file text-primary me-2"></i>Quotation Status</h6>
                                          <?php if ($quoNo !== '' && isset($allQuotations[$quoNo])): ?>
                                            <?php $quoObj = $allQuotations[$quoNo]; ?>
                                            <div class="mb-3">
                                              <a href="../admin/quotation-details.php?id=<?= $quoObj->getId() ?>&print=1" target="_blank" class="d-inline-flex align-items-center gap-2 p-2 px-3 border border-danger border-opacity-25 rounded-3 bg-danger-subtle text-danger text-decoration-none fw-semibold shadow-xs">
                                                <i class="bx bxs-file-pdf fs-4"></i>
                                                <span><?= htmlspecialchars($quoNo, ENT_QUOTES, 'UTF-8') ?>.pdf</span>
                                                <i class="bx bx-export fs-6 ms-1"></i>
                                              </a>
                                            </div>
                                            <div class="fw-bold text-dark mb-2">Total Amount: ₹<?= number_format((float)$quoObj->getTotalAmount(), 2) ?></div>
                                            <?php if ($isQuoApproved): ?>
                                              <span class="badge bg-success"><i class="bx bx-check-double me-1"></i> Quotation Approved</span>
                                            <?php else: ?>
                                              <form method="POST" action="my-requests.php?mobile=<?= urlencode($mobileQuery) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="action" value="approve_quotation" />
                                                <input type="hidden" name="request_id" value="<?= $reqId ?>" />
                                                <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="bx bx-check me-1"></i> Approve Quotation</button>
                                              </form>
                                            <?php endif; ?>
                                          <?php else: ?>
                                            <span class="text-muted fs-7">Pending Admin Quotation</span>
                                          <?php endif; ?>
                                        </div>
                                      </div>

                                      <!-- Invoice Box -->
                                      <div class="col-md-6">
                                        <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                          <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-receipt text-primary me-2"></i>Invoice Status</h6>
                                          <?php if (!$isQuoApproved): ?>
                                            <span class="badge bg-label-warning text-dark"><i class="bx bx-lock-alt me-1"></i> Approve Quotation First</span>
                                          <?php elseif ($invNo !== '' && isset($allInvoices[$invNo])): ?>
                                            <?php $invObj = $allInvoices[$invNo]; ?>
                                            <div class="mb-3">
                                              <a href="../admin/invoice-details.php?id=<?= $invObj->getId() ?>&print=1" target="_blank" class="d-inline-flex align-items-center gap-2 p-2 px-3 border border-danger border-opacity-25 rounded-3 bg-danger-subtle text-danger text-decoration-none fw-semibold shadow-xs">
                                                <i class="bx bxs-file-pdf fs-4"></i>
                                                <span><?= htmlspecialchars($invNo, ENT_QUOTES, 'UTF-8') ?>.pdf</span>
                                                <i class="bx bx-export fs-6 ms-1"></i>
                                              </a>
                                            </div>
                                            <div class="fw-bold text-dark mb-2">Total Amount: ₹<?= number_format((float)$invObj->getTotalAmount(), 2) ?></div>
                                            <?php if ($isInvApproved): ?>
                                              <span class="badge bg-success"><i class="bx bx-check-double me-1"></i> Invoice Approved</span>
                                            <?php else: ?>
                                              <form method="POST" action="my-requests.php?mobile=<?= urlencode($mobileQuery) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="action" value="approve_invoice" />
                                                <input type="hidden" name="request_id" value="<?= $reqId ?>" />
                                                <button type="submit" class="btn btn-primary btn-sm fw-bold"><i class="bx bx-check-circle me-1"></i> Approve Invoice</button>
                                              </form>
                                            <?php endif; ?>
                                          <?php else: ?>
                                            <span class="text-muted fs-7">Pending Admin Invoice</span>
                                          <?php endif; ?>
                                        </div>
                                      </div>
                                    </div>

                                    <!-- Section 4: Payment QR Code -->
                                    <?php if ($isInvApproved): ?>
                                      <?php
                                      $invTotal = isset($allInvoices[$invNo]) ? (float)$allInvoices[$invNo]->getTotalAmount() : 0.00;
                                      $upiString = 'upi://pay?pa=' . urlencode('techxpert@upi') . '&pn=' . urlencode('tech-xpert Portal') . '&am=' . $invTotal . '&cu=INR';
                                      $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($upiString);
                                      ?>
                                      <div class="border border-warning rounded-3 p-4 bg-label-warning text-center">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bx bx-qr-scan text-warning me-2"></i>Company Payment QR Code</h6>
                                        <p class="fs-7 text-muted mb-3">Scan with any UPI App (GPay, PhonePe, Paytm, BHIM) to complete payment.</p>
                                        
                                        <div class="p-3 bg-white d-inline-block border rounded-3 shadow-sm mb-3">
                                          <img src="<?= $qrUrl ?>" alt="Company Payment QR Code" width="180" height="180" class="img-fluid" />
                                        </div>
                                        
                                        <div class="display-6 fw-bold text-success mb-3">Amount: ₹<?= number_format($invTotal, 2) ?></div>

                                        <div class="bg-white p-3 rounded-3 text-start small border mx-auto max-w-500">
                                          <div class="mb-1"><strong>Company:</strong> <?= htmlspecialchars($companyProfile ? $companyProfile->getCompanyName() : 'tech-xpert Services', ENT_QUOTES, 'UTF-8') ?></div>
                                          <div class="mb-1"><strong>UPI ID:</strong> <span class="text-primary fw-bold">techxpert@upi</span></div>
                                          <div class="mb-1"><strong>Bank Account:</strong> 987654321098</div>
                                          <div class="mb-1"><strong>IFSC Code:</strong> SBIN0001234</div>
                                          <div><strong>Bank Name:</strong> State Bank of India</div>
                                        </div>
                                      </div>
                                    <?php endif; ?>

                                  </div>

                                  <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Close</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>

                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.js"></script>
</body>
</html>
