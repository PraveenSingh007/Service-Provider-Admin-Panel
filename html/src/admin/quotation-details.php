<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Quotation.php';
require_once __DIR__ . '/Model/QuotationVersion.php';
require_once __DIR__ . '/Model/QuotationItem.php';
require_once __DIR__ . '/Repository/QuotationRepository.php';
require_once __DIR__ . '/Model/Company.php';
require_once __DIR__ . '/Repository/CompanyRepository.php';
require_once __DIR__ . '/Service/QuotationManagementService.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\CompanyRepository;
use App\Admin\Repository\QuotationRepository;
use App\Admin\Service\QuotationManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) && empty($_SESSION['customer_user'])) {
    header('Location: ../user/login.php');
    exit;
}

$quotationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$repository = new QuotationRepository($dbConn);
$service = new QuotationManagementService($repository);
$companyRepo = new CompanyRepository($dbConn);
$company = $companyRepo->getCompany();

$quotation = $service->getQuotationById($quotationId);
if ($quotation === null) {
    die('Quotation not found.');
}

$versions = $service->getQuotationVersions($quotationId);
$selectedVersionNum = isset($_GET['version']) ? (int) $_GET['version'] : $quotation->getCurrentVersion();

$activeVersion = null;
foreach ($versions as $v) {
    if ($v->getVersionNumber() === $selectedVersionNum) {
        $activeVersion = $v;
        break;
    }
}
if ($activeVersion === null && !empty($versions)) {
    $activeVersion = end($versions);
}

$activeVersionNum = $activeVersion !== null ? $activeVersion->getVersionNumber() : $quotation->getCurrentVersion();
$existingVersionInvoice = null;

$checkVerInvSql = "SELECT id, invoice_number FROM invoices WHERE quotation_id = ? LIMIT 1";
$checkVerStmt = $dbConn->prepare($checkVerInvSql);
if ($checkVerStmt) {
    $qId = $quotation->getId();
    $checkVerStmt->bind_param('i', $qId);
    $checkVerStmt->execute();
    $resVerInv = $checkVerStmt->get_result();
    if ($resVerInv && $invRow = $resVerInv->fetch_assoc()) {
        $existingVersionInvoice = $invRow;
    }
    $checkVerStmt->close();
}

