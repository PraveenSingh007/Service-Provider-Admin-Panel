<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Service.php';
require_once __DIR__ . '/Repository/ServiceRepository.php';
require_once __DIR__ . '/Service/ServiceManagementService.php';
require_once __DIR__ . '/Controller/ServiceController.php';

use App\Admin\Controller\ServiceController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\ServiceRepository;
use App\Admin\Service\ServiceManagementService;

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
$serviceRepo = new ServiceRepository($dbConn);
$serviceMgmt = new ServiceManagementService($serviceRepo);
$controller = new ServiceController($serviceMgmt);

$serviceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $serviceId > 0;
$existingService = $isEditMode ? $serviceMgmt->getServiceById($serviceId) : null;

$formMessage = null;
$formError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isEditMode) {
        $result = $controller->update($serviceId, $_POST, $_FILES, $csrfToken);
    } else {
        $result = $controller->store($_POST, $_FILES, $csrfToken);
    }

    if ($result['response']['success']) {
        header('Location: services.php');
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
  data-assets-path="../../../assets/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?= $isEditMode ? 'Edit Service' : 'Add New Service' ?> - tech-xpert Admin</title>

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
        $activePage = 'add-service';
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
                <h4 class="fw-bold py-3 mb-0"><?= $isEditMode ? 'Edit Service' : 'Add New Service' ?></h4>
                <a href="services.php" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i> Back to Services
                </a>
              </div>

              <?php if ($formError !== null): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                  <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Service Form Card -->
              <div class="row">
                <div class="col-xl-8 col-lg-10">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0"><?= $isEditMode ? 'Service Details (# ' . $serviceId . ')' : 'Service Details' ?></h5>
                    </div>
                    <div class="card-body">
                      <form
                        action="add-service.php<?= $isEditMode ? '?id=' . $serviceId : '' ?>"
                        method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                        <div class="mb-4">
                          <label class="form-label" for="service_name">Service Name <span class="text-danger">*</span></label>
                          <input
                            type="text"
                            class="form-control"
                            id="service_name"
                            name="service_name"
                            placeholder="e.g. Home Cleaning, AC Repair, Plumbing"
                            value="<?= htmlspecialchars((string)($_POST['service_name'] ?? ($existingService !== null ? $existingService->getServiceName() : '')), ENT_QUOTES, 'UTF-8') ?>"
                            required />
                        </div>

                        <div class="mb-4">
                          <label class="form-label" for="service_image">Service Image</label>
                          <?php if ($isEditMode && $existingService !== null && !empty($existingService->getServiceImage())): ?>
                            <?php
                            $existingImg = (string) $existingService->getServiceImage();
                            $cleanPath = ltrim($existingImg, '/');
                            if (strpos($cleanPath, 'html/') === 0) {
                                $cleanPath = substr($cleanPath, 5);
                            }
                            $imgSrc = '../../' . $cleanPath;
                            ?>
                            <div class="mb-3">
                              <label class="form-label d-block text-muted">Current Service Image</label>
                              <div class="p-2 border rounded d-inline-block bg-light">
                                <img
                                  src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
                                  alt="Current Service Image"
                                  class="rounded shadow-sm"
                                  style="max-width: 140px; max-height: 140px; object-fit: cover;" />
                              </div>
                            </div>
                          <?php endif; ?>
                          <input
                            type="file"
                            class="form-control"
                            id="service_image"
                            name="service_image"
                            accept="image/jpeg,image/png,image/webp,image/gif" />
                          <div class="form-text">Allowed formats: JPG, PNG, WEBP, GIF. Max file size: 5MB.</div>
                        </div>

                        <div class="d-flex gap-2">
                          <button type="submit" class="btn btn-primary">
                            <i class="icon-base bx bx-save me-1"></i> <?= $isEditMode ? 'Update Service' : 'Save Service' ?>
                          </button>
                          <a href="services.php" class="btn btn-outline-secondary">Cancel</a>
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
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../../assets/vendor/js/menu.js"></script>
    <script src="../../../assets/js/main.js"></script>
  </body>
</html>
