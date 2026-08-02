<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Invoice.php';
require_once __DIR__ . '/Model/InvoiceItem.php';
require_once __DIR__ . '/Model/InvoiceVersion.php';
require_once __DIR__ . '/Model/Quotation.php';
require_once __DIR__ . '/Model/QuotationVersion.php';
require_once __DIR__ . '/Model/QuotationItem.php';
require_once __DIR__ . '/Repository/InvoiceRepository.php';
require_once __DIR__ . '/Repository/QuotationRepository.php';
require_once __DIR__ . '/Service/InvoiceManagementService.php';
require_once __DIR__ . '/Controller/InvoiceController.php';

use App\Admin\Controller\InvoiceController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\InvoiceRepository;
use App\Admin\Repository\QuotationRepository;
use App\Admin\Service\InvoiceManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = (array) $_SESSION['user'];
$username = (string) ($user['username'] ?? 'Admin');
$role = (string) ($user['role'] ?? 'admin');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$invRepo = new InvoiceRepository($dbConn);
$quoRepo = new QuotationRepository($dbConn);
$service = new InvoiceManagementService($invRepo, $quoRepo);
$controller = new InvoiceController($service);

$quotationId = isset($_GET['quotation_id']) ? (int) $_GET['quotation_id'] : 0;
$invoiceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isRevision = isset($_GET['revision']) && $_GET['revision'] === '1';

$existingInvoice = $invoiceId > 0 ? $service->getInvoiceById($invoiceId) : null;
$linkedQuotation = $quotationId > 0 ? $quoRepo->findById($quotationId) : null;
$versionNum = isset($_GET['version']) ? (int) $_GET['version'] : (isset($_POST['quotation_version']) ? (int) $_POST['quotation_version'] : null);

// Determine mode
$isEditing = ($existingInvoice !== null && !$isRevision);
$isAddingRevision = ($existingInvoice !== null && $isRevision);

// Pre-fill from Quotation or existing invoice
$preFillItems = [];
$preFillSubtotal = 0.0;
$preFillDiscount = 0.0;
$preFillTax = 0.0;
$preFillPStatus = 'unpaid';
$preFillPMethod = 'UPI';
$preFillInvoiceDate = date('Y-m-d');
$preFillDueDate = date('Y-m-d', strtotime('+7 days'));
$preFillNotes = '';

if ($linkedQuotation !== null) {
    $versions = $quoRepo->findVersionsByQuotationId($quotationId);
    $selectedVer = null;
    if ($versionNum !== null) {
        foreach ($versions as $v) {
            if ($v->getVersionNumber() === $versionNum) {
                $selectedVer = $v;
                break;
            }
        }
    }
    if ($selectedVer === null && !empty($versions)) {
        $selectedVer = end($versions);
    }
    if ($selectedVer !== null) {
        $versionNum = $selectedVer->getVersionNumber();
        $preFillItems = $selectedVer->getItems();
        $preFillSubtotal = $selectedVer->getSubtotal();
        $preFillDiscount = $selectedVer->getDiscount();
        $preFillTax = $selectedVer->getTax();
        $preFillNotes = "Tax Invoice generated from Quotation {$linkedQuotation->getQuotationNumber()} (Version {$versionNum})";
    }
} elseif ($existingInvoice !== null) {
    $latestVer = $existingInvoice->getLatestVersion();
    if ($latestVer !== null) {
        $preFillItems = $latestVer->getItems();
        $preFillSubtotal = $latestVer->getSubtotal();
        $preFillDiscount = $latestVer->getDiscount();
        $preFillTax = $latestVer->getTax();
        $preFillPStatus = $isAddingRevision ? 'unpaid' : $latestVer->getPaymentStatus();
        $preFillPMethod = $latestVer->getPaymentMethod();
        $preFillInvoiceDate = $isAddingRevision ? date('Y-m-d') : $latestVer->getInvoiceDate();
        $preFillDueDate = $isAddingRevision ? date('Y-m-d', strtotime('+7 days')) : $latestVer->getDueDate();
        $preFillNotes = $isAddingRevision 
            ? "Revised invoice Version " . ($existingInvoice->getCurrentVersion() + 1)
            : ($latestVer->getRevisionNotes() ?? '');
    }
}