$actionError = null;
$actionMessage = isset($_GET['msg']) ? (string)$_GET['msg'] : null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
        $actionError = 'CSRF validation failed.';
    } elseif ($_POST['action'] === 'delete_version') {
        $verNumToDelete = (int) ($_POST['version_number'] ?? 0);
        $deleteInvoiceToo = !empty($_POST['delete_invoice']);
        
        if ($existingVersionInvoice !== null && !$deleteInvoiceToo) {
            $actionError = "Quotation Deletion Locked! Tax Invoice {$existingVersionInvoice['invoice_number']} has already been created for Version {$verNumToDelete}. Delete Invoice {$existingVersionInvoice['invoice_number']} first to unlock quotation deletion.";
        } elseif ($verNumToDelete > 0) {
            $delRes = $service->deleteQuotationVersion($quotationId, $verNumToDelete, $deleteInvoiceToo);
            if ($delRes['success']) {
                if ($delRes['quotation_deleted']) {
                    header('Location: quotations.php?msg=' . urlencode('Quotation deleted successfully because all versions were removed.'));
                    exit;
                } else {
                    header("Location: quotation-details.php?id={$quotationId}&msg=" . urlencode("Version {$verNumToDelete} deleted successfully!"));
                    exit;
                }
            } else {
                $actionError = $delRes['message'];
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quotation History - <?= htmlspecialchars($quotation->getQuotationNumber(), ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
    <style>
      body {
        font-family: 'Public Sans', sans-serif;
        background-color: #f4f5fa;
        margin: 0;
        padding: 30px;
      }
      .quotation-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        padding: 35px;
        max-width: 900px;
        margin: 0 auto;
      }
      .version-tab-btn {
        border-radius: 20px;
        padding: 6px 18px;
        font-weight: 600;
      }
      @media print {
        body { background: #fff; padding: 0; }
        .no-print { display: none !important; }
        .quotation-card { box-shadow: none; border: none; padding: 0; }
      }
    </style>
  </head>
  <body>
    <div class="container no-print mb-4 d-flex justify-content-between align-items-center" style="max-width: 900px;">
      <?php if (!empty($_SESSION['customer_user'])): ?>
        <a href="../user/my-requests.php" class="btn btn-outline-secondary">
          <i class="icon-base bx bx-arrow-back me-1"></i> Back
        </a>
      <?php else: ?>
        <a href="quotations.php" class="btn btn-outline-secondary">
          <i class="icon-base bx bx-arrow-back me-1"></i> Back to Quotations
        </a>
      <?php endif; ?>
      <div class="d-flex gap-2">
        <?php if (!empty($_SESSION['user'])): ?>
          <?php if ($existingVersionInvoice === null): ?>
            <a href="generate-invoice.php?quotation_id=<?= $quotation->getId() ?>&version=<?= $activeVersionNum ?>" class="btn btn-success">
              <i class="icon-base bx bx-file me-1"></i> Create Invoice (v<?= $activeVersionNum ?>)
            </a>
          <?php else: ?>
            <a href="invoice-details.php?id=<?= (int)$existingVersionInvoice['id'] ?>" class="btn btn-outline-success">
              <i class="icon-base bx bx-check-circle me-1"></i> View Invoice (<?= htmlspecialchars((string)$existingVersionInvoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?>)
            </a>
          <?php endif; ?>
          <a href="add-quotation.php?id=<?= $quotation->getId() ?>" class="btn btn-warning">
            <i class="icon-base bx bx-plus-circle me-1"></i> Add Revision (v<?= $quotation->getCurrentVersion() + 1 ?>)
          </a>
          <?php if ($existingVersionInvoice !== null): ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#deleteVersionModal" title="Deletion locked because invoice exists">
              <i class="icon-base bx bx-lock-alt me-1"></i> Delete v<?= $activeVersionNum ?> (Locked)
            </button>
          <?php else: ?>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteVersionModal">
              <i class="icon-base bx bx-trash me-1"></i> Delete v<?= $activeVersionNum ?>
            </button>
          <?php endif; ?>
        <?php endif; ?>
        <button onclick="window.print();" class="btn btn-primary">
          <i class="icon-base bx bx-printer me-1"></i> Print
        </button>
      </div>
    </div>

    <?php if ($actionMessage): ?>
      <div class="container no-print mb-3" style="max-width: 900px;">
        <div class="alert alert-success alert-dismissible" role="alert">
          <?= htmlspecialchars($actionMessage, ENT_QUOTES, 'UTF-8') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($actionError): ?>
      <div class="container no-print mb-3" style="max-width: 900px;">
        <div class="alert alert-danger alert-dismissible" role="alert">
          <?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Version Selection Toolbar -->
    <div class="container no-print mb-4" style="max-width: 900px;">
      <div class="card p-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-bold me-2"><i class="icon-base bx bx-history me-1"></i> Revision History:</span>
          <?php foreach ($versions as $ver): ?>
            <?php $isAct = ($activeVersion !== null && $ver->getVersionNumber() === $activeVersion->getVersionNumber()); ?>
            <a href="quotation-details.php?id=<?= $quotation->getId() ?>&version=<?= $ver->getVersionNumber() ?>"
               class="btn btn-sm <?= $isAct ? 'btn-primary' : 'btn-outline-secondary' ?> version-tab-btn">
              Version <?= $ver->getVersionNumber() ?>
              <?php if ($ver->getVersionNumber() === $quotation->getCurrentVersion()): ?>
                <span class="badge bg-white text-primary ms-1">Latest</span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Printable Official Quotation Document -->
    <div class="quotation-card">
      <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
          <div class="d-flex align-items-center gap-3 mb-2">
            <img src="../../../assets/img/logo.png" alt="Tech-xpert Logo" style="height: 65px; width: auto; object-fit: contain;" />
            <div>
              <h2 class="fw-bold mb-0" style="color: #1a9df4;"><?= htmlspecialchars($company !== null ? $company->getCompanyName() : 'Tech-xpert Services Pvt Ltd', ENT_QUOTES, 'UTF-8') ?></h2>
              <p class="text-muted mb-0 small">Suraksha, Seva, Santusstti</p>
            </div>
          </div>
          <?php if ($company !== null): ?>
            <div class="text-muted mt-2" style="font-size: 12px;">
              <?php if (!empty($company->getRegistrationNo())): ?><strong>Reg No:</strong> <?= htmlspecialchars($company->getRegistrationNo(), ENT_QUOTES, 'UTF-8') ?> | <?php endif; ?>
              <?php if (!empty($company->getGstNo())): ?><strong>GSTIN:</strong> <?= htmlspecialchars($company->getGstNo(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            </div>
            <div class="text-muted" style="font-size: 12px;">
              <?php if (!empty($company->getPhone())): ?><strong>Ph:</strong> <?= htmlspecialchars($company->getPhone(), ENT_QUOTES, 'UTF-8') ?> | <?php endif; ?>
              <?php if (!empty($company->getFax())): ?><strong>Fax:</strong> <?= htmlspecialchars($company->getFax(), ENT_QUOTES, 'UTF-8') ?> | <?php endif; ?>
              <?php if (!empty($company->getEmail())): ?><strong>Email:</strong> <?= htmlspecialchars($company->getEmail(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="text-end">
          <h3 class="fw-bold text-dark mb-1">QUOTATION</h3>
          <span class="badge bg-label-primary fs-6"><?= htmlspecialchars($quotation->getQuotationNumber(), ENT_QUOTES, 'UTF-8') ?></span>
          <div class="mt-1 text-muted">
            <!-- <strong>Version:</strong> <?= $activeVersion !== null ? $activeVersion->getVersionNumber() : 1 ?> of <?= $quotation->getCurrentVersion() ?> -->
          </div>
        </div>
      </div>

      <!-- Customer & Request Info -->
      <div class="row mb-4">
        <div class="col-6">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Service Request Info</h6>
          <div><strong>Request ID:</strong> <span class="badge bg-label-info"><?= htmlspecialchars($quotation->getServiceRequestId(), ENT_QUOTES, 'UTF-8') ?></span></div>
          <div><strong>Date Created:</strong> <?= htmlspecialchars($activeVersion !== null ? (string)$activeVersion->getCreatedAt() : (string)$quotation->getCreatedAt(), ENT_QUOTES, 'UTF-8') ?></div>
          <div><strong>Service Name:</strong> <?= htmlspecialchars($quotation->getServiceName(), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="col-6 text-end">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Customer Details</h6>
          <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($quotation->getCustomerName(), ENT_QUOTES, 'UTF-8') ?></div>
          <div>📱 <?= htmlspecialchars($quotation->getCustomerMobile(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php if (!empty($quotation->getCustomerEmail())): ?>
            <div>✉️ <?= htmlspecialchars($quotation->getCustomerEmail(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($activeVersion !== null && !empty($activeVersion->getRevisionNotes())): ?>
        <div class="alert alert-warning mb-4">
          <strong><i class="icon-base bx bx-info-circle me-1"></i> Remarks for Version <?= $activeVersion->getVersionNumber() ?>:</strong>
          <?= htmlspecialchars($activeVersion->getRevisionNotes(), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <!-- Particulars Table -->
      <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 35%;">Particulars</th>
              <th style="width: 10%;" class="text-center">Qty</th>
              <th style="width: 15%;" class="text-end">Unit Price (₹)</th>
              <th style="width: 12%;" class="text-center">Disc (%)</th>
              <th style="width: 12%;" class="text-center">GST (%)</th>
              <th style="width: 16%;" class="text-end">Total Amount (₹)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($activeVersion !== null && !empty($activeVersion->getItems())): ?>
              <?php foreach ($activeVersion->getItems() as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item->getItemDescription(), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-center"><?= $item->getQuantity() ?></td>
                  <td class="text-end">₹<?= number_format($item->getUnitPrice(), 2) ?></td>
                  <td class="text-center"><?= number_format($item->getDiscountPercent(), 2) ?>%</td>
                  <td class="text-center"><?= number_format($item->getGstPercent(), 2) ?>%</td>
                  <td class="text-end fw-semibold">₹<?= number_format($item->getTotalPrice(), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center text-muted">No items recorded for this version.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Financial Totals -->
      <div class="row justify-content-end">
        <div class="col-md-5">
          <table class="table table-borderless">
            <tr>
              <td>Subtotal (Base Amount):</td>
              <td class="text-end fw-semibold">₹<?= number_format($activeVersion !== null ? $activeVersion->getSubtotal() : 0, 2) ?></td>
            </tr>
            <?php if ($activeVersion !== null && $activeVersion->getDiscount() > 0): ?>
              <tr>
                <td>Total Discount:</td>
                <td class="text-end text-danger">- ₹<?= number_format($activeVersion->getDiscount(), 2) ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td>GST Tax Amount:</td>
              <td class="text-end fw-semibold">₹<?= number_format($activeVersion !== null ? $activeVersion->getTax() : 0, 2) ?></td>
            </tr>
            <tr class="border-top border-2">
              <td class="fs-5 fw-bold text-primary">Grand Total:</td>
              <td class="text-end fs-5 fw-bold text-primary">₹<?= number_format($activeVersion !== null ? $activeVersion->getTotalAmount() : 0, 2) ?></td>
            </tr>
          </table>
        </div>
      </div>

      <div class="border-top pt-4 mt-4 text-muted" style="font-size: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div></div>
          <div class="fw-semibold" style="color: #1a9df4;">Thank you for choosing <?= htmlspecialchars($company !== null ? $company->getCompanyName() : 'Tech-xpert Services', ENT_QUOTES, 'UTF-8') ?>!</div>
        </div>
        <div class="text-center text-secondary border-top pt-2 mt-2">
          <strong>Office Address:</strong> <?= htmlspecialchars($company !== null && !empty($company->getAddress()) ? $company->getAddress() : '123 Business Tower, Tech Park Road, Mumbai, Maharashtra 400001', ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
      </div>
    </div>

    <!-- Delete Version Modal -->
    <div class="modal fade no-print" id="deleteVersionModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title text-white fw-bold"><i class="icon-base bx bx-trash me-2"></i>Delete Quotation Version <?= $activeVersionNum ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="quotation-details.php?id=<?= $quotation->getId() ?>&version=<?= $activeVersionNum ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="delete_version" />
            <input type="hidden" name="version_number" value="<?= $activeVersionNum ?>" />

            <div class="modal-body p-4">
              <p class="text-dark fw-semibold mb-3">Are you sure you want to delete <strong>Version <?= $activeVersionNum ?></strong> of Quotation <strong><?= htmlspecialchars($quotation->getQuotationNumber(), ENT_QUOTES, 'UTF-8') ?></strong>?</p>

              <?php if ($existingVersionInvoice !== null): ?>
                <div class="alert alert-warning mb-3">
                  <i class="icon-base bx bx-error me-1"></i> Invoice <strong><?= htmlspecialchars((string)$existingVersionInvoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?></strong> is linked to this version!
                </div>
              <?php endif; ?>

              <div class="form-check bg-light p-3 rounded border">
                <input class="form-check-input" type="checkbox" name="delete_invoice" value="1" id="deleteInvoiceCheck" <?= $existingVersionInvoice !== null ? 'checked' : '' ?> />
                <label class="form-check-label fw-bold text-danger ms-1" for="deleteInvoiceCheck">
                  Also delete associated invoice <?= $existingVersionInvoice !== null ? '(' . htmlspecialchars((string)$existingVersionInvoice['invoice_number'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                </label>
                <div class="form-text ms-4">If checked, the tax invoice created for this version will also be permanently deleted.</div>
              </div>
            </div>

            <div class="modal-footer bg-light px-4 py-3">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger fw-bold"><i class="icon-base bx bx-trash me-1"></i> Delete Version & Invoice</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- JS Scripts -->
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
    <script>
      $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1' || urlParams.get('pdf') === '1') {
          setTimeout(function() {
            window.print();
          }, 300);
        }
      });
    </script>
  </body>
</html>
