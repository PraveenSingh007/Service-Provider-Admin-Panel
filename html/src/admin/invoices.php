<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Invoice.php';
require_once __DIR__ . '/Model/InvoiceItem.php';
require_once __DIR__ . '/Model/InvoiceVersion.php';
require_once __DIR__ . '/Model/Quotation.php';
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

$actionMessage = null;
$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $result = $controller->handleRequest($_POST);
        if ($result['success']) {
            $actionMessage = $result['message'];
        } else {
            $actionError = $result['message'];
        }
    }
}

// Check for redirect messages
if (isset($_GET['msg'])) {
    $actionMessage = (string) $_GET['msg'];
}

$invoices = $service->getAllInvoices();

// Build flat list of all invoice versions for the table (each version = one row)
$allVersionRows = [];
foreach ($invoices as $inv) {
    foreach ($inv->getVersions() as $ver) {
        $allVersionRows[] = [
            'invoice' => $inv,
            'version' => $ver,
        ];
    }
    // If invoice has no versions (edge case), still show a row
    if (empty($inv->getVersions())) {
        $allVersionRows[] = [
            'invoice' => $inv,
            'version' => null,
        ];
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

    <title>Invoices - Tech-xpert Admin</title>

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

    <!-- DataTables & Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />

    <style>
      /* Compact Colorful Icon Buttons for DataTables */
      .dt-buttons .btn-icon-export {
        width: 38px;
        height: 38px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        font-size: 1.15rem;
        box-shadow: 0 0.125rem 0.25rem rgba(165, 163, 174, 0.25);
        transition: all 0.2s ease-in-out;
      }
      .dt-buttons .btn-icon-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 0.5rem rgba(165, 163, 174, 0.35);
      }
      .btn-export-copy { background-color: #8592a3 !important; color: #ffffff !important; border: none !important; }
      .btn-export-csv { background-color: #61BEF1 !important; color: #ffffff !important; border: none !important; }
      .btn-export-excel { background-color: #71dd37 !important; color: #ffffff !important; border: none !important; }
      .btn-export-pdf { background-color: #ff3e1d !important; color: #ffffff !important; border: none !important; }
      .btn-export-print { background-color: #03c3ec !important; color: #ffffff !important; border: none !important; }
      .version-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 12px;
      }
    </style>

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
        $activePage = 'invoices';
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
                <h4 class="fw-bold py-1 mb-0">Invoices Management</h4>
                <a href="generate-invoice.php" class="btn btn-primary">
                  <i class="icon-base bx bx-plus me-1"></i> Create New Invoice
                </a>
              </div>

              <?php if ($actionMessage !== null): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                  <?= htmlspecialchars($actionMessage, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <?php if ($actionError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <div class="card p-4">
                <div class="table-responsive">
                  <table id="invoicesTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>#ID</th>
                        <th>Invoice No</th>
                        <th>Request ID</th>
                        <th>Version</th>
                        <th>Customer Details</th>
                        <th>Invoice Date</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($allVersionRows as $row):
                        $inv = $row['invoice'];
                        $ver = $row['version'];
                        $verNum = $ver !== null ? $ver->getVersionNumber() : 1;
                        $totalVersions = $inv->getCurrentVersion();
                        $isLatest = ($verNum === $totalVersions);
                        $paymentStatus = $ver !== null ? $ver->getPaymentStatus() : 'unpaid';
                        $paymentMethod = $ver !== null ? $ver->getPaymentMethod() : 'Cash';
                        $totalAmount = $ver !== null ? $ver->getTotalAmount() : 0.0;
                        $invoiceDate = $ver !== null ? $ver->getInvoiceDate() : '';
                        $qVersion = $ver !== null ? $ver->getQuotationVersion() : null;
                      ?>
                        <tr>
                          <td data-order="<?= (int)$inv->getId() ?>"><strong>#<?= (int)$inv->getId() ?></strong></td>
                          <td><span class="badge bg-label-success fs-6"><?= htmlspecialchars($inv->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td><span class="badge bg-label-info"><?= htmlspecialchars($inv->getServiceRequestId(), ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td>
                            <span class="badge version-badge <?= $isLatest ? 'bg-primary' : 'bg-label-secondary' ?>">
                              v<?= $verNum ?><?= $isLatest && $totalVersions > 1 ? ' (Latest)' : '' ?>
                            </span>
                            <?php if ($totalVersions > 1): ?>
                              <small class="text-muted d-block">of <?= $totalVersions ?> versions</small>
                            <?php endif; ?>
                            <?php if ($qVersion !== null): ?>
                              <small class="text-muted d-block">Quo v<?= $qVersion ?></small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($inv->getCustomerName(), ENT_QUOTES, 'UTF-8') ?></span>
                            <small class="text-muted">📱 <?= htmlspecialchars($inv->getCustomerMobile(), ENT_QUOTES, 'UTF-8') ?></small>
                          </td>
                          <td><?= htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8') ?></td>
                          <td><strong class="text-primary fs-6">₹<?= number_format($totalAmount, 2) ?></strong></td>
                          <td>
                            <?php if ($paymentStatus === 'paid'): ?>
                              <span class="badge bg-label-success">PAID (<?= htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php elseif ($paymentStatus === 'partially_paid'): ?>
                              <span class="badge bg-label-info">PARTIAL</span>
                            <?php else: ?>
                              <span class="badge bg-label-warning">UNPAID</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="d-flex align-items-center gap-1">
                              <!-- Print Invoice Version -->
                              <a href="invoice-details.php?id=<?= (int)$inv->getId() ?>&version=<?= $verNum ?>&print=1" target="_blank" class="btn btn-sm btn-icon btn-outline-primary" title="Print Tax Invoice v<?= $verNum ?>">
                                <i class="icon-base bx bx-printer"></i>
                              </a>

                              <!-- View Details -->
                              <a href="invoice-details.php?id=<?= (int)$inv->getId() ?>&version=<?= $verNum ?>" class="btn btn-sm btn-icon btn-outline-info" title="View Tax Invoice Details v<?= $verNum ?>">
                                <i class="icon-base bx bx-file"></i>
                              </a>

                              <!-- Edit Invoice -->
                              <a href="generate-invoice.php?id=<?= (int)$inv->getId() ?>" class="btn btn-sm btn-icon btn-outline-warning" title="Edit Invoice">
                                <i class="icon-base bx bx-edit-alt"></i>
                              </a>

                              <!-- Mark Paid -->
                              <?php if ($paymentStatus !== 'paid'): ?>
                                <form method="POST" action="invoices.php" onsubmit="return confirm('Mark Version <?= $verNum ?> as PAID?');" class="d-inline">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                  <input type="hidden" name="action" value="mark_paid" />
                                  <input type="hidden" name="id" value="<?= (int)$inv->getId() ?>" />
                                  <input type="hidden" name="version_number" value="<?= $verNum ?>" />
                                  <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Mark Paid">
                                    <i class="icon-base bx bx-check-circle"></i>
                                  </button>
                                </form>
                              <?php endif; ?>

                              <!-- Delete -->
                              <?php if ($totalVersions > 1): ?>
                                <form method="POST" action="invoices.php" onsubmit="return confirm('Delete Version <?= $verNum ?> of this invoice?');" class="d-inline">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                  <input type="hidden" name="action" value="delete_version" />
                                  <input type="hidden" name="invoice_id" value="<?= (int)$inv->getId() ?>" />
                                  <input type="hidden" name="version_number" value="<?= $verNum ?>" />
                                  <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Version <?= $verNum ?>">
                                    <i class="icon-base bx bx-trash"></i>
                                  </button>
                                </form>
                              <?php else: ?>
                                <form method="POST" action="invoices.php" onsubmit="return confirm('Are you sure you want to delete this invoice?');" class="d-inline">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                  <input type="hidden" name="action" value="delete" />
                                  <input type="hidden" name="id" value="<?= (int)$inv->getId() ?>" />
                                  <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Invoice">
                                    <i class="icon-base bx bx-trash"></i>
                                  </button>
                                </form>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
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

    <!-- DataTables & Export Buttons JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <!-- DataTables Initialization -->
    <script>
      $(document).ready(function () {
        $('#invoicesTable').DataTable({
          dom: '<"row mb-3 align-items-center"<"col-md-4"l><"col-md-8 d-flex justify-content-end align-items-center gap-2"fB>>' +
               '<"table-responsive"t>' +
               '<"row mt-3 align-items-center"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>',
          buttons: [
            {
              extend: 'copyHtml5',
              className: 'btn btn-icon-export btn-export-copy me-1',
              text: '<i class="icon-base bx bx-copy"></i>',
              titleAttr: 'Copy to Clipboard',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'csvHtml5',
              className: 'btn btn-icon-export btn-export-csv me-1',
              text: '<i class="icon-base bx bx-file"></i>',
              titleAttr: 'Export as CSV',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'excelHtml5',
              className: 'btn btn-icon-export btn-export-excel me-1',
              text: '<i class="icon-base bx bx-spreadsheet"></i>',
              titleAttr: 'Export as Excel',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'pdfHtml5',
              className: 'btn btn-icon-export btn-export-pdf me-1',
              text: '<i class="icon-base bx bxs-file-pdf"></i>',
              titleAttr: 'Export as PDF',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'print',
              className: 'btn btn-icon-export btn-export-print',
              text: '<i class="icon-base bx bx-printer"></i>',
              titleAttr: 'Print Table',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
            }
          ],
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
          pageLength: 10,
          language: {
            search: "_INPUT_",
            searchPlaceholder: "Search invoice no, customer...",
            lengthMenu: "Display _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ invoice versions",
            paginate: {
              previous: 'Prev',
              next: 'Next'
            }
          },
          order: [[0, 'asc']],
          columnDefs: [
            { type: 'num', targets: 0 },
            { orderable: false, targets: [8] },
            { orderSequence: ['asc', 'desc'], targets: [0, 1, 2, 3, 4, 5, 6, 7] }
          ]
        });
      });
    </script>
  </body>
</html>
