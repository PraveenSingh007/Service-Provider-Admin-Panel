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

$actionMessage = null;
$actionError = null;

// Handle Delete Request
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $result = $controller->destroy($serviceId, $_POST, $csrfToken);
    if ($result['response']['success']) {
        $actionMessage = (string) $result['response']['message'];
    } else {
        $errors = (array) ($result['response']['errors'] ?? []);
        $actionError = count($errors) > 0 ? implode(', ', $errors) : (string) $result['response']['message'];
    }
}

$servicesResult = $controller->index();
$services = (array) ($servicesResult['response']['data']['services'] ?? []);
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

    <title>Services List - Tech-xpert Admin</title>

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
        background-color: #61BEF1 !important;
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
    <script src="../../../assets/vendor/js/helpers.min.js"></script>
    <script src="../../../assets/js/config.min.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Sidebar Menu -->
        <?php
        $activePage = 'services';
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
                <h4 class="fw-bold py-1 mb-0">Services List</h4>
                <!-- Top Right Add Service Button -->
                <a href="add-service.php" class="btn btn-primary">
                  <i class="icon-base bx bx-plus me-1"></i> Add Service
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

              <!-- Services DataTable Card -->
              <div class="card p-4">
                <div class="table-responsive">
                  <table id="servicesTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>#ID</th>
                        <th>Service Image</th>
                        <th>Service Name</th>
                        <th>Created At</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($services as $srv): ?>
                        <tr>
                          <td data-order="<?= (int)$srv['id'] ?>"><strong>#<?= (int)$srv['id'] ?></strong></td>
                          <td>
                            <?php
                            $imgPath = (string) ($srv['service_image'] ?? '');
                            $cleanPath = ltrim($imgPath, '/');
                            if (strpos($cleanPath, 'html/') === 0) {
                                $cleanPath = substr($cleanPath, 5);
                            }
                            $imgSrc = '../../' . ($cleanPath ?: 'uploads/services/cctv_service.png');
                            ?>
                            <?php if (!empty($imgPath)): ?>
                              <img
                                src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars((string)$srv['service_name'], ENT_QUOTES, 'UTF-8') ?>"
                                class="rounded shadow-sm"
                                style="width: 50px; height: 50px; object-fit: cover;" />
                            <?php else: ?>
                              <span class="badge bg-label-secondary">No Image</span>
                            <?php endif; ?>
                          </td>
                          <td><span class="fw-medium"><?= htmlspecialchars((string)$srv['service_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                          <td><?= htmlspecialchars((string)($srv['created_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                          <td>
                            <div class="d-flex align-items-center gap-2">
                              <!-- Edit Option Button -->
                              <a
                                href="add-service.php?id=<?= htmlspecialchars((string)$srv['id'], ENT_QUOTES, 'UTF-8') ?>"
                                class="btn btn-sm btn-outline-primary">
                                <i class="icon-base bx bx-edit-alt me-1"></i> Edit
                              </a>
                              <!-- Delete Option Button -->
                              <form method="POST" action="services.php" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="service_id" value="<?= htmlspecialchars((string)$srv['id'], ENT_QUOTES, 'UTF-8') ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                  <i class="icon-base bx bx-trash me-1"></i> Delete
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
        $('#servicesTable').DataTable({
          dom: '<"row mb-3 align-items-center"<"col-md-4"l><"col-md-8 d-flex justify-content-end align-items-center gap-2"fB>>' +
               '<"table-responsive"t>' +
               '<"row mt-3 align-items-center"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>',
          buttons: [
            {
              extend: 'copyHtml5',
              className: 'btn btn-icon-export btn-export-copy me-1',
              text: '<i class="icon-base bx bx-copy"></i>',
              titleAttr: 'Copy to Clipboard',
              exportOptions: { columns: [0, 2, 3] }
            },
            {
              extend: 'csvHtml5',
              className: 'btn btn-icon-export btn-export-csv me-1',
              text: '<i class="icon-base bx bx-file"></i>',
              titleAttr: 'Export as CSV',
              exportOptions: { columns: [0, 2, 3] }
            },
            {
              extend: 'excelHtml5',
              className: 'btn btn-icon-export btn-export-excel me-1',
              text: '<i class="icon-base bx bx-spreadsheet"></i>',
              titleAttr: 'Export as Excel',
              exportOptions: { columns: [0, 2, 3] }
            },
            {
              extend: 'pdfHtml5',
              className: 'btn btn-icon-export btn-export-pdf me-1',
              text: '<i class="icon-base bx bxs-file-pdf"></i>',
              titleAttr: 'Export as PDF',
              exportOptions: { columns: [0, 2, 3] }
            },
            {
              extend: 'print',
              className: 'btn btn-icon-export btn-export-print',
              text: '<i class="icon-base bx bx-printer"></i>',
              titleAttr: 'Print Table',
              exportOptions: { columns: [0, 2, 3] }
            }
          ],
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
          pageLength: 10,
          language: {
            search: "_INPUT_",
            searchPlaceholder: "Search services...",
            lengthMenu: "Display _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ services",
            paginate: {
              previous: 'Prev',
              next: 'Next'
            }
          },
          order: [[0, 'asc']],
          columnDefs: [
            { type: 'num', targets: 0 },
            { orderable: false, targets: [1, 4] },
            { orderSequence: ['asc', 'desc'], targets: [0, 2, 3] }
          ]
        });
      });
    </script>
  </body>
</html>
