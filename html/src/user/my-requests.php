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
  <title>Track Service Request - Customer Portal</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
</head>
<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="bx bx-wrench me-2 text-primary"></i>Service Provider Portal</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item me-3"><a class="nav-link" href="index.php">Home</a></li>
          <li class="nav-item me-3"><a class="nav-link" href="book-service.php">Book Service</a></li>
          <li class="nav-item me-3"><a class="nav-link active" href="my-requests.php">Track Request</a></li>
          
          <?php if ($currentUser !== null): ?>
            <li class="nav-item me-2">
              <a href="personal-info.php" class="btn btn-sm btn-outline-light"><i class="bx bx-user me-1"></i><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></a>
            </li>
            <li class="nav-item">
              <a href="logout.php" class="btn btn-sm btn-danger"><i class="bx bx-log-out me-1"></i> Log Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="login.php" class="btn btn-primary btn-sm fw-bold"><i class="bx bx-log-in-circle me-1"></i> Sign In with OTP</a>
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
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-1"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <?php if ($errorMsg !== null): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-1"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
        
        <div class="card shadow-sm p-4 mb-4">
          <div class="card-body">
            <h4 class="card-title fw-bold mb-3"><i class="bx bx-search text-primary me-2"></i>Track Service Request Status</h4>
            <p class="text-muted">Enter your 10-digit mobile number to view request progress, approve quotations & invoices, and complete payments.</p>

            <form method="GET" action="my-requests.php" class="row g-3">
              <div class="col-md-9">
                <input type="text" name="mobile" class="form-control form-control-lg" placeholder="Enter Mobile Number (e.g. 9876543210)" value="<?= htmlspecialchars($mobileQuery, ENT_QUOTES, 'UTF-8') ?>" required />
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold"><i class="bx bx-search me-1"></i> Track Requests</button>
              </div>
            </form>
          </div>
        </div>

        <?php if ($mobileQuery !== ''): ?>
          <div class="card shadow-sm p-4">
            <div class="card-body">
              <h5 class="fw-bold mb-3">Service Requests for Mobile: <span class="text-primary"><?= htmlspecialchars($mobileQuery, ENT_QUOTES, 'UTF-8') ?></span></h5>

              <?php if (count($customerRequests) === 0): ?>
                <div class="alert alert-warning mb-0">No service requests found for mobile number "<?= htmlspecialchars($mobileQuery, ENT_QUOTES, 'UTF-8') ?>".</div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                      <tr>
                        <th>Req No</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Assigned Tech</th>
                        <th>Quotation & Approval</th>
                        <th>Invoice & Approval</th>
                        <th>Payment / QR Code</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($customerRequests as $reqObj): ?>
                        <?php
                        $req = $reqObj->toArray();
                        $reqId = (int) $req['id'];
                        $st = (string) ($req['request_status'] ?? 'pending');
                        
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
                          <td><strong><?= htmlspecialchars((string)$req['service_request_no'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                          <td>
                            <div class="fw-bold"><?= htmlspecialchars((string)$req['service_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted"><?= htmlspecialchars(strtoupper((string)$req['service_category']), ENT_QUOTES, 'UTF-8') ?></small>
                          </td>
                          <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $st)), ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td><?= htmlspecialchars((string)($req['assign_to'] ?: 'Pending Assignment'), ENT_QUOTES, 'UTF-8') ?></td>
                          
                          <!-- COLUMN 1: QUOTATION PDF & APPROVAL -->
                          <td>
                            <?php if ($quoNo !== '' && isset($allQuotations[$quoNo])): ?>
                              <?php $quoObj = $allQuotations[$quoNo]; ?>
                              <div class="mb-2">
                                <a href="../admin/quotation-details.php?id=<?= $quoObj->getId() ?>" target="_blank" class="btn btn-outline-danger btn-sm fw-bold">
                                  <i class="bx bxs-file-pdf me-1"></i> View <?= htmlspecialchars($quoNo, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                              </div>
                              <div class="small fw-semibold text-dark">Amount: ₹<?= number_format((float)$quoObj->getTotalAmount(), 2) ?></div>

                              <?php if ($isQuoApproved): ?>
                                <span class="badge bg-success mt-1"><i class="bx bx-check-double me-1"></i> Quotation Approved</span>
                              <?php else: ?>
                                <form method="POST" action="my-requests.php?mobile=<?= urlencode($mobileQuery) ?>" class="mt-2">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                  <input type="hidden" name="action" value="approve_quotation" />
                                  <input type="hidden" name="request_id" value="<?= $reqId ?>" />
                                  <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="bx bx-check me-1"></i> Approve Quotation</button>
                                </form>
                              <?php endif; ?>

                            <?php else: ?>
                              <span class="text-muted small">Pending Admin Quotation</span>
                            <?php endif; ?>
                          </td>

                          <!-- COLUMN 2: INVOICE PDF & APPROVAL -->
                          <td>
                            <?php if (!$isQuoApproved): ?>
                              <span class="badge bg-label-warning text-dark"><i class="bx bx-lock-alt me-1"></i> Approve Quotation First</span>
                            <?php elseif ($invNo !== '' && isset($allInvoices[$invNo])): ?>
                              <?php $invObj = $allInvoices[$invNo]; ?>
                              <div class="mb-2">
                                <a href="../admin/invoice-details.php?id=<?= $invObj->getId() ?>" target="_blank" class="btn btn-outline-danger btn-sm fw-bold">
                                  <i class="bx bxs-file-pdf me-1"></i> View <?= htmlspecialchars($invNo, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                              </div>
                              <div class="small fw-semibold text-dark">Amount: ₹<?= number_format((float)$invObj->getTotalAmount(), 2) ?></div>

                              <?php if ($isInvApproved): ?>
                                <span class="badge bg-success mt-1"><i class="bx bx-check-double me-1"></i> Invoice Approved</span>
                              <?php else: ?>
                                <form method="POST" action="my-requests.php?mobile=<?= urlencode($mobileQuery) ?>" class="mt-2">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                  <input type="hidden" name="action" value="approve_invoice" />
                                  <input type="hidden" name="request_id" value="<?= $reqId ?>" />
                                  <button type="submit" class="btn btn-primary btn-sm fw-bold"><i class="bx bx-check-circle me-1"></i> Approve Invoice</button>
                                </form>
                              <?php endif; ?>

                            <?php else: ?>
                              <span class="text-muted small">Pending Admin Invoice</span>
                            <?php endif; ?>
                          </td>

                          <!-- COLUMN 3: COMPANY QR CODE FOR PAYMENT -->
                          <td>
                            <?php if ($isInvApproved): ?>
                              <?php
                              $invTotal = isset($allInvoices[$invNo]) ? (float)$allInvoices[$invNo]->getTotalAmount() : 0.00;
                              $upiString = 'upi://pay?pa=' . urlencode('serviceprovider@upi') . '&pn=' . urlencode('Service Provider Portal') . '&am=' . $invTotal . '&cu=INR';
                              $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($upiString);
                              ?>
                              <button type="button" class="btn btn-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#qrModal<?= $reqId ?>">
                                <i class="bx bx-qr me-1"></i> Pay Now (QR Code)
                              </button>

                              <!-- QR Modal -->
                              <div class="modal fade" id="qrModal<?= $reqId ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                  <div class="modal-content">
                                    <div class="modal-header bg-dark text-white">
                                      <h5 class="modal-title text-white fw-bold"><i class="bx bx-qr-scan me-2 text-warning"></i>Company Payment QR Code</h5>
                                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center p-4">
                                      <h6 class="fw-bold mb-2">Scan & Pay for Service Request #<?= htmlspecialchars((string)$req['service_request_no'], ENT_QUOTES, 'UTF-8') ?></h6>
                                      <div class="display-6 fw-bold text-success mb-3">Total: ₹<?= number_format($invTotal, 2) ?></div>
                                      
                                      <div class="p-3 bg-white d-inline-block border rounded-3 shadow-sm mb-3">
                                        <img src="<?= $qrUrl ?>" alt="Company Payment QR Code" width="180" height="180" class="img-fluid" />
                                      </div>

                                      <div class="bg-light p-3 rounded-3 text-start small">
                                        <div class="mb-1"><strong>Company:</strong> <?= htmlspecialchars($companyProfile ? $companyProfile->getCompanyName() : 'Service Provider Company', ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="mb-1"><strong>UPI ID:</strong> <span class="text-primary fw-bold">serviceprovider@upi</span></div>
                                        <div class="mb-1"><strong>Bank Account:</strong> 987654321098</div>
                                        <div class="mb-1"><strong>IFSC Code:</strong> SBIN0001234</div>
                                        <div><strong>Bank Name:</strong> State Bank of India</div>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                  </div>
                                </div>
                              </div>

                            <?php else: ?>
                              <span class="badge bg-label-secondary text-muted"><i class="bx bx-lock-alt me-1"></i> Locked until Invoice Approved</span>
                            <?php endif; ?>
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
