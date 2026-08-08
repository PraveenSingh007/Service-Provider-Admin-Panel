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

$empId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $empId > 0;
$existingEmp = $isEditMode ? $serviceMgmt->getEmployeeById($empId) : null;

$formError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($isEditMode) {
        $result = $controller->update($empId, $_POST, $_FILES, $csrfToken);
    } else {
        $result = $controller->store($_POST, $_FILES, $csrfToken);
    }

    if ($result['response']['success']) {
        header('Location: employees.php');
        exit;
    }

    $errors = (array) ($result['response']['errors'] ?? []);
    $formError = count($errors) > 0 ? implode(', ', $errors) : (string) $result['response']['message'];
}

$generatedEmpCode = 'EMP-' . rand(1000, 9999);
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

    <title><?= $isEditMode ? 'Edit Employee' : 'Add New Employee' ?> - Tech-xpert Admin</title>

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
        $activePage = 'add-employee';
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
                <h4 class="fw-bold py-1 mb-0"><?= $isEditMode ? 'Edit Employee' : 'Add New Employee' ?></h4>
                <a href="employees.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Employees
                </a>
              </div>

              <?php if ($formError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Employee Form Card -->
              <div class="row">
                <div class="col-12">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0"><?= $isEditMode ? 'Employee Details (# ' . $empId . ')' : 'Employee Details' ?></h5>
                    </div>
                    <div class="card-body">
                      <form
                        action="add-employee.php<?= $isEditMode ? '?id=' . $empId : '' ?>"
                        method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                        <div class="row">
                          <!-- Employee Code / ID -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_code">Employee ID / Code <span class="text-danger">*</span></label>
                            <input
                              type="text"
                              class="form-control"
                              id="emp_code"
                              name="emp_code"
                              value="<?= htmlspecialchars((string)($_POST['emp_code'] ?? ($existingEmp !== null ? $existingEmp->getEmpCode() : $generatedEmpCode)), ENT_QUOTES, 'UTF-8') ?>"
                              required />
                          </div>

                          <!-- Employee Name -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_name">Employee Name <span class="text-danger">*</span></label>
                            <input
                              type="text"
                              class="form-control"
                              id="emp_name"
                              name="emp_name"
                              placeholder="e.g. Rahul Sharma"
                              value="<?= htmlspecialchars((string)($_POST['emp_name'] ?? ($existingEmp !== null ? $existingEmp->getEmpName() : '')), ENT_QUOTES, 'UTF-8') ?>"
                              required />
                          </div>
                        </div>

                        <div class="row">
                          <!-- Email -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_email">Employee Email <span class="text-danger">*</span></label>
                            <input
                              type="email"
                              class="form-control"
                              id="emp_email"
                              name="emp_email"
                              placeholder="rahul@example.com"
                              value="<?= htmlspecialchars((string)($_POST['emp_email'] ?? ($existingEmp !== null ? $existingEmp->getEmpEmail() : '')), ENT_QUOTES, 'UTF-8') ?>"
                              required />
                          </div>

                          <!-- Mobile -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_mobile">Mobile Number <span class="text-danger">*</span></label>
                            <input
                              type="text"
                              class="form-control"
                              id="emp_mobile"
                              name="emp_mobile"
                              placeholder="9876543210"
                              value="<?= htmlspecialchars((string)($_POST['emp_mobile'] ?? ($existingEmp !== null ? $existingEmp->getEmpMobile() : '')), ENT_QUOTES, 'UTF-8') ?>"
                              required />
                          </div>
                        </div>

                        <div class="row">
                          <!-- Aadhaar Card Number -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_aadhar">Aadhaar Card No</label>
                            <input
                              type="text"
                              class="form-control"
                              id="emp_aadhar"
                              name="emp_aadhar"
                              placeholder="e.g. 1234 5678 9012"
                              maxlength="16"
                              value="<?= htmlspecialchars((string)($_POST['emp_aadhar'] ?? ($existingEmp !== null ? (string)$existingEmp->getEmpAadhar() : '')), ENT_QUOTES, 'UTF-8') ?>" />
                          </div>

                          <!-- PAN Card Number -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_pan">PAN Card No</label>
                            <input
                              type="text"
                              class="form-control text-uppercase"
                              id="emp_pan"
                              name="emp_pan"
                              placeholder="e.g. ABCDE1234F"
                              maxlength="10"
                              value="<?= htmlspecialchars((string)($_POST['emp_pan'] ?? ($existingEmp !== null ? (string)$existingEmp->getEmpPan() : '')), ENT_QUOTES, 'UTF-8') ?>" />
                          </div>
                        </div>

                        <div class="row">
                          <!-- Role Dropdown -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_role">Employee Role <span class="text-danger">*</span></label>
                            <?php $curEmpRole = (string)($_POST['emp_role'] ?? ($existingEmp !== null ? $existingEmp->getEmpRole() : 'office_staff')); ?>
                            <select class="form-select" id="emp_role" name="emp_role" required>
                              <option value="super_admin" <?= $curEmpRole === 'super_admin' ? 'selected' : '' ?>>Super Administrator (super_admin)</option>
                              <option value="admin" <?= $curEmpRole === 'admin' ? 'selected' : '' ?>>Administrator (admin)</option>
                              <option value="manager" <?= $curEmpRole === 'manager' ? 'selected' : '' ?>>Manager (manager)</option>
                              <option value="office_incharge" <?= $curEmpRole === 'office_incharge' ? 'selected' : '' ?>>Office Incharge (office_incharge)</option>
                              <option value="office_staff" <?= $curEmpRole === 'office_staff' ? 'selected' : '' ?>>Office Staff (office_staff)</option>
                              <option value="site_engineer" <?= $curEmpRole === 'site_engineer' ? 'selected' : '' ?>>Site Engineer (site_engineer)</option>
                            </select>
                          </div>

                          <!-- Base Salary -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_salary">Base Monthly Salary (₹) <span class="text-danger">*</span></label>
                            <input
                              type="number"
                              step="0.01"
                              class="form-control"
                              id="emp_salary"
                              name="emp_salary"
                              placeholder="25000.00"
                              value="<?= htmlspecialchars((string)($_POST['emp_salary'] ?? ($existingEmp !== null ? $existingEmp->getEmpSalary() : '')), ENT_QUOTES, 'UTF-8') ?>"
                              required />
                          </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-4">
                          <label class="form-label" for="emp_address">Address <span class="text-danger">*</span></label>
                          <textarea
                            class="form-control"
                            id="emp_address"
                            name="emp_address"
                            rows="2"
                            placeholder="Full residential or office address"
                            required><?= htmlspecialchars((string)($_POST['emp_address'] ?? ($existingEmp !== null ? $existingEmp->getEmpAddress() : '')), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="row">
                          <!-- Photo Upload -->
                          <div class="col-md-6 mb-4">
                            <label class="form-label" for="emp_photo">Employee Profile Photo / Avatar</label>
                            <input type="file" class="form-control" id="emp_photo" name="emp_photo" accept="image/*" />
                            <?php if ($existingEmp !== null && !empty($existingEmp->getEmpPhoto())): ?>
                              <div class="mt-2">
                                <img
                                  src="<?= htmlspecialchars($existingEmp->getEmpPhoto(), ENT_QUOTES, 'UTF-8') ?>"
                                  alt="Current Photo"
                                  class="rounded-circle"
                                  style="width: 50px; height: 50px; object-fit: cover;" />
                                <small class="text-muted ms-2">Current Photo</small>
                              </div>
                            <?php endif; ?>
                          </div>

                          <!-- Joining Date -->
                          <div class="col-md-3 mb-4">
                            <label class="form-label" for="joining_date">Joining Date <span class="text-danger">*</span></label>
                            <input
                              type="date"
                              class="form-control"
                              id="joining_date"
                              name="joining_date"
                              value="<?= htmlspecialchars((string)($_POST['joining_date'] ?? ($existingEmp !== null ? $existingEmp->getJoiningDate() : date('Y-m-d'))), ENT_QUOTES, 'UTF-8') ?>"
                              required />
                          </div>

                          <!-- Status -->
                          <div class="col-md-3 mb-4">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                              <?php
                              $curStatus = (string)($_POST['status'] ?? ($existingEmp !== null ? $existingEmp->getStatus() : 'active'));
                              ?>
                              <option value="active" <?= $curStatus === 'active' ? 'selected' : '' ?>>Active</option>
                              <option value="inactive" <?= $curStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                              <option value="terminated" <?= $curStatus === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                            </select>
                          </div>

                          <!-- Inactive / Termination Date -->
                          <?php
                          $curStatusChangeDate = (string)($_POST['status_change_date'] ?? ($existingEmp !== null ? (string)$existingEmp->getStatusChangeDate() : date('Y-m-d')));
                          ?>
                          <div class="col-md-3 mb-4" id="statusDateWrapper" style="<?= $curStatus === 'active' ? 'display: none;' : '' ?>">
                            <label class="form-label" for="status_change_date">Inactive / Termination Date <span class="text-danger">*</span></label>
                            <input
                              type="date"
                              class="form-control"
                              id="status_change_date"
                              name="status_change_date"
                              value="<?= htmlspecialchars($curStatusChangeDate, ENT_QUOTES, 'UTF-8') ?>" />
                          </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                          <button type="submit" class="btn btn-primary">
                            <i class="icon-base bx bx-save me-1"></i> <?= $isEditMode ? 'Update Employee' : 'Save Employee' ?>
                          </button>
                          <a href="employees.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                      </form>
                    </div>
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
    <script>
      $(document).ready(function () {
        $('#status').on('change', function () {
          if ($(this).val() === 'active') {
            $('#statusDateWrapper').slideUp();
          } else {
            $('#statusDateWrapper').slideDown();
          }
        });
      });
    </script>
  </body>
</html>