$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $result = $controller->handleRequest($_POST);
        if ($result['success']) {
            $redirectId = $result['invoice_id'] ?? $result['id'] ?? $invoiceId;
            if ($redirectId > 0) {
                header("Location: invoice-details.php?id={$redirectId}&msg=" . urlencode($result['message']));
            } else {
                header('Location: invoices.php');
            }
            exit;
        } else {
            $actionError = $result['message'];
        }
    }
}

// Page title
if ($isAddingRevision) {
    $pageTitle = "Add Invoice Revision (v" . ($existingInvoice->getCurrentVersion() + 1) . ") — " . $existingInvoice->getInvoiceNumber();
} elseif ($isEditing) {
    $pageTitle = "Edit Invoice ({$existingInvoice->getInvoiceNumber()})";
} elseif ($linkedQuotation !== null) {
    $pageTitle = "Generate Tax Invoice from Quotation {$linkedQuotation->getQuotationNumber()}";
} else {
    $pageTitle = "Create New Tax Invoice";
}
?>
<!doctype html>

<html
  lang="en"
  class="layout-menu-fixed layout-compact"
  data-assets-path="../../../assets/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?= $isAddingRevision ? 'Add Invoice Revision' : ($isEditing ? 'Edit Invoice' : 'Generate Tax Invoice') ?> - tech-xpert Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../../../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Helpers -->
    <script src="../../../assets/vendor/js/helpers.js"></script>
    <script src="../../../assets/js/config.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Sidebar Menu -->
        <?php
        $activePage = 'generate-invoice';
        require __DIR__ . '/sidebar.php';
        ?>

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->
          <nav
            class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
              <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a
                    class="nav-link dropdown-toggle hide-arrow p-0"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="../../../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="../../../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-0"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h6>
                            <small class="text-body-secondary"><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?></small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="dashboard.php?action=logout">
                        <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                <div class="d-flex gap-2">
                  <?php if ($existingInvoice !== null): ?>
                    <a href="invoice-details.php?id=<?= (int)$existingInvoice->getId() ?>" class="btn btn-outline-info">
                      <i class="icon-base bx bx-file me-1"></i> View Invoice
                    </a>
                  <?php endif; ?>
                  <a href="invoices.php" class="btn btn-outline-secondary">
                    <i class="icon-base bx bx-arrow-back me-1"></i> Back to Invoices
                  </a>
                </div>
              </div>

              <?php if ($isAddingRevision): ?>
                <div class="alert alert-info alert-dismissible mb-4" role="alert">
                  <i class="icon-base bx bx-info-circle me-1"></i>
                  You are creating <strong>Version <?= $existingInvoice->getCurrentVersion() + 1 ?></strong> of Invoice <strong><?= htmlspecialchars($existingInvoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></strong>.
                  The previous version(s) will be preserved in the revision history.
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <?php if ($actionError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <form method="POST" action="generate-invoice.php<?= $invoiceId > 0 ? "?id={$invoiceId}" . ($isRevision ? '&revision=1' : '') : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                <?php if ($isAddingRevision): ?>
                  <input type="hidden" name="action" value="add_revision" />
                  <input type="hidden" name="invoice_id" value="<?= (int)$existingInvoice->getId() ?>" />
                <?php else: ?>
                  <input type="hidden" name="action" value="save" />
                  <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int)$existingInvoice->getId() ?>" />
                    <input type="hidden" name="invoice_number" value="<?= htmlspecialchars($existingInvoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?>" />
                  <?php endif; ?>
                <?php endif; ?>

                <input type="hidden" name="quotation_id" id="quotation_id" value="<?= $linkedQuotation !== null ? (int)$linkedQuotation->getId() : '' ?>" />
                <input type="hidden" name="quotation_version" id="quotation_version" value="<?= $versionNum !== null ? (int)$versionNum : '' ?>" />

                <!-- Quotation Search & Selector Card -->
                <?php if (!$isEditing && !$isAddingRevision): ?>
                  <div class="card p-4 mb-4 border-primary">
                    <h5 class="card-title text-primary mb-3">
                      <i class="icon-base bx bx-search-alt me-1"></i> Step 1: Select Quotation <span class="text-danger">*</span>
                    </h5>
                    <div class="row g-3 align-items-end">
                      <div class="col-md-6">
                        <label class="form-label fw-semibold" for="quotation_search_input">Quotation Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <input
                            type="text"
                            class="form-control"
                            id="quotation_search_input"
                            placeholder="Enter Quotation No (e.g. QUO-2026-001)"
                            value="<?= $linkedQuotation !== null ? htmlspecialchars($linkedQuotation->getQuotationNumber(), ENT_QUOTES, 'UTF-8') : '' ?>"
                            required />
                          <button type="button" class="btn btn-primary" id="btn_search_quotation">
                            <i class="icon-base bx bx-search me-1"></i> Search & Load
                          </button>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold" for="quotation_version_select">Quotation Version</label>
                        <select class="form-select" id="quotation_version_select" disabled>
                          <option value="">-- Select Version --</option>
                          <?php if ($linkedQuotation !== null): ?>
                            <option value="<?= (int)$versionNum ?>" selected>Version <?= (int)$versionNum ?></option>
                          <?php endif; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <div id="quotation_status_badge">
                          <?php if ($linkedQuotation !== null): ?>
                            <span class="badge bg-success w-100 py-2"><i class="icon-base bx bx-check-circle me-1"></i> Quotation Loaded</span>
                          <?php else: ?>
                            <span class="badge bg-warning text-dark w-100 py-2"><i class="icon-base bx bx-error me-1"></i> Quotation Required</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <div id="quotation_search_msg" class="form-text mt-2"></div>
                  </div>
                <?php endif; ?>

                <!-- Customer & Invoice Details -->
                <div class="card p-4 mb-4">
                  <h5 class="card-title text-primary mb-3">
                    <i class="icon-base bx bx-file me-1"></i> Invoice & Customer Details
                  </h5>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label" for="service_request_id">Service Request ID <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="service_request_id"
                        name="service_request_id"
                        value="<?= htmlspecialchars($existingInvoice !== null ? $existingInvoice->getServiceRequestId() : ($linkedQuotation !== null ? $linkedQuotation->getServiceRequestId() : ''), ENT_QUOTES, 'UTF-8') ?>"
                        required
                        readonly />
                    </div>

                    <div class="col-md-4">
                      <label class="form-label" for="customer_name">Customer Name <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="customer_name"
                        name="customer_name"
                        value="<?= htmlspecialchars($existingInvoice !== null ? $existingInvoice->getCustomerName() : ($linkedQuotation !== null ? $linkedQuotation->getCustomerName() : ''), ENT_QUOTES, 'UTF-8') ?>"
                        required />
                    </div>

                    <div class="col-md-4">
                      <label class="form-label" for="customer_mobile">Mobile Number <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="customer_mobile"
                        name="customer_mobile"
                        value="<?= htmlspecialchars($existingInvoice !== null ? $existingInvoice->getCustomerMobile() : ($linkedQuotation !== null ? $linkedQuotation->getCustomerMobile() : ''), ENT_QUOTES, 'UTF-8') ?>"
                        required />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label" for="customer_email">Customer Email</label>
                      <input
                        type="email"
                        class="form-control"
                        id="customer_email"
                        name="customer_email"
                        value="<?= htmlspecialchars($existingInvoice !== null ? (string)$existingInvoice->getCustomerEmail() : ($linkedQuotation !== null ? (string)$linkedQuotation->getCustomerEmail() : ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label" for="service_name">Service Description <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="service_name"
                        name="service_name"
                        value="<?= htmlspecialchars($existingInvoice !== null ? $existingInvoice->getServiceName() : ($linkedQuotation !== null ? $linkedQuotation->getServiceName() : ''), ENT_QUOTES, 'UTF-8') ?>"
                        required />
                    </div>

                    <div class="col-md-3">
                      <label class="form-label" for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                      <input
                        type="date"
                        class="form-control"
                        id="invoice_date"
                        name="invoice_date"
                        value="<?= htmlspecialchars($preFillInvoiceDate, ENT_QUOTES, 'UTF-8') ?>"
                        required />
                    </div>

                    <div class="col-md-3">
                      <label class="form-label" for="due_date">Due Date <span class="text-danger">*</span></label>
                      <input
                        type="date"
                        class="form-control"
                        id="due_date"
                        name="due_date"
                        value="<?= htmlspecialchars($preFillDueDate, ENT_QUOTES, 'UTF-8') ?>"
                        required />
                    </div>

                    <div class="col-md-3">
                      <label class="form-label" for="payment_status">Payment Status</label>
                      <select class="form-select" id="payment_status" name="payment_status">
                        <option value="unpaid" <?= $preFillPStatus === 'unpaid' ? 'selected' : '' ?>>UNPAID</option>
                        <option value="paid" <?= $preFillPStatus === 'paid' ? 'selected' : '' ?>>PAID</option>
                        <option value="partially_paid" <?= $preFillPStatus === 'partially_paid' ? 'selected' : '' ?>>PARTIALLY PAID</option>
                      </select>
                    </div>

                    <div class="col-md-3">
                      <label class="form-label" for="payment_method">Payment Method</label>
                      <select class="form-select" id="payment_method" name="payment_method">
                        <option value="UPI" <?= $preFillPMethod === 'UPI' ? 'selected' : '' ?>>UPI / QR Code</option>
                        <option value="Cash" <?= $preFillPMethod === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="Card" <?= $preFillPMethod === 'Card' ? 'selected' : '' ?>>Credit / Debit Card</option>
                        <option value="Net Banking" <?= $preFillPMethod === 'Net Banking' ? 'selected' : '' ?>>Net Banking</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Invoice Line Items Table -->
                <div class="card p-4 mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title text-primary mb-0">
                      <i class="icon-base bx bx-list-check me-1"></i> Particulars Breakdown
                    </h5>
                    <button type="button" id="addItemRow" class="btn btn-sm btn-outline-primary">
                      <i class="icon-base bx bx-plus"></i> Add Item
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="itemsTable">
                      <thead class="table-light">
                        <tr>
                          <th style="width: 35%;">Description</th>
                          <th style="width: 10%;">Qty</th>
                          <th style="width: 15%;">Unit Price (₹)</th>
                          <th style="width: 12%;">Discount (%)</th>
                          <th style="width: 12%;">GST (%)</th>
                          <th style="width: 12%;">Total (₹)</th>
                          <th style="width: 4%;">Action</th>
                        </tr>
                      </thead>
                      <tbody id="itemsContainer">
                        <?php
                        if (empty($preFillItems)) {
                            $preFillItems = [null];
                        }
                        foreach ($preFillItems as $idx => $item):
                            $desc = $item !== null ? $item->getItemDescription() : '';
                            $qty = $item !== null ? $item->getQuantity() : 1;
                            $uPrice = $item !== null ? $item->getUnitPrice() : 0.0;
                            $discPct = $item !== null ? $item->getDiscountPercent() : 0.0;
                            $gstPct = $item !== null ? $item->getGstPercent() : 18.0;
                            $tPrice = $item !== null ? $item->getTotalPrice() : 0.0;
                        ?>
                          <tr class="item-row">
                            <td>
                              <input type="text" class="form-control item-desc" name="items[<?= $idx ?>][description]" value="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>" placeholder="Item / Labor description..." required />
                            </td>
                            <td>
                              <input type="number" class="form-control item-qty" name="items[<?= $idx ?>][quantity]" value="<?= $qty ?>" min="1" required />
                            </td>
                            <td>
                              <input type="number" step="0.01" class="form-control item-price" name="items[<?= $idx ?>][unit_price]" value="<?= number_format($uPrice, 2, '.', '') ?>" min="0" required />
                            </td>
                            <td>
                              <input type="number" step="0.01" class="form-control item-disc-pct" name="items[<?= $idx ?>][discount_percent]" value="<?= number_format($discPct, 2, '.', '') ?>" min="0" max="100" placeholder="0" />
                            </td>
                            <td>
                              <input type="number" step="0.01" class="form-control item-gst-pct" name="items[<?= $idx ?>][gst_percent]" value="<?= number_format($gstPct, 2, '.', '') ?>" min="0" max="100" placeholder="18" />
                            </td>
                            <td>
                              <input type="number" step="0.01" class="form-control item-total" name="items[<?= $idx ?>][total_price]" value="<?= number_format($tPrice, 2, '.', '') ?>" readonly />
                            </td>
                            <td class="text-center">
                              <button type="button" class="btn btn-sm btn-icon btn-outline-danger remove-row"><i class="icon-base bx bx-trash"></i></button>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- Calculations Summary -->
                  <div class="row justify-content-end mt-4">
                    <div class="col-md-5">
                      <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                          <span>Subtotal (Base Amount):</span>
                          <strong id="dispSubtotal">₹0.00</strong>
                          <input type="hidden" name="subtotal" id="inputSubtotal" value="0" />
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span>Item Discounts:</span>
                          <strong id="dispDiscount">₹0.00</strong>
                          <input type="hidden" name="discount" id="inputDiscount" value="0" />
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span>GST Tax Amount:</span>
                          <strong id="dispTax">₹0.00</strong>
                          <input type="hidden" name="tax" id="inputTax" value="0" />
                        </div>
                        <hr />
                        <div class="d-flex justify-content-between text-success fs-5 fw-bold">
                          <span>Invoice Total:</span>
                          <span id="dispTotal">₹0.00</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Notes Card -->
                <div class="card p-4 mb-4">
                  <div class="mb-3">
                    <label class="form-label" for="notes"><?= $isAddingRevision ? 'Revision Notes:' : 'Invoice Terms & Notes:' ?></label>
                    <textarea class="form-control" id="notes" name="<?= $isAddingRevision ? 'revision_notes' : 'notes' ?>" rows="2" placeholder="<?= $isAddingRevision ? 'Describe what changed in this revision...' : 'Payment due within 7 days. Thank you for your business!' ?>"><?= htmlspecialchars($preFillNotes, ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>

                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4">
                      <i class="icon-base bx bx-check me-1"></i>
                      <?php if ($isAddingRevision): ?>
                        Save Revision (v<?= $existingInvoice->getCurrentVersion() + 1 ?>)
                      <?php elseif ($isEditing): ?>
                        Update Invoice
                      <?php else: ?>
                        Save & Generate Invoice
                      <?php endif; ?>
                    </button>
                    <?php if ($existingInvoice !== null): ?>
                      <a href="invoice-details.php?id=<?= (int)$existingInvoice->getId() ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php else: ?>
                      <a href="invoices.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                  </div>
                </div>
              </form>
            </div>
            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <!-- Core JS -->
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../../assets/vendor/js/menu.js"></script>
    <script src="../../../assets/js/main.js"></script>

    <!-- Calculation JS -->
    <script>
      $(document).ready(function () {
        let rowCounter = $('#itemsContainer tr').length;

        function calculateTotals() {
          let subtotal = 0;
          let totalDiscount = 0;
          let totalTax = 0;

          $('#itemsContainer tr.item-row').each(function () {
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let price = parseFloat($(this).find('.item-price').val()) || 0;
            let discPct = parseFloat($(this).find('.item-disc-pct').val()) || 0;
            let gstPct = parseFloat($(this).find('.item-gst-pct').val()) || 0;

            let baseTotal = qty * price;
            let discAmt = baseTotal * (discPct / 100);
            let taxable = baseTotal - discAmt;
            let gstAmt = taxable * (gstPct / 100);
            let itemTotal = taxable + gstAmt;

            $(this).find('.item-total').val(itemTotal.toFixed(2));

            subtotal += baseTotal;
            totalDiscount += discAmt;
            totalTax += gstAmt;
          });

          let finalTotal = (subtotal - totalDiscount) + totalTax;

          $('#dispSubtotal').text('₹' + subtotal.toFixed(2));
          $('#inputSubtotal').val(subtotal.toFixed(2));

          $('#dispDiscount').text('₹' + totalDiscount.toFixed(2));
          $('#inputDiscount').val(totalDiscount.toFixed(2));

          $('#dispTax').text('₹' + totalTax.toFixed(2));
          $('#inputTax').val(totalTax.toFixed(2));

          $('#dispTotal').text('₹' + finalTotal.toFixed(2));
        }

        $('#itemsContainer').on('input', '.item-qty, .item-price, .item-disc-pct, .item-gst-pct', function () {
          calculateTotals();
        });

        $('#addItemRow').on('click', function () {
          rowCounter++;
          let newRow = `
            <tr class="item-row">
              <td>
                <input type="text" class="form-control item-desc" name="items[${rowCounter}][description]" placeholder="Item / Labor description..." required />
              </td>
              <td>
                <input type="number" class="form-control item-qty" name="items[${rowCounter}][quantity]" value="1" min="1" required />
              </td>
              <td>
                <input type="number" step="0.01" class="form-control item-price" name="items[${rowCounter}][unit_price]" value="0.00" min="0" required />
              </td>
              <td>
                <input type="number" step="0.01" class="form-control item-disc-pct" name="items[${rowCounter}][discount_percent]" value="0.00" min="0" max="100" placeholder="0" />
              </td>
              <td>
                <input type="number" step="0.01" class="form-control item-gst-pct" name="items[${rowCounter}][gst_percent]" value="18.00" min="0" max="100" placeholder="18" />
              </td>
              <td>
                <input type="number" step="0.01" class="form-control item-total" name="items[${rowCounter}][total_price]" value="0.00" readonly />
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger remove-row"><i class="icon-base bx bx-trash"></i></button>
              </td>
            </tr>
          `;
          $('#itemsContainer').append(newRow);
          calculateTotals();
        });

        $('#itemsContainer').on('click', '.remove-row', function () {
          if ($('#itemsContainer tr.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
          } else {
            alert('At least one item row is required.');
          }
        });

        calculateTotals();

        // Quotation AJAX search & loading
        function loadQuotationData(quotationNum, versionNum) {
          let url = 'api-get-quotation.php?q=' + encodeURIComponent(quotationNum);
          if (versionNum) {
            url += '&version=' + versionNum;
          }

          $('#quotation_search_msg').html('<span class="text-info"><i class="icon-base bx bx-loader-alt bx-spin me-1"></i> Searching quotation details...</span>');

          $.getJSON(url, function (res) {
            if (!res.success) {
              $('#quotation_search_msg').html('<span class="text-danger"><i class="icon-base bx bx-error me-1"></i> ' + res.message + '</span>');
              $('#quotation_status_badge').html('<span class="badge bg-danger w-100 py-2"><i class="icon-base bx bx-x-circle me-1"></i> Not Found</span>');
              $('#quotation_id').val('');
              $('#quotation_version').val('');
              return;
            }

            let q = res.quotation;
            let v = res.version;

            $('#quotation_id').val(q.id);
            $('#quotation_version').val(v.version_number);
            $('#service_request_id').val(q.service_request_id);
            $('#customer_name').val(q.customer_name);
            $('#customer_mobile').val(q.customer_mobile);
            $('#customer_email').val(q.customer_email);
            $('#service_name').val(q.service_name);
            $('#inputDiscount').val(v.discount.toFixed(2));
            $('#notes').val('Tax Invoice generated from Quotation ' + q.quotation_number + ' (Version ' + v.version_number + ')');

            // Populate versions dropdown
            let verSelect = $('#quotation_version_select');
            verSelect.empty().prop('disabled', false);
            res.available_versions.forEach(function (ver) {
              let selected = (ver.version_number === v.version_number) ? 'selected' : '';
              verSelect.append(`<option value="${ver.version_number}" ${selected}>Version ${ver.version_number} (₹${parseFloat(ver.total_amount).toFixed(2)})</option>`);
            });

            // Populate line items
            let container = $('#itemsContainer');
            container.empty();
            rowCounter = 0;

            if (v.items && v.items.length > 0) {
              v.items.forEach(function (item) {
                rowCounter++;
                let newRow = `
                  <tr class="item-row">
                    <td>
                      <input type="text" class="form-control item-desc" name="items[${rowCounter}][description]" value="${item.description.replace(/"/g, '&quot;')}" placeholder="Item / Labor description..." required />
                    </td>
                    <td>
                      <input type="number" class="form-control item-qty" name="items[${rowCounter}][quantity]" value="${item.quantity}" min="1" required />
                    </td>
                    <td>
                      <input type="number" step="0.01" class="form-control item-price" name="items[${rowCounter}][unit_price]" value="${parseFloat(item.unit_price).toFixed(2)}" min="0" required />
                    </td>
                    <td>
                      <input type="number" step="0.01" class="form-control item-total" name="items[${rowCounter}][total_price]" value="${parseFloat(item.total_price).toFixed(2)}" readonly />
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-danger remove-row"><i class="icon-base bx bx-trash"></i></button>
                    </td>
                  </tr>
                `;
                container.append(newRow);
              });
            }

            calculateTotals();

            $('#quotation_search_msg').html('<span class="text-success"><i class="icon-base bx bx-check-circle me-1"></i> Loaded Quotation ' + q.quotation_number + ' (Version ' + v.version_number + ') successfully!</span>');
            $('#quotation_status_badge').html('<span class="badge bg-success w-100 py-2"><i class="icon-base bx bx-check-circle me-1"></i> Quotation Loaded</span>');
          }).fail(function () {
            $('#quotation_search_msg').html('<span class="text-danger"><i class="icon-base bx bx-error me-1"></i> Error loading quotation details. Please try again.</span>');
            $('#quotation_status_badge').html('<span class="badge bg-danger w-100 py-2"><i class="icon-base bx bx-x-circle me-1"></i> Error</span>');
          });
        }

        $('#btn_search_quotation').on('click', function () {
          let qNum = $('#quotation_search_input').val().trim();
          if (!qNum) {
            alert('Please enter a Quotation Number to search.');
            return;
          }
          loadQuotationData(qNum, null);
        });

        $('#quotation_search_input').on('keypress', function (e) {
          if (e.which === 13) {
            e.preventDefault();
            $('#btn_search_quotation').click();
          }
        });

        $('#quotation_version_select').on('change', function () {
          let qNum = $('#quotation_search_input').val().trim();
          let verNum = $(this).val();
          if (qNum && verNum) {
            loadQuotationData(qNum, verNum);
          }
        });

        $('form').on('submit', function (e) {
          let qId = $('#quotation_id').val();
          let isEditing = <?= ($isEditing || $isAddingRevision) ? 'true' : 'false' ?>;
          if (!isEditing && (!qId || parseInt(qId) <= 0)) {
            e.preventDefault();
            alert('A valid Quotation is required to generate an invoice. Please enter a Quotation Number and click "Search & Load".');
            $('#quotation_search_input').focus();
          }
        });
      });
    </script>
  </body>
</html>
