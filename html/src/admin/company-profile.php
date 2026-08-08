<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/Model/Company.php';
require_once __DIR__ . '/Repository/CompanyRepository.php';
require_once __DIR__ . '/Service/CompanyManagementService.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\CompanyRepository;
use App\Admin\Service\CompanyManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$role = (string) ($user['role'] ?? 'admin');
$username = (string) ($user['username'] ?? 'Admin');

enforceModulePermission($role, 'company_profile');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$companyRepo = new CompanyRepository($dbConn);
$service = new CompanyManagementService($companyRepo);

$actionMessage = null;
$actionError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $actionError = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $result = $service->updateCompanyProfile($_POST);
        if ($result['success']) {
            $actionMessage = $result['message'];
        } else {
            $actionError = implode(', ', $result['errors']);
        }
    }
}

$company = $service->getCompanyProfile();
?>
<!doctype html>
<html
  lang="en"
  class="layout-menu-fixed layout-compact"
  data-assets-path="../../../assets/">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Company Profile - Tech-xpert Admin</title>

    <link rel="icon" type="image/x-icon" href="../../../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />
    <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" />
    <link rel="stylesheet" href="../../../assets/css/demo.min.css" />
    <link rel="stylesheet" href="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.css" />
    <script src="../../../assets/vendor/js/helpers.min.js"></script>
    <script src="../../../assets/js/config.min.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Sidebar Menu -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        <!-- / Sidebar Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->
          <nav class="layout-navbar container-fluid px-3 px-md-4 navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
              <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="../../../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="edit-profile.php">
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
                    <li><div class="dropdown-divider my-1"></div></li>
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
                    <li><div class="dropdown-divider my-1"></div></li>
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
                <h4 class="fw-bold py-1 mb-0"><i class="icon-base bx bx-building-house me-2"></i>Company Profile</h4>
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

              <div class="card p-4">
                <div class="card-header border-bottom mb-4 px-0 pt-0">
                  <h5 class="card-title text-primary mb-1">Company Information Settings</h5>
                  <p class="text-muted mb-0">These details are automatically printed on Quotation and Invoice Headers & Footers.</p>
                </div>

                <form method="POST" action="company-profile.php">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                  <div class="row g-3 mb-4">
                    <!-- Company Name -->
                    <div class="col-md-6">
                      <label class="form-label" for="company_name">Company Name <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        id="company_name"
                        name="company_name"
                        placeholder="e.g. Tech-xpert Services Pvt Ltd"
                        value="<?= htmlspecialchars((string)($company !== null ? $company->getCompanyName() : ''), ENT_QUOTES, 'UTF-8') ?>"
                        required />
                    </div>

                    <!-- Registration Number -->
                    <div class="col-md-6">
                      <label class="form-label" for="registration_no">Registration No / CIN</label>
                      <input
                        type="text"
                        class="form-control"
                        id="registration_no"
                        name="registration_no"
                        placeholder="e.g. REG-2026-987654"
                        value="<?= htmlspecialchars((string)($company !== null ? (string)$company->getRegistrationNo() : ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>
                  </div>

                  <div class="row g-3 mb-4">
                    <!-- GST Number -->
                    <div class="col-md-4">
                      <label class="form-label" for="gst_no">GSTIN / Tax No</label>
                      <input
                        type="text"
                        class="form-control text-uppercase"
                        id="gst_no"
                        name="gst_no"
                        placeholder="e.g. 27AAACS1234F1Z5"
                        value="<?= htmlspecialchars((string)($company !== null ? (string)$company->getGstNo() : ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>

                    <!-- Phone -->
                    <div class="col-md-4">
                      <label class="form-label" for="phone">Phone / Mobile No</label>
                      <input
                        type="text"
                        class="form-control"
                        id="phone"
                        name="phone"
                        placeholder="e.g. +91 98765 43210"
                        value="<?= htmlspecialchars((string)($company !== null ? (string)$company->getPhone() : ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>

                    <!-- Fax -->
                    <div class="col-md-4">
                      <label class="form-label" for="fax">Fax Number</label>
                      <input
                        type="text"
                        class="form-control"
                        id="fax"
                        name="fax"
                        placeholder="e.g. 022-12345678"
                        value="<?= htmlspecialchars((string)($company !== null ? (string)$company->getFax() : ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>
                  </div>

                  <div class="row g-3 mb-4">
                    <!-- Email -->
                    <div class="col-md-6">
                      <label class="form-label" for="email">Company Email Address</label>
                      <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="e.g. contact@techxpert.com"
                        value="<?= htmlspecialchars((string)($company !== null ? (string)$company->getEmail() : ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>

                    <!-- Address -->
                    <div class="col-md-6">
                      <label class="form-label" for="address">Full Office Address</label>
                      <textarea
                        class="form-control"
                        id="address"
                        name="address"
                        rows="2"
                        placeholder="Enter full office address"><?= htmlspecialchars((string)($company !== null ? (string)$company->getAddress() : ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                  </div>

                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                      <i class="icon-base bx bx-save me-1"></i> Save Company Profile
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                  </div>
                </form>
              </div>
            </div>

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-fluid px-3 px-md-4 d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  © <?= date('Y') ?>, Tech-xpert Admin Panel
                </div>
              </div>
            </footer>
          </div>
        </div>
      </div>
    </div>

    <script src="../../../assets/vendor/libs/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.min.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.min.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../../assets/vendor/js/menu.min.js"></script>
    <script src="../../../assets/js/main.min.js"></script>
  </body>
</html>
