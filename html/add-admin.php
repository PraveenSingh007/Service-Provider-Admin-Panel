<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/src/Model/User.php';
require_once __DIR__ . '/src/Repository/UserRepository.php';
require_once __DIR__ . '/src/Service/AdminManagementService.php';
require_once __DIR__ . '/src/Controller/AdminController.php';

use App\Controller\AdminController;
use App\Database\DatabaseConnection;
use App\Repository\UserRepository;
use App\Service\AdminManagementService;

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

enforceModulePermission($role, 'admins');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$userRepo = new UserRepository($dbConn);
$serviceMgmt = new AdminManagementService($userRepo);
$controller = new AdminController($serviceMgmt);

$adminId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $adminId > 0;
$existingAdmin = $isEditMode ? $serviceMgmt->getAdminById($adminId) : null;

$formError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($isEditMode) {
        $result = $controller->update($adminId, $_POST, $csrfToken);
    } else {
        $result = $controller->store($_POST, $csrfToken);
    }

    if ($result['response']['success']) {
        header('Location: admins.php');
        exit;
    }

    $errors = (array) ($result['response']['errors'] ?? []);
    $formError = count($errors) > 0 ? implode(', ', $errors) : (string) $result['response']['message'];
}
?>
<!doctype html>

<html
  lang="en"
  class="layout-menu-fixed layout-compact"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?= $isEditMode ? 'Edit Admin' : 'Add New Admin' ?> - Sneat Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Sidebar Menu -->
        <?php
        $activePage = 'add-admin';
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
                      <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
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
                <h4 class="fw-bold py-3 mb-0"><?= $isEditMode ? 'Edit Admin' : 'Add New Admin' ?></h4>
                <a href="admins.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Admins
                </a>
              </div>

              <?php if ($formError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Admin Form Card -->
              <div class="row">
                <div class="col-xl-8 col-lg-10">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0"><?= $isEditMode ? 'Admin Account Details (# ' . $adminId . ')' : 'Admin Account Details' ?></h5>
                    </div>
                    <div class="card-body">
                      <form
                        action="add-admin.php<?= $isEditMode ? '?id=' . $adminId : '' ?>"
                        method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                        <div class="row g-3 mb-4">
                          <div class="col-md-6">
                            <label class="form-label" for="first_name">First Name</label>
                            <input
                              type="text"
                              class="form-control"
                              id="first_name"
                              name="first_name"
                              placeholder="Enter first name"
                              value="<?= htmlspecialchars((string)($_POST['first_name'] ?? ($existingAdmin !== null ? (string)$existingAdmin->getFirstName() : '')), ENT_QUOTES, 'UTF-8') ?>" />
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input
                              type="text"
                              class="form-control"
                              id="last_name"
                              name="last_name"
                              placeholder="Enter last name"
                              value="<?= htmlspecialchars((string)($_POST['last_name'] ?? ($existingAdmin !== null ? (string)$existingAdmin->getLastName() : '')), ENT_QUOTES, 'UTF-8') ?>" />
                          </div>
                        </div>

                        <div class="mb-4">
                          <label class="form-label" for="admin_username">Admin Username / Email <span class="text-danger">*</span></label>
                          <input
                            type="text"
                            class="form-control"
                            id="admin_username"
                            name="admin_username"
                            placeholder="admin@example.com"
                            value="<?= htmlspecialchars((string)($_POST['admin_username'] ?? ($existingAdmin !== null ? $existingAdmin->getUsername() : '')), ENT_QUOTES, 'UTF-8') ?>"
                            required />
                        </div>

                        <div class="mb-4 form-password-toggle">
                          <label class="form-label" for="admin_password">Password <?= $isEditMode ? '<span class="text-muted">(Leave blank to keep current password)</span>' : '<span class="text-danger">*</span>' ?></label>
                          <div class="input-group input-group-merge">
                            <input
                              type="password"
                              id="admin_password"
                              class="form-control"
                              name="admin_password"
                              placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                              <?= $isEditMode ? '' : 'required' ?> />
                            <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                          </div>
                        </div>

                        <div class="mb-4">
                          <label class="form-label" for="admin_role">Admin Role <span class="text-danger">*</span></label>
                          <select class="form-select" id="admin_role" name="admin_role" required>
                            <?php
                            $rawRole = trim((string)($_POST['admin_role'] ?? ($existingAdmin !== null ? $existingAdmin->getRole() : 'admin')));
                            $currentRoleNorm = strtolower(str_replace([' ', '_'], '', $rawRole));

                            $roles = [
                                'admin' => 'Administrator',
                                'super_admin' => 'Super Administrator',
                                'site_engineer' => 'Site Engineer',
                                'office_staff' => 'Office Staff',
                                'office_incharge' => 'Office Incharge',
                                'manager' => 'Manager',
                            ];

                            $matchedKey = null;
                            foreach ($roles as $key => $label) {
                                $keyNorm = strtolower(str_replace([' ', '_'], '', $key));
                                $labelNorm = strtolower(str_replace([' ', '_'], '', $label));
                                if ($currentRoleNorm === $keyNorm || $currentRoleNorm === $labelNorm) {
                                    $matchedKey = $key;
                                    break;
                                }
                            }

                            foreach ($roles as $val => $label):
                                $isSelected = ($matchedKey !== null && $matchedKey === $val);
                            ?>
                              <option value="<?= $val ?>" <?= $isSelected ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>

                            <?php if ($matchedKey === null && $rawRole !== ''): ?>
                              <option value="<?= htmlspecialchars($rawRole, ENT_QUOTES, 'UTF-8') ?>" selected>
                                <?= htmlspecialchars(ucfirst($rawRole), ENT_QUOTES, 'UTF-8') ?>
                              </option>
                            <?php endif; ?>
                          </select>
                        </div>

                        <div class="d-flex gap-2">
                          <button type="submit" class="btn btn-primary">
                            <i class="icon-base bx bx-save me-1"></i> <?= $isEditMode ? 'Update Admin' : 'Save Admin' ?>
                          </button>
                          <a href="admins.php" class="btn btn-outline-secondary">Cancel</a>
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
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
  </body>
</html>
