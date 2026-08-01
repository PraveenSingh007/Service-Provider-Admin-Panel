<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';
require_once __DIR__ . '/Service/EmployeeManagementService.php';
require_once __DIR__ . '/Controller/EmployeeController.php';

use App\Admin\Controller\EmployeeController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Service\EmployeeManagementService;

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
$repository = new EmployeeRepository($dbConn);
$serviceMgmt = new EmployeeManagementService($repository);
$controller = new EmployeeController($serviceMgmt);

$actionMessage = null;
$actionError = null;

// Handle Delete Request
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $empId = (int) ($_POST['emp_id'] ?? 0);
    $result = $controller->destroy($empId, $_POST, $csrfToken);
    if ($result['response']['success']) {
        $actionMessage = (string) $result['response']['message'];
    } else {
        $errors = (array) ($result['response']['errors'] ?? []);
        $actionError = count($errors) > 0 ? implode(', ', $errors) : (string) $result['response']['message'];
    }
}

$empResult = $controller->index();
$employees = (array) ($empResult['response']['data']['employees'] ?? []);
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

    <title>Employees Management - tech-xpert Admin</title>

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
      .btn-export-copy {
        background-color: #8592a3 !important;
        color: #ffffff !important;
        border: none !important;
      }
      .btn-export-copy:hover {
        background-color: #788393 !important;
        color: #ffffff !important;
      }
      .btn-export-csv {
        background-color: #696cff !important;
        color: #ffffff !important;
        border: none !important;
      }
      .btn-export-csv:hover {
        background-color: #5f61e6 !important;
        color: #ffffff !important;
      }
      .btn-export-excel {
        background-color: #71dd37 !important;
        color: #ffffff !important;
        border: none !important;
      }
      .btn-export-excel:hover {
        background-color: #64c431 !important;
        color: #ffffff !important;
      }
      .btn-export-pdf {
        background-color: #ff3e1d !important;
        color: #ffffff !important;
        border: none !important;
      }
      .btn-export-pdf:hover {
        background-color: #e6381a !important;
        color: #ffffff !important;
      }
      .btn-export-print {
        background-color: #03c3ec !important;
        color: #ffffff !important;
        border: none !important;
      }
      .btn-export-print:hover {
        background-color: #03afd4 !important;
        color: #ffffff !important;
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
        $activePage = 'employees';
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
                      <a class="dropdown-item" href="edit-profile.php">
                        <i class="icon-base bx bx-user me-3"></i><span>Edit Profile</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="change-password.php">
                        <i class="icon-base bx bx-key me-3"></i><span>Change Password</span>
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
                <h4 class="fw-bold py-3 mb-0">Employees List</h4>
                <div class="d-flex gap-2">
                  <a href="employee-attendance.php" class="btn btn-outline-primary">
                    <i class="icon-base bx bx-calendar-check me-1"></i> Attendance
                  </a>
                  <a href="employee-salaries.php" class="btn btn-outline-success">
                    <i class="icon-base bx bx-dollar-circle me-1"></i> Salaries
                  </a>
                  <a href="add-employee.php" class="btn btn-primary">
                    <i class="icon-base bx bx-plus me-1"></i> Add Employee
                  </a>
                </div>
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

              <!-- Employees DataTable Card -->
              <div class="card p-4">
                <div class="table-responsive">
                  <table id="employeesTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>#ID</th>
                        <th>Photo</th>
                        <th>Emp Code</th>
                        <th>Employee Name</th>
                        <th>Role</th>
                        <th>Mobile / Email</th>
                        <th>Base Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($employees as $emp): ?>
                        <tr>
                          <td data-order="<?= (int)$emp['id'] ?>"><strong>#<?= (int)$emp['id'] ?></strong></td>
                          <td>
                            <?php if (!empty($emp['emp_photo'])): ?>
                              <img
                                src="<?= htmlspecialchars((string)$emp['emp_photo'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars((string)$emp['emp_name'], ENT_QUOTES, 'UTF-8') ?>"
                                class="rounded-circle"
                                style="width: 42px; height: 42px; object-fit: cover;" />
                            <?php else: ?>
                              <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded-circle bg-label-primary"><?= htmlspecialchars(strtoupper(substr((string)$emp['emp_name'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></span>
                              </div>
                            <?php endif; ?>
                          </td>
                          <td><span class="badge bg-label-info"><?= htmlspecialchars((string)$emp['emp_code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars((string)$emp['emp_name'], ENT_QUOTES, 'UTF-8') ?></span>
                          </td>
                          <td><span class="badge bg-label-secondary"><?= htmlspecialchars((string)$emp['emp_role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td>
                            <div><i class="icon-base bx bx-phone text-muted me-1"></i><?= htmlspecialchars((string)$emp['emp_mobile'], ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted d-block"><?= htmlspecialchars((string)$emp['emp_email'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php if (!empty($emp['emp_aadhar']) || !empty($emp['emp_pan'])): ?>
                              <div class="mt-1" style="font-size: 11px;">
                                <?php if (!empty($emp['emp_aadhar'])): ?>
                                  <span class="badge bg-label-primary py-1 px-2 me-1" title="Aadhaar Card No">Aadhaar: <?= htmlspecialchars((string)$emp['emp_aadhar'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <?php if (!empty($emp['emp_pan'])): ?>
                                  <span class="badge bg-label-warning py-1 px-2" title="PAN Card No">PAN: <?= htmlspecialchars((string)$emp['emp_pan'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                          </td>
                          <td><strong class="text-success">₹<?= number_format((float)$emp['emp_salary'], 2) ?></strong></td>
                          <td>
                            <?php if ($emp['status'] === 'active'): ?>
                              <span class="badge bg-label-success">Active</span>
                            <?php else: ?>
                              <span class="badge bg-label-danger"><?= htmlspecialchars(ucfirst((string)$emp['status']), ENT_QUOTES, 'UTF-8') ?></span>
                              <?php if (!empty($emp['status_change_date'])): ?>
                                <small class="text-muted d-block" style="font-size: 11px;">since <?= htmlspecialchars((string)$emp['status_change_date'], ENT_QUOTES, 'UTF-8') ?></small>
                              <?php endif; ?>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="d-flex align-items-center gap-1">
                              <!-- ID Card Generation Button -->
                              <a
                                href="employee-id-card.php?id=<?= htmlspecialchars((string)$emp['id'], ENT_QUOTES, 'UTF-8') ?>"
                                target="_blank"
                                class="btn btn-sm btn-outline-info"
                                title="Print ID Card">
                                <i class="icon-base bx bx-id-card"></i>
                              </a>
                              <!-- Edit Button -->
                              <a
                                href="add-employee.php?id=<?= htmlspecialchars((string)$emp['id'], ENT_QUOTES, 'UTF-8') ?>"
                                class="btn btn-sm btn-outline-primary"
                                title="Edit Employee">
                                <i class="icon-base bx bx-edit-alt"></i>
                              </a>
                              <!-- Delete Button -->
                              <form method="POST" action="employees.php" onsubmit="return confirm('Are you sure you want to delete this employee?');" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="emp_id" value="<?= htmlspecialchars((string)$emp['id'], ENT_QUOTES, 'UTF-8') ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Employee">
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
        $('#employeesTable').DataTable({
          dom: '<"row mb-3 align-items-center"<"col-md-4"l><"col-md-8 d-flex justify-content-end align-items-center gap-2"fB>>' +
               '<"table-responsive"t>' +
               '<"row mt-3 align-items-center"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>',
          buttons: [
            {
              extend: 'copyHtml5',
              className: 'btn btn-icon-export btn-export-copy me-1',
              text: '<i class="icon-base bx bx-copy"></i>',
              titleAttr: 'Copy to Clipboard',
              exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'csvHtml5',
              className: 'btn btn-icon-export btn-export-csv me-1',
              text: '<i class="icon-base bx bx-file"></i>',
              titleAttr: 'Export as CSV',
              exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'excelHtml5',
              className: 'btn btn-icon-export btn-export-excel me-1',
              text: '<i class="icon-base bx bx-spreadsheet"></i>',
              titleAttr: 'Export as Excel',
              exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'pdfHtml5',
              className: 'btn btn-icon-export btn-export-pdf me-1',
              text: '<i class="icon-base bx bxs-file-pdf"></i>',
              titleAttr: 'Export as PDF',
              exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'print',
              className: 'btn btn-icon-export btn-export-print',
              text: '<i class="icon-base bx bx-printer"></i>',
              titleAttr: 'Print Table',
              exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] }
            }
          ],
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
          pageLength: 10,
          language: {
            search: "_INPUT_",
            searchPlaceholder: "Search employee code, name, role...",
            lengthMenu: "Display _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ employees",
            paginate: {
              previous: 'Prev',
              next: 'Next'
            }
          },
          order: [[0, 'asc']],
          columnDefs: [
            { type: 'num', targets: 0 },
            { orderable: false, targets: [1, 8] },
            { orderSequence: ['asc', 'desc'], targets: [0, 2, 3, 4, 5, 6, 7] }
          ]
        });
      });
    </script>
  </body>
</html>
