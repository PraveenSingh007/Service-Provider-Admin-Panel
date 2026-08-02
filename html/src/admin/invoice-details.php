<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Invoice.php';
require_once __DIR__ . '/Model/InvoiceItem.php';
require_once __DIR__ . '/Model/InvoiceVersion.php';
require_once __DIR__ . '/Model/Quotation.php';
require_once __DIR__ . '/Model/QuotationVersion.php';
require_once __DIR__ . '/Model/QuotationItem.php';
require_once __DIR__ . '/Model/Company.php';
require_once __DIR__ . '/Repository/InvoiceRepository.php';
require_once __DIR__ . '/Repository/QuotationRepository.php';
require_once __DIR__ . '/Repository/CompanyRepository.php';
require_once __DIR__ . '/Service/InvoiceManagementService.php';
require_once __DIR__ . '/Controller/InvoiceController.php';

use App\Admin\Controller\InvoiceController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\CompanyRepository;
use App\Admin\Repository\InvoiceRepository;
use App\Admin\Repository\QuotationRepository;
use App\Admin\Service\InvoiceManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) && empty($_SESSION['customer_user'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$invoiceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$invRepo = new InvoiceRepository($dbConn);
$quoRepo = new QuotationRepository($dbConn);
$companyRepo = new CompanyRepository($dbConn);
$service = new InvoiceManagementService($invRepo, $quoRepo);
$controller = new InvoiceController($service);

$invoice = $service->getInvoiceById($invoiceId);
if ($invoice === null) {
    die('Invoice not found.');
}
$company = $companyRepo->getCompany();

// Version selection
$versions = $invoice->getVersions();
$selectedVersionNum = isset($_GET['version']) ? (int) $_GET['version'] : $invoice->getCurrentVersion();

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

$activeVersionNum = $activeVersion !== null ? $activeVersion->getVersionNumber() : $invoice->getCurrentVersion();

// Linked quotation info
$linkedQuotationNumber = null;
if ($invoice->getQuotationId() !== null) {
    $linkedQ = $quoRepo->findById($invoice->getQuotationId());
    if ($linkedQ !== null) {
        $linkedQuotationNumber = $linkedQ->getQuotationNumber();
    }
}

// Handle POST actions
$actionError = null;
$actionMessage = isset($_GET['msg']) ? (string)$_GET['msg'] : null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed.';
    } elseif ($_POST['action'] === 'delete_version') {
        $verNumToDelete = (int) ($_POST['version_number'] ?? 0);
        if ($verNumToDelete > 0) {
            $delRes = $service->deleteInvoiceVersion($invoiceId, $verNumToDelete);
            if ($delRes['success']) {
                if (!empty($delRes['invoice_deleted'])) {
                    header('Location: invoices.php?msg=' . urlencode('Invoice deleted because all versions were removed.'));
                    exit;
                } else {
                    header("Location: invoice-details.php?id={$invoiceId}&msg=" . urlencode("Version {$verNumToDelete} deleted successfully!"));
                    exit;
                }
            } else {
                $actionError = $delRes['message'];
            }
        }
    } elseif ($_POST['action'] === 'mark_paid') {
        $verNumToPay = (int) ($_POST['version_number'] ?? 0);
        $payMethod = (string) ($_POST['payment_method'] ?? 'UPI');
        if ($verNumToPay > 0) {
            $payRes = $service->markVersionPaid($invoiceId, $verNumToPay, $payMethod);
            if ($payRes['success']) {
                header("Location: invoice-details.php?id={$invoiceId}&version={$verNumToPay}&msg=" . urlencode($payRes['message']));
                exit;
            } else {
                $actionError = $payRes['message'];
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
    <title>Tax Invoice - <?= htmlspecialchars($invoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></title>
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
      .invoice-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        padding: 40px;
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
        .invoice-card { box-shadow: none; border: none; padding: 0; }
      }
    </style>
  </head>
  <body>
    <div class="container no-print mb-4 d-flex justify-content-between align-items-center" style="max-width: 900px;">
      <a href="invoices.php" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Back to Invoices
      </a>
      <div class="d-flex gap-2">
        <?php if ($activeVersion !== null && $activeVersion->getPaymentStatus() !== 'paid'): ?>
          <form method="POST" action="invoice-details.php?id=<?= (int)$invoice->getId() ?>&version=<?= $activeVersionNum ?>" onsubmit="return confirm('Mark Version <?= $activeVersionNum ?> as PAID?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="mark_paid" />
            <input type="hidden" name="version_number" value="<?= $activeVersionNum ?>" />
            <input type="hidden" name="payment_method" value="UPI" />
            <button type="submit" class="btn btn-success">
              <i class="icon-base bx bx-check-circle me-1"></i> Mark v<?= $activeVersionNum ?> Paid
            </button>
          </form>
        <?php endif; ?>
        <a href="generate-invoice.php?id=<?= (int)$invoice->getId() ?>" class="btn btn-warning">
          <i class="icon-base bx bx-edit-alt me-1"></i> Edit Invoice
        </a>
        <a href="generate-invoice.php?id=<?= (int)$invoice->getId() ?>&revision=1" class="btn btn-info text-white">
          <i class="icon-base bx bx-plus-circle me-1"></i> Add Revision (v<?= $invoice->getCurrentVersion() + 1 ?>)
        </a>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteVersionModal">
          <i class="icon-base bx bx-trash me-1"></i> Delete v<?= $activeVersionNum ?>
        </button>
        <button onclick="window.print();" class="btn btn-primary">
          <i class="icon-base bx bx-printer me-1"></i> Print / Download PDF
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
    <?php if (count($versions) > 1): ?>
      <div class="container no-print mb-4" style="max-width: 900px;">
        <div class="card p-3">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold me-2"><i class="icon-base bx bx-history me-1"></i> Invoice Revision History:</span>
            <?php foreach ($versions as $ver): ?>
              <?php $isAct = ($activeVersion !== null && $ver->getVersionNumber() === $activeVersion->getVersionNumber()); ?>
              <a href="invoice-details.php?id=<?= $invoice->getId() ?>&version=<?= $ver->getVersionNumber() ?>"
                 class="btn btn-sm <?= $isAct ? 'btn-primary' : 'btn-outline-secondary' ?> version-tab-btn">
                Version <?= $ver->getVersionNumber() ?>
                <?php if ($ver->getVersionNumber() === $invoice->getCurrentVersion()): ?>
                  <span class="badge bg-white text-primary ms-1">Latest</span>
                <?php endif; ?>
                <?php if ($ver->getPaymentStatus() === 'paid'): ?>
                  <span class="badge bg-success ms-1">PAID</span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Printable Official Tax Invoice Document -->
    <div class="invoice-card">
      <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
          <div class="d-flex align-items-center gap-3 mb-2">
            <img src="../../../assets/img/logo.png" alt="tech-xpert Logo" style="height: 65px; width: auto; object-fit: contain;" />
            <div>
              <h2 class="fw-bold mb-0" style="color: #1a9df4;"><?= htmlspecialchars($company !== null ? $company->getCompanyName() : 'tech-xpert Services Pvt Ltd', ENT_QUOTES, 'UTF-8') ?></h2>
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
          <h3 class="fw-bold text-dark mb-1">TAX INVOICE</h3>
          <span class="badge bg-label-success fs-6"><?= htmlspecialchars($invoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></span>
          <?php if (count($versions) > 1): ?>
            <div class="mt-1 text-muted">
              <strong>Version:</strong> <?= $activeVersion !== null ? $activeVersion->getVersionNumber() : 1 ?> of <?= $invoice->getCurrentVersion() ?>
            </div>
          <?php endif; ?>
          <div class="mt-2">
            <?php if ($activeVersion !== null && $activeVersion->getPaymentStatus() === 'paid'): ?>
              <span class="badge bg-success fs-6">PAID</span>
            <?php elseif ($activeVersion !== null && $activeVersion->getPaymentStatus() === 'partially_paid'): ?>
              <span class="badge bg-info fs-6">PARTIALLY PAID</span>
            <?php else: ?>
              <span class="badge bg-warning fs-6">UNPAID</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Customer & Invoice Info -->
      <div class="row mb-4">
        <div class="col-6">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Billed To (Customer)</h6>
          <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($invoice->getCustomerName(), ENT_QUOTES, 'UTF-8') ?></div>
          <div>📱 <?= htmlspecialchars($invoice->getCustomerMobile(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php if (!empty($invoice->getCustomerEmail())): ?>
            <div>✉️ <?= htmlspecialchars($invoice->getCustomerEmail(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>
        <div class="col-6 text-end">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Invoice Details</h6>
          <div><strong>Request ID:</strong> <span class="badge bg-label-info"><?= htmlspecialchars($invoice->getServiceRequestId(), ENT_QUOTES, 'UTF-8') ?></span></div>
          <?php if ($linkedQuotationNumber !== null): ?>
            <div><strong>Quotation:</strong> <span class="badge bg-label-primary"><?= htmlspecialchars($linkedQuotationNumber, ENT_QUOTES, 'UTF-8') ?></span></div>
          <?php endif; ?>
          <?php if ($activeVersion !== null && $activeVersion->getQuotationVersion() !== null): ?>
            <div><strong>Quotation Version:</strong> <span class="badge bg-primary">Version <?= (int)$activeVersion->getQuotationVersion() ?></span></div>
          <?php endif; ?>
          <div><strong>Invoice Date:</strong> <?= htmlspecialchars($activeVersion !== null ? $activeVersion->getInvoiceDate() : '', ENT_QUOTES, 'UTF-8') ?></div>
          <div><strong>Due Date:</strong> <?= htmlspecialchars($activeVersion !== null ? $activeVersion->getDueDate() : '', ENT_QUOTES, 'UTF-8') ?></div>
          <div><strong>Payment Method:</strong> <?= htmlspecialchars($activeVersion !== null ? $activeVersion->getPaymentMethod() : 'Cash', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>

      <?php if ($activeVersion !== null && !empty($activeVersion->getRevisionNotes())): ?>
        <div class="alert alert-info mb-4">
          <strong><i class="icon-base bx bx-info-circle me-1"></i> <?= count($versions) > 1 ? "Revision Notes (Version {$activeVersion->getVersionNumber()}):" : 'Notes:' ?></strong>
          <?= htmlspecialchars($activeVersion->getRevisionNotes(), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <!-- Particulars Table -->
      <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 35%;">Item / Service Description</th>
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
              <tr><td colspan="6" class="text-center text-muted">No line items recorded.</td></tr>
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
              <td class="fs-5 fw-bold text-success">Total Amount:</td>
              <td class="text-end fs-5 fw-bold text-success">₹<?= number_format($activeVersion !== null ? $activeVersion->getTotalAmount() : 0, 2) ?></td>
            </tr>
          </table>
        </div>
      </div>

      <div class="border-top pt-4 mt-4 text-muted" style="font-size: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>Authorized Signatory: <strong>___________________</strong></div>
          <div class="fw-semibold" style="color: #1a9df4;">Thank you for choosing <?= htmlspecialchars($company !== null ? $company->getCompanyName() : 'tech-xpert Services', ENT_QUOTES, 'UTF-8') ?>!</div>
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
            <h5 class="modal-title text-white fw-bold"><i class="icon-base bx bx-trash me-2"></i>Delete Invoice Version <?= $activeVersionNum ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="invoice-details.php?id=<?= $invoice->getId() ?>&version=<?= $activeVersionNum ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="delete_version" />
            <input type="hidden" name="version_number" value="<?= $activeVersionNum ?>" />

            <div class="modal-body p-4">
              <p class="text-dark fw-semibold mb-3">Are you sure you want to delete <strong>Version <?= $activeVersionNum ?></strong> of Invoice <strong><?= htmlspecialchars($invoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></strong>?</p>
              <?php if (count($versions) <= 1): ?>
                <div class="alert alert-warning mb-0">
                  <i class="icon-base bx bx-error me-1"></i> This is the only version. Deleting it will remove the entire invoice!
                </div>
              <?php endif; ?>
            </div>

            <div class="modal-footer bg-light px-4 py-3">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger fw-bold"><i class="icon-base bx bx-trash me-1"></i> Delete Version <?= $activeVersionNum ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php if (isset($_GET['print']) && $_GET['print'] === '1'): ?>
      <script>
        window.addEventListener('DOMContentLoaded', () => {
          setTimeout(() => { window.print(); }, 300);
        });
      </script>
    <?php endif; ?>

    <!-- JS Scripts -->
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
  </body>
</html>
