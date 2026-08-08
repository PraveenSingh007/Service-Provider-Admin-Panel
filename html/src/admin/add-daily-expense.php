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
$empRepo = new EmployeeRepository($dbConn);
$allEmployees = $empRepo->findActive();
$service = new DailyExpenseManagementService($repo);
$controller = new DailyExpenseController($service);

$expenseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$existingExpense = $expenseId > 0 ? $service->getExpenseById($expenseId) : null;
$isEditMode = $existingExpense !== null;

// Validate ownership if site_engineer or office_staff is attempting to edit an existing expense
$normalizedRole = strtolower(str_replace([' ', '-'], '_', trim($role)));
if ($isEditMode && in_array($normalizedRole, ['site_engineer', 'office_staff'], true)) {
    $userEmail = strtolower(trim((string)($user['username'] ?? $user['email'] ?? '')));
    $fullName = strtolower(trim((string)($user['full_name'] ?? '')));
    $loggedInEmpId = null;
    foreach ($allEmployees as $emp) {
        if (strtolower(trim($emp->getEmpEmail())) === $userEmail || (!empty($fullName) && strtolower(trim($emp->getEmpName())) === $fullName)) {
            $loggedInEmpId = $emp->getId();
            break;
        }
    }

    $expCreator = strtolower(trim((string)($existingExpense->getCreatedBy() ?? '')));
    $isCreator = (!empty($username) && $expCreator === strtolower(trim($username))) || (!empty($fullName) && $expCreator === $fullName);
    $isEmpId = $loggedInEmpId !== null && $existingExpense->getEmployeeId() === $loggedInEmpId;

    if (!$isCreator && !$isEmpId) {
        header('Location: daily-expenses.php');
        exit;
    }
}

$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $result = $controller->handleRequest($_POST, $username);
        if ($result['success']) {
            header('Location: daily-expenses.php');
            exit;
        } else {
            $actionError = $result['message'];
        }
    }
}

$categories = [
    'Travel & Fuel',
    'Office Supplies',
    'Equipment & Tools',
    'Food & Refreshments',
    'Maintenance & Repairs',
    'Utility Bills',
    'Staff Allowance',
    'Miscellaneous'
];
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

    <title><?= $isEditMode ? 'Edit Daily Expense' : 'Record Daily Expense' ?> - Tech-xpert Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" />
    <link rel="stylesheet" href="../../../assets/css/demo.min.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.css" />

    <!-- Helpers -->
    <script src="../../../assets/vendor/js/helpers.min.js"></script>
    <script src="../../../assets/js/config.min.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Sidebar Menu -->
        <?php
        $activePage = 'add-daily-expense';
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
                  <?= $isEditMode ? 'Edit Expense Record' : 'Record Daily Expense' ?>
                </h4>
                <a href="daily-expenses.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Expenses
                </a>
              </div>

              <?php if ($actionError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <div class="row">
                <div class="col-12">
                  <div class="card mb-4 p-4">
                    <h5 class="card-title text-primary mb-4">
                      <i class="icon-base bx bx-wallet me-1"></i> Expense Details
                    </h5>

                    <form method="POST" action="add-daily-expense.php<?= $isEditMode ? '?id=' . $expenseId : '' ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="action" value="save" />
                      <?php if ($isEditMode): ?>
                        <input type="hidden" name="id" value="<?= (int)$existingExpense->getId() ?>" />
                      <?php endif; ?>

                      <div class="row g-3">
                        <!-- Expense Type -->
                        <div class="col-md-6">
                          <label class="form-label" for="expense_type">Expense Type / Category <span class="text-danger">*</span></label>
                          <?php $curType = (string)($_POST['expense_type'] ?? ($existingExpense !== null ? $existingExpense->getExpenseType() : '')); ?>
                          <select class="form-select" id="expense_type" name="expense_type" required>
                            <option value="">-- Select Expense Category --</option>
                            <?php foreach ($categories as $cat): ?>
                              <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $curType === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <!-- Expense Amount -->
                        <div class="col-md-6">
                          <label class="form-label" for="amount">Amount (₹) <span class="text-danger">*</span></label>
                          <input
                            type="number"
                            step="0.01"
                            class="form-control"
                            id="amount"
                            name="amount"
                            placeholder="0.00"
                            value="<?= htmlspecialchars((string)($_POST['amount'] ?? ($existingExpense !== null ? number_format($existingExpense->getAmount(), 2, '.', '') : '')), ENT_QUOTES, 'UTF-8') ?>"
                            required />
                        </div>

                        <!-- Expense Date -->
                        <div class="col-md-6">
                          <label class="form-label" for="expense_date">Expense Date <span class="text-danger">*</span></label>
                          <input
                            type="date"
                            class="form-control"
                            id="expense_date"
                            name="expense_date"
                            value="<?= htmlspecialchars((string)($_POST['expense_date'] ?? ($existingExpense !== null ? $existingExpense->getExpenseDate() : date('Y-m-d'))), ENT_QUOTES, 'UTF-8') ?>"
                            required />
                        </div>

                        <!-- Expense Incurred For (Employee List Dropdown) -->
                        <div class="col-md-6">
                          <label class="form-label" for="employee_id">Expense Incurred For (Employee)</label>
                          <?php $curEmpId = (int)($_POST['employee_id'] ?? ($existingExpense !== null ? (int)$existingExpense->getEmployeeId() : 0)); ?>
                          <select class="form-select" id="employee_id" name="employee_id">
                            <option value="">-- General Office / Company Expense --</option>
                            <?php foreach ($allEmployees as $emp): ?>
                              <option value="<?= (int)$emp->getId() ?>" <?= $curEmpId === (int)$emp->getId() ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp->getEmpName(), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($emp->getEmpCode(), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($emp->getEmpRole(), ENT_QUOTES, 'UTF-8') ?>)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <!-- Notes / Description -->
                        <div class="col-md-12">
                          <label class="form-label" for="notes">Notes / Description</label>
                          <textarea
                            class="form-control"
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="Specify details or purpose of this expense..."><?= htmlspecialchars((string)($_POST['notes'] ?? ($existingExpense !== null ? (string)$existingExpense->getNotes() : '')), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                      </div>

                      <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                          <i class="icon-base bx bx-save me-1"></i> <?= $isEditMode ? 'Update Expense' : 'Save Expense' ?>
                        </button>
                        <a href="daily-expenses.php" class="btn btn-outline-secondary">Cancel</a>
                      </div>
                    </form>
                  </div>
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
    <script src="../../../assets/vendor/libs/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.min.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.min.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../../assets/vendor/js/menu.min.js"></script>
    <script src="../../../assets/js/main.min.js"></script>
  </body>
</html>
