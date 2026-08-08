<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Model/Salary.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';
require_once __DIR__ . '/Repository/AttendanceRepository.php';
require_once __DIR__ . '/Repository/SalaryRepository.php';
require_once __DIR__ . '/Service/SalaryManagementService.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\AttendanceRepository;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Repository\SalaryRepository;
use App\Admin\Service\SalaryManagementService;

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
$empRepo = new EmployeeRepository($dbConn);
$attnRepo = new AttendanceRepository($dbConn);
$salaryRepo = new SalaryRepository($dbConn);
$salaryService = new SalaryManagementService($salaryRepo, $empRepo, $attnRepo);

$selectedMonth = isset($_GET['month']) ? trim((string) $_GET['month']) : date('Y-m');
$actionMessage = null;
$actionError = null;

// Handle Generate Salaries Request
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $postMonth = (string) ($_POST['month'] ?? $selectedMonth);
        $res = $salaryService->generateMonthlySalaries($postMonth);
        if ($res['success']) {
            $actionMessage = $res['message'];
            $selectedMonth = $postMonth;
        } else {
            $actionError = $res['message'];
        }
    }
}

// Handle Mark Paid Request
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $salaryId = (int) ($_POST['salary_id'] ?? 0);
        $res = $salaryService->markSalaryPaid($salaryId);
        if ($res['success']) {
            $actionMessage = $res['message'];
        } else {
            $actionError = $res['message'];
        }
    }
}

// Handle Manual Salary Edit Request (Present Days, Leaves, Deductions)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_salary') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $salaryId = (int) ($_POST['salary_id'] ?? 0);
        $presentDays = (int) ($_POST['present_days'] ?? 0);
        $halfDays = (int) ($_POST['half_days'] ?? 0);
        $leaveDays = (int) ($_POST['leave_days'] ?? 0);
        $bonus = (float) ($_POST['bonus'] ?? 0.0);
        $deductions = (float) ($_POST['deductions'] ?? 0.0);
        $notes = !empty($_POST['notes']) ? trim((string)$_POST['notes']) : null;

        $res = $salaryService->updateSalaryRecord($salaryId, $presentDays, $halfDays, $leaveDays, $bonus, $deductions, $notes);
        if ($res['success']) {
            $actionMessage = $res['message'];
        } else {
            $actionError = $res['message'];
        }
    }
}

$salaries = $salaryService->getSalariesByMonth($selectedMonth);
if (empty($salaries)) {
    $salaryService->generateMonthlySalaries($selectedMonth);
    $salaries = $salaryService->getSalariesByMonth($selectedMonth);
}

