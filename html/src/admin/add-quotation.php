<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Quotation.php';
require_once __DIR__ . '/Model/QuotationVersion.php';
require_once __DIR__ . '/Model/QuotationItem.php';
require_once __DIR__ . '/Repository/QuotationRepository.php';
require_once __DIR__ . '/Service/QuotationManagementService.php';
require_once __DIR__ . '/Controller/QuotationController.php';

use App\Admin\Controller\QuotationController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\QuotationRepository;
use App\Admin\Service\QuotationManagementService;

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
$repository = new QuotationRepository($dbConn);
$service = new QuotationManagementService($repository);
$controller = new QuotationController($service);

$quotationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$existingQuotation = $quotationId > 0 ? $service->getQuotationById($quotationId) : null;
$existingVersions = $quotationId > 0 ? $service->getQuotationVersions($quotationId) : [];
$latestVersion = !empty($existingVersions) ? end($existingVersions) : null;

$isRevisionMode = $existingQuotation !== null;
$serviceRequests = [];
$srRes = $dbConn->query("SELECT service_request_no, customer_name, request_by_mobile_no, customer_email, service_name FROM service_requests ORDER BY id DESC");
if ($srRes) {
    while ($srRow = $srRes->fetch_assoc()) {
        $serviceRequests[] = $srRow;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $result = $controller->handleRequest($_POST);
        if ($result['success']) {
            header('Location: quotations.php');
            exit;
        } else {
            $actionError = $result['message'];
        }
    }
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

    <title><?= $isRevisionMode ? 'Create Quotation Revision' : 'Create Quotation' ?> - Tech-xpert Admin</title>

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
        $activePage = 'add-quotation';
        require __DIR__ . '/sidebar.php';
        ?>

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->
          <nav
            class="layout-navbar container-fluid px-3 px-md-4 navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
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
            <div class="container-fluid px-3 px-md-4 flex-grow-1 container-p-y">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-1 mb-0">
                  <?= $isRevisionMode ? 'Update Quotation (Version ' . ($existingQuotation->getCurrentVersion() + 1) . ')' : 'Create New Quotation' ?>
                </h4>
                <a href="quotations.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Quotations
                </a>
              </div>

              <?php if ($actionError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <form method="POST" action="add-quotation.php<?= $isRevisionMode ? '?id=' . $existingQuotation->getId() : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="action" value="<?= $isRevisionMode ? 'revise' : 'create' ?>" />
                <?php if ($isRevisionMode): ?>
                  <input type="hidden" name="quotation_id" value="<?= (int)$existingQuotation->getId() ?>" />
                <?php endif; ?>

                <!-- Step 1: Service Request Search & Selection Card -->
                <?php if (!$isRevisionMode): ?>
                  <div class="card p-4 mb-4 border-primary">
                    <h5 class="card-title text-primary mb-3">
                      <i class="icon-base bx bx-search-alt me-1"></i> Step 1: Select Service Request <span class="text-danger">*</span>
                    </h5>
                    <div class="row g-3 align-items-end">
                      <div class="col-md-8">
                        <label class="form-label fw-semibold" for="sr_search_input">Service Request No <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <input
                            type="text"
                            class="form-control"
                            id="sr_search_input"
                            placeholder="Enter Service Request No (e.g. REQ-1001)"
                            value="<?= htmlspecialchars($_POST['service_request_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required />
                          <button type="button" class="btn btn-primary" id="btn_search_sr">
                            <i class="icon-base bx bx-search me-1"></i> Search & Load
                          </button>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div id="sr_status_badge">
                          <span class="badge bg-warning text-dark w-100 py-2"><i class="icon-base bx bx-error me-1"></i> Service Request Required</span>
                        </div>
                      </div>
                    </div>
                    <div id="sr_search_msg" class="form-text mt-2"></div>
                  </div>
                <?php endif; ?>

                <!-- Customer & Request Info Card -->
                <div class="card p-4 mb-4">
                  <h5 class="card-title text-primary mb-3">
                    <i class="icon-base bx bx-user-pin me-1"></i> Service Request Details
                  </h5>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label" for="service_request_id">Service Request ID <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="service_request_id"
                        name="service_request_id"
                        value="<?= htmlspecialchars($isRevisionMode ? $existingQuotation->getServiceRequestId() : ($_POST['service_request_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. REQ-1001"
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
                        value="<?= htmlspecialchars($isRevisionMode ? $existingQuotation->getCustomerName() : ($_POST['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Customer Full Name"
                        required />
                    </div>

                    <div class="col-md-4">
                      <label class="form-label" for="customer_mobile">Mobile Number <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="customer_mobile"
                        name="customer_mobile"
                        value="<?= htmlspecialchars($isRevisionMode ? $existingQuotation->getCustomerMobile() : ($_POST['customer_mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="10-digit mobile number"
                        required />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label" for="customer_email">Customer Email</label>
                      <input
                        type="email"
                        class="form-control"
                        id="customer_email"
                        name="customer_email"
                        value="<?= htmlspecialchars($isRevisionMode ? (string)$existingQuotation->getCustomerEmail() : ($_POST['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="customer@example.com" />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label" for="service_name">Service Name / Description <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="service_name"
                        name="service_name"
                        value="<?= htmlspecialchars($isRevisionMode ? $existingQuotation->getServiceName() : ($_POST['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. Electrical Wiring & AC Maintenance"
                        required />
                    </div>
                  </div>
                </div>

                <!-- Quotation Line Items Table Card -->
                <div class="card p-4 mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title text-primary mb-0">
                      <i class="icon-base bx bx-list-check me-1"></i> Itemized Particulars
                    </h5>
                    <button type="button" id="addItemRow" class="btn btn-sm btn-outline-primary">
                      <i class="icon-base bx bx-plus"></i> Add Particular
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
                        $initialItems = ($latestVersion !== null) ? $latestVersion->getItems() : [];
                        if (empty($initialItems)) {
                            // Default empty row
                            $initialItems = [null];
                        }
                        foreach ($initialItems as $idx => $item):
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
                        <div class="d-flex justify-content-between text-primary fs-5 fw-bold">
                          <span>Final Total Amount:</span>
                          <strong id="dispTotal">₹0.00</strong>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-4">
                  <a href="quotations.php" class="btn btn-outline-secondary">Cancel</a>
                  <button type="submit" class="btn btn-primary">
                    <i class="icon-base bx bx-save me-1"></i> <?= $isRevisionMode ? 'Save & Create Revision' : 'Save & Create Quotation' ?>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Core JS -->
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../../assets/vendor/libs/select2/select2.js"></script>

    <!-- Calculation Script -->
    <script>
      let rowCounter = <?= count($initialItems) ?>;

      $(document).ready(function () {
        function calculateTotals() {
          let subtotal = 0;
          let totalDiscount = 0;
          let totalTax = 0;

          $('.item-row').each(function () {
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

        // Event delegation for live input updates
        $('#itemsContainer').on('input', '.item-qty, .item-price, .item-disc-pct, .item-gst-pct', function () {
          calculateTotals();
        });

        // Add new row
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

        // Service Request AJAX search & auto-fill (Invoice pattern)
        function loadServiceRequestData(srNo) {
          $('#sr_search_msg').html('<span class="text-info"><i class="icon-base bx bx-loader-alt bx-spin me-1"></i> Searching Service Request...</span>');

          $.getJSON('api-get-service-request.php?sr=' + encodeURIComponent(srNo), function (res) {
            if (!res.success) {
              $('#sr_search_msg').html('<span class="text-danger"><i class="icon-base bx bx-error me-1"></i> ' + res.message + '</span>');
              $('#sr_status_badge').html('<span class="badge bg-danger w-100 py-2"><i class="icon-base bx bx-x-circle me-1"></i> Not Found</span>');
              $('#service_request_id').val('');
              return;
            }

            let sr = res.service_request;
            $('#service_request_id').val(sr.service_request_no);
            $('#customer_name').val(sr.customer_name);
            $('#customer_mobile').val(sr.customer_mobile);
            $('#customer_email').val(sr.customer_email);
            $('#service_name').val(sr.service_name);

            const firstDescInput = $('.item-desc').first();
            if (firstDescInput.length > 0 && !firstDescInput.val()) {
              firstDescInput.val(sr.service_name);
            }

            $('#sr_search_msg').html('<span class="text-success"><i class="icon-base bx bx-check-circle me-1"></i> Loaded Service Request ' + sr.service_request_no + ' successfully!</span>');
            $('#sr_status_badge').html('<span class="badge bg-success w-100 py-2"><i class="icon-base bx bx-check-circle me-1"></i> Request Loaded</span>');
          }).fail(function () {
            $('#sr_search_msg').html('<span class="text-danger"><i class="icon-base bx bx-error me-1"></i> Error loading Service Request details. Please try again.</span>');
            $('#sr_status_badge').html('<span class="badge bg-danger w-100 py-2"><i class="icon-base bx bx-x-circle me-1"></i> Error</span>');
          });
        }

        $('#btn_search_sr').on('click', function () {
          let srNo = $('#sr_search_input').val().trim();
          if (!srNo) {
            alert('Please enter a Service Request No to search.');
            return;
          }
          loadServiceRequestData(srNo);
        });

        $('#sr_search_input').on('keypress', function (e) {
          if (e.which === 13) {
            e.preventDefault();
            $('#btn_search_sr').click();
          }
        });

        $('form').on('submit', function (e) {
          let srId = $('#service_request_id').val();
          let isRevision = <?= $isRevisionMode ? 'true' : 'false' ?>;
          if (!isRevision && (!srId || srId.trim() === '')) {
            e.preventDefault();
            alert('A valid Service Request is required to create a quotation. Please enter a Service Request No and click "Search & Load".');
            $('#sr_search_input').focus();
          }
        });

        // Remove row
        $('#itemsContainer').on('click', '.remove-row', function () {
          if ($('#itemsContainer tr.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
          } else {
            alert('At least one item row is required.');
          }
        });

        // Initial calculation
        calculateTotals();
      });
    </script>
  </body>
</html>
