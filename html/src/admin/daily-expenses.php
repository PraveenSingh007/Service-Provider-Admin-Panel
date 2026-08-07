<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Model/DailyExpense.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';
require_once __DIR__ . '/Repository/DailyExpenseRepository.php';
require_once __DIR__ . '/Service/DailyExpenseManagementService.php';
require_once __DIR__ . '/Controller/DailyExpenseController.php';

use App\Admin\Controller\DailyExpenseController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\DailyExpenseRepository;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Service\DailyExpenseManagementService;

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
$repo = new DailyExpenseRepository($dbConn);
$service = new DailyExpenseManagementService($repo);
$controller = new DailyExpenseController($service);

$startDate = isset($_GET['start_date']) ? trim((string)$_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? trim((string)$_GET['end_date']) : date('Y-m-d');

$actionMessage = null;
$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $result = $controller->handleRequest($_POST, $username);
        if ($result['success']) {
            $actionMessage = $result['message'];
        } else {
            $actionError = $result['message'];
        }
    }
}

$expenses = $service->getExpensesByDateRange($startDate, $endDate);

// Restrict Site Engineer and Office Staff to view ONLY their own expenses
$normalizedRole = strtolower(str_replace([' ', '-'], '_', trim($role)));
if (in_array($normalizedRole, ['site_engineer', 'office_staff'], true)) {
    $empRepo = new EmployeeRepository($dbConn);
    $allEmployees = $empRepo->findAll();
    $loggedInEmpId = null;
    $userEmail = strtolower(trim((string)($user['username'] ?? $user['email'] ?? '')));
    $fullName = strtolower(trim((string)($user['full_name'] ?? '')));

    foreach ($allEmployees as $emp) {
        if (strtolower(trim($emp->getEmpEmail())) === $userEmail || (!empty($fullName) && strtolower(trim($emp->getEmpName())) === $fullName)) {
            $loggedInEmpId = $emp->getId();
            break;
        }
    }

    $filteredExpenses = [];
    foreach ($expenses as $exp) {
        $expCreator = strtolower(trim((string)($exp->getCreatedBy() ?? '')));
        $isCreator = (!empty($username) && $expCreator === strtolower(trim($username)))
            || (!empty($fullName) && $expCreator === $fullName);
        $isEmpId = $loggedInEmpId !== null && $exp->getEmployeeId() === $loggedInEmpId;

        if ($isCreator || $isEmpId) {
            $filteredExpenses[] = $exp;
        }
    }
    $expenses = $filteredExpenses;
}

