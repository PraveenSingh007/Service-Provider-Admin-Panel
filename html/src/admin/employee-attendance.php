<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Model/Attendance.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';
require_once __DIR__ . '/Repository/AttendanceRepository.php';
require_once __DIR__ . '/Service/AttendanceManagementService.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\AttendanceRepository;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Service\AttendanceManagementService;

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
$attnService = new AttendanceManagementService($attnRepo, $empRepo);

$selectedDate = isset($_GET['date']) ? trim((string) $_GET['date']) : date('Y-m-d');
$actionMessage = null;
$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $postDate = (string) ($_POST['attendance_date'] ?? $selectedDate);
        $attnData = (array) ($_POST['attendance'] ?? []);
        $checkInData = (array) ($_POST['check_in'] ?? []);
        $checkOutData = (array) ($_POST['check_out'] ?? []);
        $notesData = (array) ($_POST['notes'] ?? []);

        $res = $attnService->markBulkAttendance($postDate, $attnData, $checkInData, $checkOutData, $notesData);
        if ($res['success']) {
            $actionMessage = $res['message'];
            $selectedDate = $postDate;
        } else {
            $actionError = $res['message'];
        }
    }
}

$employees = $empRepo->findAll();
$normalizedRole = strtolower(str_replace([' ', '-'], '_', trim($role)));
if ($normalizedRole === 'site_engineer') {
    $userEmail = strtolower(trim((string)($user['username'] ?? $user['email'] ?? '')));
    $selfList = [];
    foreach ($employees as $emp) {
        if (strtolower(trim($emp->getEmpEmail())) === $userEmail || strtolower(trim($emp->getEmpName())) === strtolower(trim((string)($user['full_name'] ?? '')))) {
            $selfList[] = $emp;
            break;
        }
    }
    if (empty($selfList)) {
        foreach ($employees as $emp) {
            if (strtolower(trim($emp->getEmpRole())) === 'site engineer') {
                $selfList[] = $emp;
                break;
            }
        }
    }
    if (!empty($selfList)) {
        $employees = $selfList;
    }
}

$existingAttn = $attnService->getAttendanceByDate($selectedDate);
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

    <title>Employee Attendance - Tech-xpert Admin</title>

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
        $activePage = 'attendance';
        require __DIR__ . '/sidebar.php';
        ?>
        <!-- / Sidebar Menu -->

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
                <h4 class="fw-bold py-1 mb-0">Daily Attendance Tracker</h4>
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

              <!-- Attendance Card -->
              <div class="card p-4">
                <form method="GET" action="employee-attendance.php" class="row align-items-center mb-4">
                  <div class="col-md-4">
                    <label class="form-label fw-bold" for="date">Select Date:</label>
                    <div class="input-group">
                      <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>" />
                      <button type="submit" class="btn btn-primary">Load Date</button>
                    </div>
                  </div>
                </form>

                <form method="POST" action="employee-attendance.php">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                  <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>" />

                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Emp Code</th>
                          <th>Employee Name</th>
                          <th>Role</th>
                          <th>Attendance Status</th>
                          <th>Check-In Time</th>
                          <th>Check-Out Time</th>
                          <th>Notes</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($employees as $emp): ?>
                          <?php
                          if (!$attnService->isEligibleForAttendance($emp, $selectedDate)) {
                              continue;
                          }
                          $empId = (int) $emp->getId();
                          $curStatus = isset($existingAttn[$empId]) ? $existingAttn[$empId]->getStatus() : 'present';
                          $curCheckIn = isset($existingAttn[$empId]) ? (string)$existingAttn[$empId]->getCheckInTime() : '';
                          $curCheckOut = isset($existingAttn[$empId]) ? (string)$existingAttn[$empId]->getCheckOutTime() : '';
                          $curNotes = isset($existingAttn[$empId]) ? (string)$existingAttn[$empId]->getNotes() : '';
                          ?>
                          <tr>
                            <td><span class="badge bg-label-info"><?= htmlspecialchars($emp->getEmpCode(), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                              <span class="fw-semibold text-dark"><?= htmlspecialchars($emp->getEmpName(), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td><?= htmlspecialchars($emp->getEmpRole(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                              <div class="btn-group" role="group" aria-label="Attendance status">
                                <input type="radio" class="btn-check" name="attendance[<?= $empId ?>]" id="pres_<?= $empId ?>" value="present" <?= $curStatus === 'present' ? 'checked' : '' ?> />
                                <label class="btn btn-outline-success btn-sm" for="pres_<?= $empId ?>">Present</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $empId ?>]" id="abs_<?= $empId ?>" value="absent" <?= $curStatus === 'absent' ? 'checked' : '' ?> />
                                <label class="btn btn-outline-danger btn-sm" for="abs_<?= $empId ?>">Absent</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $empId ?>]" id="half_<?= $empId ?>" value="half_day" <?= $curStatus === 'half_day' ? 'checked' : '' ?> />
                                <label class="btn btn-outline-warning btn-sm" for="half_<?= $empId ?>">Half Day</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $empId ?>]" id="leave_<?= $empId ?>" value="leave" <?= $curStatus === 'leave' ? 'checked' : '' ?> />
                                <label class="btn btn-outline-info btn-sm" for="leave_<?= $empId ?>">Leave</label>
                              </div>
                            </td>
                            <td>
                              <input type="time" class="form-control form-control-sm" name="check_in[<?= $empId ?>]" value="<?= htmlspecialchars($curCheckIn, ENT_QUOTES, 'UTF-8') ?>" />
                            </td>
                            <td>
                              <input type="time" class="form-control form-control-sm" name="check_out[<?= $empId ?>]" value="<?= htmlspecialchars($curCheckOut, ENT_QUOTES, 'UTF-8') ?>" />
                            </td>
                            <td>
                              <input type="text" class="form-control form-control-sm" name="notes[<?= $empId ?>]" value="<?= htmlspecialchars($curNotes, ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional notes..." />
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-4">
                    <button type="submit" class="btn btn-success px-4">
                      <i class="icon-base bx bx-check me-1"></i> Save Attendance for <?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                  </div>
                </form>
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
