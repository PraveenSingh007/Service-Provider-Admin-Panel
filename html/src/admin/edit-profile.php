<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/Model/User.php';
require_once __DIR__ . '/Repository/UserRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\UserRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = (array) $_SESSION['user'];
$userId = (int) ($user['id'] ?? 0);
$username = (string) ($user['username'] ?? 'Admin');
$role = (string) ($user['role'] ?? 'admin');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$userRepo = new UserRepository($dbConn);
$currentUserModel = $userId > 0 ? $userRepo->findById($userId) : null;

$actionMessage = null;
$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $newUsername = trim((string) ($_POST['username'] ?? ''));
        $isSuperAdmin = hasModulePermission($role, 'admins');
        $targetRole = ($isSuperAdmin && !empty($_POST['role'])) ? trim((string) $_POST['role']) : $role;

        if (empty($newUsername)) {
            $actionError = 'Username / Email cannot be empty.';
        } else {
            // Update user record in database
            if ($userRepo->update($userId, $firstName, $lastName, $newUsername, null, $targetRole)) {
                // Update session variables
                $_SESSION['user']['username'] = $newUsername;
                $_SESSION['user']['first_name'] = $firstName;
                $_SESSION['user']['last_name'] = $lastName;
                $_SESSION['user']['role'] = $targetRole;
                $username = $newUsername;
                $role = $targetRole;
                $actionMessage = 'Your profile details and role have been updated successfully!';
                $currentUserModel = $userRepo->findById($userId);
            } else {
                $actionError = 'Failed to update profile details in database.';
            }
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

    <title>Edit Profile - Tech-xpert Admin</title>

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
        $activePage = 'edit-profile';
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
                            <small class="text-body-secondary"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role)), ENT_QUOTES, 'UTF-8') ?></small>
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
                        <i class="icon-base bx bx-power-off me-3"></i><span>Log Out</span>
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
                <h4 class="fw-bold py-1 mb-0">Edit Profile</h4>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Dashboard
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

              <div class="row">
                <div class="col-12">
                  <div class="card mb-4 p-4">
                    <!-- User Avatar Header Card -->
                    <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-label-primary rounded">
                      <div class="avatar avatar-xl me-2">
                        <img src="../../../assets/img/avatars/1.png" alt="User Avatar" class="w-px-60 h-auto rounded-circle shadow-sm" />
                      </div>
                      <div>
                        <h5 class="mb-1 text-primary fw-bold"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h5>
                        <span class="badge bg-primary fs-6"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role)), ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                    </div>

                    <h5 class="card-title text-primary mb-4">
                      <i class="icon-base bx bx-user me-1"></i> Update Profile Information
                    </h5>

                    <form method="POST" action="edit-profile.php">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                      <?php
                      $curFirstName = $currentUserModel !== null ? (string)$currentUserModel->getFirstName() : '';
                      $curLastName = $currentUserModel !== null ? (string)$currentUserModel->getLastName() : '';
                      ?>
                      <div class="row g-3 mb-3">
                        <div class="col-md-6">
                          <label class="form-label" for="first_name">First Name</label>
                          <input
                            type="text"
                            class="form-control"
                            id="first_name"
                            name="first_name"
                            placeholder="Enter first name"
                            value="<?= htmlspecialchars($curFirstName, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="last_name">Last Name</label>
                          <input
                            type="text"
                            class="form-control"
                            id="last_name"
                            name="last_name"
                            placeholder="Enter last name"
                            value="<?= htmlspecialchars($curLastName, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label" for="username">Username / Email <span class="text-danger">*</span></label>
                        <input
                          type="text"
                          class="form-control"
                          id="username"
                          name="username"
                          value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                          required />
                      </div>

                      <div class="mb-3">
                        <label class="form-label" for="role">System Role</label>
                        <?php if (hasModulePermission($role, 'admins')): ?>
                          <select class="form-select" id="role" name="role">
                            <?php
                            $allRoles = [
                                'super_admin' => 'Super Administrator',
                                'admin' => 'Administrator',
                                'manager' => 'Manager',
                                'office_incharge' => 'Office Incharge',
                                'office_staff' => 'Office Staff',
                                'site_engineer' => 'Site Engineer',
                            ];
                            $normalizedCurrentRole = strtolower(str_replace([' ', '-'], '_', $role));
                            foreach ($allRoles as $key => $label):
                                $isSelected = ($normalizedCurrentRole === $key);
                            ?>
                              <option value="<?= $key ?>" <?= $isSelected ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                          </select>
                          <small class="text-muted">As a Super Administrator, you can modify user role.</small>
                        <?php else: ?>
                          <input
                            type="text"
                            class="form-control bg-light"
                            value="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role)), ENT_QUOTES, 'UTF-8') ?>"
                            disabled />
                          <small class="text-muted">Role permissions are managed by Super Administrator.</small>
                        <?php endif; ?>
                      </div>

                      <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                          <i class="icon-base bx bx-save me-1"></i> Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
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
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../../assets/vendor/js/menu.js"></script>
    <script src="../../../assets/js/main.js"></script>
  </body>
</html>