$totalExpenseAmount = 0.0;
foreach ($expenses as $exp) {
    $totalExpenseAmount += $exp->getAmount();
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

    <title>Daily Expenses - tech-xpert Admin</title>

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
      .btn-export-csv { background-color: #696cff !important; color: #ffffff !important; border: none !important; }
      .btn-export-excel { background-color: #71dd37 !important; color: #ffffff !important; border: none !important; }
      .btn-export-pdf { background-color: #ff3e1d !important; color: #ffffff !important; border: none !important; }
      .btn-export-print { background-color: #03c3ec !important; color: #ffffff !important; border: none !important; }
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
        $activePage = 'daily-expenses';
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
                <h4 class="fw-bold py-1 mb-0">Daily Expenses Tracker</h4>
                <a href="add-daily-expense.php" class="btn btn-primary">
                  <i class="icon-base bx bx-plus me-1"></i> Record New Expense
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

              <!-- Date Range Search Filter Card -->
              <div class="card p-4 mb-4">
                <form method="GET" action="daily-expenses.php" class="row g-3 align-items-end">
                  <div class="col-md-4">
                    <label class="form-label fw-bold" for="start_date"><i class="icon-base bx bx-calendar me-1"></i> From Date:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>" required />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-bold" for="end_date"><i class="icon-base bx bx-calendar me-1"></i> To Date:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>" required />
                  </div>
                  <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                      <i class="icon-base bx bx-search me-1"></i> Search Expenses
                    </button>
                    <a href="daily-expenses.php" class="btn btn-outline-secondary" title="Reset to Current Month">Reset</a>
                  </div>
                </form>
              </div>

              <!-- Summary Card -->
              <div class="row mb-4">
                <div class="col-md-4">
                  <div class="card p-3 bg-label-primary border-0 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <small class="text-uppercase fw-semibold">Expenses Total (<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>)</small>
                        <h3 class="fw-bold text-primary mb-0 mt-1">₹<?= number_format($totalExpenseAmount, 2) ?></h3>
                      </div>
                      <div class="avatar avatar-md bg-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="icon-base bx bx-wallet text-white icon-lg"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Expenses DataTable Card -->
              <div class="card p-4">
                <div class="table-responsive">
                  <table id="expensesTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>#ID</th>
                        <th>Expense Type</th>
                        <th>Incurred For</th>
                        <th>Amount (₹)</th>
                        <th>Date</th>
                        <th>Notes / Description</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($expenses as $exp): ?>
                        <tr>
                          <td data-order="<?= (int)$exp->getId() ?>"><strong>#<?= (int)$exp->getId() ?></strong></td>
                          <td><span class="badge bg-label-info fs-6"><?= htmlspecialchars($exp->getExpenseType(), ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td>
                            <?php if (!empty($exp->getEmployeeName())): ?>
                              <span class="badge bg-label-success"><i class="icon-base bx bx-user me-1"></i><?= htmlspecialchars($exp->getEmployeeName(), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                              <small class="text-muted">Company General</small>
                            <?php endif; ?>
                          </td>
                          <td><strong class="text-danger fs-6">₹<?= number_format($exp->getAmount(), 2) ?></strong></td>
                          <td><?= htmlspecialchars($exp->getExpenseDate(), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)($exp->getNotes() ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><small class="text-muted"><?= htmlspecialchars((string)($exp->getCreatedBy() ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></small></td>
                          <td>
                            <div class="d-flex align-items-center gap-1">
                              <!-- Edit Expense -->
                              <a href="add-daily-expense.php?id=<?= (int)$exp->getId() ?>" class="btn btn-sm btn-icon btn-outline-primary" title="Edit Expense">
                                <i class="icon-base bx bx-edit-alt"></i>
                              </a>

                              <!-- Delete Expense -->
                              <form method="POST" action="daily-expenses.php" onsubmit="return confirm('Are you sure you want to delete this expense record?');" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= (int)$exp->getId() ?>" />
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Expense">
                                  <i class="icon-base bx bx-trash"></i>
                                </button>
                              </form>
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
        $('#expensesTable').DataTable({
          dom: '<"row mb-3 align-items-center"<"col-md-4"l><"col-md-8 d-flex justify-content-end align-items-center gap-2"fB>>' +
               '<"table-responsive"t>' +
               '<"row mt-3 align-items-center"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>',
          buttons: [
            {
              extend: 'copyHtml5',
              className: 'btn btn-icon-export btn-export-copy me-1',
              text: '<i class="icon-base bx bx-copy"></i>',
              titleAttr: 'Copy to Clipboard',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            },
            {
              extend: 'csvHtml5',
              className: 'btn btn-icon-export btn-export-csv me-1',
              text: '<i class="icon-base bx bx-file"></i>',
              titleAttr: 'Export as CSV',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            },
            {
              extend: 'excelHtml5',
              className: 'btn btn-icon-export btn-export-excel me-1',
              text: '<i class="icon-base bx bx-spreadsheet"></i>',
              titleAttr: 'Export as Excel',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            },
            {
              extend: 'pdfHtml5',
              className: 'btn btn-icon-export btn-export-pdf me-1',
              text: '<i class="icon-base bx bxs-file-pdf"></i>',
              titleAttr: 'Export as PDF',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            },
            {
              extend: 'print',
              className: 'btn btn-icon-export btn-export-print',
              text: '<i class="icon-base bx bx-printer"></i>',
              titleAttr: 'Print Table',
              exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            }
          ],
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
          pageLength: 10,
          language: {
            search: "_INPUT_",
            searchPlaceholder: "Search expense type, notes...",
            lengthMenu: "Display _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ expenses",
            paginate: {
              previous: 'Prev',
              next: 'Next'
            }
          },
          order: [[0, 'asc']],
          columnDefs: [
            { type: 'num', targets: 0 },
            { orderable: false, targets: [6] },
            { orderSequence: ['asc', 'desc'], targets: [0, 1, 2, 3, 4, 5] }
          ]
        });
      });
    </script>
  </body>
</html>