$employeeMap = [];
foreach ($empRepo->findAll() as $e) {
    if ($e->getId() !== null) {
        $employeeMap[$e->getId()] = $e;
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

    <title>Salaries & Payslips - Tech-xpert Admin</title>

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
    </style>

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
        $activePage = 'salaries';
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
                <h4 class="fw-bold py-1 mb-0">Salary Generation & Payslips</h4>
                <a href="employees.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Employees
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

              <!-- Salary Control Card -->
              <div class="card p-4 mb-4">
                <div class="row align-items-center">
                  <div class="col-md-6">
                    <form method="GET" action="employee-salaries.php" class="d-flex align-items-center gap-2">
                      <label class="form-label mb-0 fw-bold" for="month">Select Month:</label>
                      <input type="month" class="form-control w-auto" id="month" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>" />
                      <button type="submit" class="btn btn-secondary">Load Month</button>
                    </form>
                  </div>
                  <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <?php $allSalariesGenerated = $salaryService->isSalaryGeneratedForAllEligibleEmployees($selectedMonth); ?>
                    <form method="POST" action="employee-salaries.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="action" value="generate" />
                      <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>" />
                      <?php if ($allSalariesGenerated): ?>
                        <button type="submit" class="btn btn-outline-primary" title="Re-calculate and update salaries based on latest attendance">
                          <i class="icon-base bx bx-refresh me-1"></i> Re-Calculate Salaries (<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>)
                        </button>
                      <?php else: ?>
                        <button type="submit" class="btn btn-success">
                          <i class="icon-base bx bx-calculator me-1"></i> Generate Salaries for <?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                      <?php endif; ?>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Salary List Table -->
              <div class="card p-4">
                <div class="table-responsive">
                  <table id="salariesTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>#ID</th>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Base Salary</th>
                        <th>Present / Days</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($salaries as $sal): ?>
                        <?php
                        $empObj = $employeeMap[$sal->getEmployeeId()] ?? null;
                        $empName = $empObj !== null ? $empObj->getEmpName() : ('Employee #' . $sal->getEmployeeId());
                        $empCode = $empObj !== null ? $empObj->getEmpCode() : '';
                        ?>
                        <tr>
                          <td data-order="<?= (int)$sal->getId() ?>"><strong>#<?= (int)$sal->getId() ?></strong></td>
                          <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($empName, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($empCode)): ?>
                              <span class="badge bg-label-info ms-1"><?= htmlspecialchars($empCode, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </td>
                          <td><span class="badge bg-label-secondary"><?= htmlspecialchars($sal->getSalaryMonth(), ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td>₹<?= number_format($sal->getBaseSalary(), 2) ?></td>
                          <td>
                            <span class="text-success fw-bold"><?= $sal->getPresentDays() ?></span> / <?= $sal->getTotalDays() ?> days
                          </td>
                          <td><strong class="text-primary fs-6">₹<?= number_format($sal->getNetSalary(), 2) ?></strong></td>
                          <td>
                            <?php if ($sal->getPaymentStatus() === 'paid'): ?>
                              <span class="badge bg-label-success">PAID</span>
                            <?php else: ?>
                              <span class="badge bg-label-warning">PENDING</span>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars($sal->getPaymentDate() ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                          <td>
                            <div class="d-flex align-items-center gap-1">
                              <!-- Edit Salary Details Button -->
                              <button
                                type="button"
                                class="btn btn-sm btn-outline-warning"
                                title="Edit Days Present, Leaves & Deductions"
                                onclick="openEditSalaryModal(<?= (int)$sal->getId() ?>, '<?= htmlspecialchars($empName, ENT_QUOTES, 'UTF-8') ?>', <?= $sal->getPresentDays() ?>, <?= $sal->getHalfDays() ?>, <?= $sal->getLeaveDays() ?>, <?= $sal->getAbsentDays() ?>, <?= $sal->getTotalDays() ?>, <?= $sal->getBaseSalary() ?>, <?= $sal->getBonus() ?>, <?= $sal->getDeductions() ?>, '<?= htmlspecialchars((string)($sal->getNotes() ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="icon-base bx bx-edit-alt"></i>
                              </button>
                              <a href="employee-payslip.php?id=<?= (int)$sal->getId() ?>" target="_blank" class="btn btn-sm btn-outline-info" title="View & Print Payslip">
                                <i class="icon-base bx bx-file"></i> Payslip
                              </a>
                              <?php if ($sal->getPaymentStatus() !== 'paid'): ?>
                                <form method="POST" action="employee-salaries.php" onsubmit="return confirm('Mark this salary as PAID?');">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                  <input type="hidden" name="action" value="mark_paid" />
                                  <input type="hidden" name="salary_id" value="<?= (int)$sal->getId() ?>" />
                                  <button type="submit" class="btn btn-sm btn-success">
                                    <i class="icon-base bx bx-check-circle"></i> Paid
                                  </button>
                                </form>
                              <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary disabled"><i class="icon-base bx bx-check"></i> Paid</span>
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

    <!-- Edit Salary Modal -->
    <div class="modal fade" id="editSalaryModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title text-white fw-bold"><i class="icon-base bx bx-edit me-1"></i> Edit Salary & Attendance Days</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="employee-salaries.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="edit_salary" />
            <input type="hidden" name="salary_id" id="edit_salary_id" value="" />

            <div class="modal-body p-4">
              <div class="alert alert-light border mb-3">
                <strong id="edit_emp_name" class="text-dark fs-6">Employee Name</strong>
                <div class="text-muted small">Total Working Days in Month: <strong id="edit_total_days" class="text-dark">0</strong> | Base Monthly Salary: <strong id="edit_base_salary" class="text-dark">₹0.00</strong></div>
              </div>

              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label fw-semibold" for="edit_present_days">Days Present <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="edit_present_days" name="present_days" min="0" max="31" required />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" for="edit_leave_days">Paid Leaves</label>
                  <input type="number" class="form-control" id="edit_leave_days" name="leave_days" min="0" max="31" value="0" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" for="edit_half_days">Half Days</label>
                  <input type="number" class="form-control" id="edit_half_days" name="half_days" min="0" max="31" value="0" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold text-danger">Unpaid Absences</label>
                  <input type="number" class="form-control bg-light" id="edit_absent_days" readonly />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" for="edit_bonus">Bonus (₹)</label>
                  <input type="number" step="0.01" class="form-control" id="edit_bonus" name="bonus" min="0" value="0.00" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold text-danger" for="edit_deductions">Leave Deductions (₹)</label>
                  <input type="number" step="0.01" class="form-control" id="edit_deductions" name="deductions" min="0" value="0.00" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" for="edit_notes">Remarks / Notes</label>
                  <textarea class="form-control" id="edit_notes" name="notes" rows="2" placeholder="Specify reason for manual adjustment..."></textarea>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-warning fw-bold"><i class="icon-base bx bx-check me-1"></i> Update & Recalculate Salary</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Core JS -->
    <script src="../../../assets/vendor/libs/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.min.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.min.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../../assets/vendor/js/menu.min.js"></script>
    <script src="../../../assets/js/main.min.js"></script>

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
        $('#salariesTable').DataTable({
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
            searchPlaceholder: "Search employee, month...",
            lengthMenu: "Display _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ salary records",
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

      function openEditSalaryModal(id, name, present, half, leave, absent, totalDays, baseSalary, bonus, deductions, notes) {
        $('#edit_salary_id').val(id);
        $('#edit_emp_name').text(name);
        $('#edit_total_days').text(totalDays);
        $('#edit_base_salary').text('₹' + parseFloat(baseSalary).toFixed(2));

        $('#edit_present_days').val(present).attr('max', totalDays);
        $('#edit_leave_days').val(leave).attr('max', totalDays);
        $('#edit_half_days').val(half).attr('max', totalDays);
        $('#edit_absent_days').val(absent);
        $('#edit_bonus').val(parseFloat(bonus).toFixed(2));
        $('#edit_deductions').val(parseFloat(deductions).toFixed(2));
        $('#edit_notes').val(notes);

        $('#editSalaryModal').modal('show');
      }

      $(document).ready(function() {
        $('#edit_present_days, #edit_leave_days, #edit_half_days').on('input', function() {
          let totalDays = parseInt($('#edit_total_days').text()) || 0;
          let present = parseInt($('#edit_present_days').val()) || 0;
          let leave = parseInt($('#edit_leave_days').val()) || 0;
          let half = parseInt($('#edit_half_days').val()) || 0;

          let absent = Math.max(0, totalDays - (present + leave + half));
          $('#edit_absent_days').val(absent);
        });
      });
    </script>
  </body>
</html>
