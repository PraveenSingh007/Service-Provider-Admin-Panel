<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/ServiceRequest.php';
require_once __DIR__ . '/Repository/ServiceRequestRepository.php';
require_once __DIR__ . '/Service/ServiceRequestManagementService.php';
require_once __DIR__ . '/Controller/ServiceRequestController.php';

use App\Admin\Controller\ServiceRequestController;
use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\ServiceRequestRepository;
use App\Admin\Service\ServiceRequestManagementService;

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
$repository = new ServiceRequestRepository($dbConn);
$serviceMgmt = new ServiceRequestManagementService($repository);
$controller = new ServiceRequestController($serviceMgmt);

$actionMessage = null;
$actionError = null;

// Handle Form Submissions (Create, Update, Quick Assign, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $action = (string) $_POST['action'];

    if ($action === 'create') {
        $result = $controller->store($_POST, $csrfToken);
        if ($result['response']['success']) {
            $actionMessage = (string) $result['response']['message'];
        } else {
            $errors = (array) ($result['response']['errors'] ?? []);
            $actionError = count($errors) > 0 ? implode(' ', $errors) : (string) $result['response']['message'];
        }
    } elseif ($action === 'update') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $result = $controller->update($requestId, $_POST, $csrfToken);
        if ($result['response']['success']) {
            $actionMessage = (string) $result['response']['message'];
        } else {
            $errors = (array) ($result['response']['errors'] ?? []);
            $actionError = count($errors) > 0 ? implode(' ', $errors) : (string) $result['response']['message'];
        }
    } elseif ($action === 'quick_assign') {
        $result = $controller->assign($_POST, $csrfToken);
        if ($result['response']['success']) {
            $actionMessage = (string) $result['response']['message'];
        } else {
            $errors = (array) ($result['response']['errors'] ?? []);
            $actionError = count($errors) > 0 ? implode(' ', $errors) : (string) $result['response']['message'];
        }
    } elseif ($action === 'delete') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $result = $controller->destroy($requestId, $_POST, $csrfToken);
        if ($result['response']['success']) {
            $actionMessage = (string) $result['response']['message'];
        } else {
            $errors = (array) ($result['response']['errors'] ?? []);
            $actionError = count($errors) > 0 ? implode(' ', $errors) : (string) $result['response']['message'];
        }
    }
}

// Fetch all service requests, available service areas, active employees, quotations, and invoices
$requestsResult = $controller->index();
$serviceRequests = (array) ($requestsResult['response']['data']['requests'] ?? []);
$serviceAreas = $serviceMgmt->getAvailableServiceAreas();
$activeEmployees = $serviceMgmt->getActiveEmployees();
$availableQuotations = $serviceMgmt->getAvailableQuotations();
$availableInvoices = $serviceMgmt->getAvailableInvoices();

// Calculate summary stats
$totalRequests = count($serviceRequests);
$pendingCount = 0;
$inProgressCount = 0;
$completedCount = 0;

foreach ($serviceRequests as $req) {
    $st = strtolower((string) ($req['request_status'] ?? 'pending'));
    if ($st === 'pending') {
        $pendingCount++;
    } elseif (in_array($st, ['assigned', 'in_progress', 'quotation_sent'], true)) {
        $inProgressCount++;
    } elseif ($st === 'completed') {
        $completedCount++;
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

    <title>Service Requests Management - tech-xpert Admin</title>

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

    <!-- Select2 CSS & Bootstrap 5 Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
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
      .btn-export-csv { background-color: #696cff !important; color: #ffffff !important; border: none !important; }
      .btn-export-excel { background-color: #71dd37 !important; color: #ffffff !important; border: none !important; }
      .btn-export-pdf { background-color: #ff3e1d !important; color: #ffffff !important; border: none !important; }
      .btn-export-print { background-color: #03c3ec !important; color: #ffffff !important; border: none !important; }
      
      .badge-category { font-size: 0.75rem; padding: 0.35em 0.65em; text-transform: uppercase; letter-spacing: 0.5px; }
      .badge-cctv { background-color: #e7e7ff; color: #696cff; }
      .badge-computer { background-color: #e8fadf; color: #71dd37; }
      .badge-amc { background-color: #fff2d6; color: #ffab00; }
      .badge-other { background-color: #f1f0f2; color: #8592a3; }
      
      .badge-priority-emergency { background-color: #ff3e1d; color: #ffffff; }
      .badge-priority-high { background-color: #ff9f43; color: #ffffff; }
      .badge-priority-medium { background-color: #03c3ec; color: #ffffff; }
      .badge-priority-low { background-color: #8592a3; color: #ffffff; }

      .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
        border-color: #d9dee3;
      }
      .select2-container {
        width: 100% !important;
      }
    </style>

    <!-- Helpers -->
    <script src="../../../assets/vendor/js/helpers.js"></script>
    <script src="../../../assets/js/config.js"></script>
  </head>

  <body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Sidebar Menu -->
        <?php
        $activePage = 'service-requests';
        require __DIR__ . '/sidebar.php';
        ?>

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
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
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
                    <li><div class="dropdown-divider my-1"></div></li>
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
              
              <!-- Header Section -->
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                  <h4 class="fw-bold py-1 mb-0"><i class="bx bx-wrench me-2 text-primary"></i>Service Requests Management</h4>
                  <small class="text-muted">CCTV Installation & Repair | Computer Hardware & Service | AMC Contracts</small>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRequestModal">
                  <i class="icon-base bx bx-plus me-1"></i> New Service Request
                </button>
              </div>

              <!-- Alerts -->
              <?php if ($actionMessage !== null): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="bx bx-check-circle me-1"></i> <?= htmlspecialchars($actionMessage, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <?php if ($actionError !== null): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bx bx-error-circle me-1"></i> <?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Summary Cards -->
              <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                  <div class="card card-border-shadow-primary h-100">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                          <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-list-check fs-4"></i></span>
                        </div>
                        <h4 class="mb-0"><?= $totalRequests ?></h4>
                      </div>
                      <p class="mb-0 text-muted">Total Service Requests</p>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                  <div class="card card-border-shadow-warning h-100">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                          <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-time fs-4"></i></span>
                        </div>
                        <h4 class="mb-0"><?= $pendingCount ?></h4>
                      </div>
                      <p class="mb-0 text-muted">Pending / New Requests</p>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                  <div class="card card-border-shadow-info h-100">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                          <span class="avatar-initial rounded bg-label-info"><i class="bx bx-user-check fs-4"></i></span>
                        </div>
                        <h4 class="mb-0"><?= $inProgressCount ?></h4>
                      </div>
                      <p class="mb-0 text-muted">Assigned / In Progress</p>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                  <div class="card card-border-shadow-success h-100">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                          <span class="avatar-initial rounded bg-label-success"><i class="bx bx-check-double fs-4"></i></span>
                        </div>
                        <h4 class="mb-0"><?= $completedCount ?></h4>
                      </div>
                      <p class="mb-0 text-muted">Completed Requests</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Service Requests DataTable Card -->
              <div class="card p-4">
                <div class="table-responsive">
                  <table id="serviceRequestsTable" class="table table-hover align-middle w-100">
                    <thead>
                      <tr>
                        <th>Req No</th>
                        <th>Customer</th>
                        <th>Service / Type</th>
                        <th>Location & Pincode</th>
                        <th>Priority</th>
                        <th>Assigned Technician</th>
                        <th>Status</th>
                        <th>Quotation / Invoice</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($serviceRequests as $req): ?>
                        <?php
                        $reqId = (int) $req['id'];
                        $reqNo = (string) $req['service_request_no'];
                        $custName = (string) $req['customer_name'];
                        $mobile = (string) $req['request_by_mobile_no'];
                        $serviceName = (string) $req['service_name'];
                        $category = (string) ($req['service_category'] ?? 'other');
                        $reqType = (string) ($req['request_type'] ?? '');
                        $pincode = (string) $req['request_pincode'];
                        $city = (string) ($req['request_city'] ?? '');
                        $assignTo = (string) ($req['assign_to'] ?? '');
                        $status = (string) ($req['request_status'] ?? 'pending');
                        $priority = (string) ($req['priority'] ?? 'medium');
                        $quotationNo = (string) ($req['request_quotation_no'] ?? '');
                        $invoiceNo = (string) ($req['request_invoice_no'] ?? '');
                        $amcNo = (string) ($req['amc_contract_number'] ?? '');

                        $catBadgeClass = 'badge-other';
                        if ($category === 'cctv_camera') { $catBadgeClass = 'badge-cctv'; }
                        elseif ($category === 'computer_hardware') { $catBadgeClass = 'badge-computer'; }
                        elseif ($category === 'amc_contract') { $catBadgeClass = 'badge-amc'; }

                        $statusBadgeClass = 'bg-label-secondary';
                        if ($status === 'pending') { $statusBadgeClass = 'bg-label-warning'; }
                        elseif ($status === 'assigned') { $statusBadgeClass = 'bg-label-info'; }
                        elseif ($status === 'in_progress') { $statusBadgeClass = 'bg-label-primary'; }
                        elseif ($status === 'quotation_sent') { $statusBadgeClass = 'bg-label-dark'; }
                        elseif ($status === 'invoice_generated') { $statusBadgeClass = 'bg-label-info'; }
                        elseif ($status === 'completed') { $statusBadgeClass = 'bg-label-success'; }
                        elseif ($status === 'cancelled') { $statusBadgeClass = 'bg-label-danger'; }

                        $priorityBadgeClass = 'badge-priority-medium';
                        if ($priority === 'emergency') { $priorityBadgeClass = 'badge-priority-emergency'; }
                        elseif ($priority === 'high') { $priorityBadgeClass = 'badge-priority-high'; }
                        elseif ($priority === 'low') { $priorityBadgeClass = 'badge-priority-low'; }
                        ?>
                        <tr>
                          <td>
                            <strong><a href="javascript:void(0);" onclick="viewRequestDetails(<?= htmlspecialchars(json_encode($req, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);"><?= htmlspecialchars($reqNo, ENT_QUOTES, 'UTF-8') ?></a></strong>
                            <?php if (!empty($amcNo)): ?>
                              <br><small class="text-warning"><i class="bx bx-award me-1"></i>AMC: <?= htmlspecialchars($amcNo, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="fw-semibold d-block"><?= htmlspecialchars($custName, ENT_QUOTES, 'UTF-8') ?></span>
                            <small class="text-muted"><i class="bx bx-phone me-1"></i><?= htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') ?></small>
                          </td>
                          <td>
                            <span class="badge <?= $catBadgeClass ?> badge-category mb-1"><?= htmlspecialchars(str_replace('_', ' ', strtoupper($category)), ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="fw-medium text-wrap" style="max-width: 200px;"><?= htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($reqType)), ENT_QUOTES, 'UTF-8') ?></small>
                          </td>
                          <td>
                            <span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i><?= htmlspecialchars($pincode, ENT_QUOTES, 'UTF-8') ?></span>
                            <small class="d-block text-muted"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?></small>
                          </td>
                          <td>
                            <span class="badge <?= $priorityBadgeClass ?>"><?= htmlspecialchars(strtoupper($priority), ENT_QUOTES, 'UTF-8') ?></span>
                          </td>
                          <td>
                            <?php if (!empty($assignTo)): ?>
                              <span class="fw-medium text-dark d-block"><i class="bx bx-user me-1 text-primary"></i><?= htmlspecialchars($assignTo, ENT_QUOTES, 'UTF-8') ?></span>
                              <button type="button" class="btn btn-xs btn-link text-primary p-0" onclick="openAssignModal(<?= $reqId ?>, '<?= htmlspecialchars((string)($req['assigned_employee_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars((string)($req['request_status_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>');">Re-assign</button>
                            <?php else: ?>
                              <button type="button" class="btn btn-sm btn-outline-warning" onclick="openAssignModal(<?= $reqId ?>, '', '');">
                                <i class="bx bx-user-plus me-1"></i> Assign
                              </button>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="badge <?= $statusBadgeClass ?>"><?= htmlspecialchars(str_replace('_', ' ', strtoupper($status)), ENT_QUOTES, 'UTF-8') ?></span>
                          </td>
                          <td>
                            <?php if (!empty($quotationNo)): ?>
                              <small class="d-block text-primary"><i class="bx bx-receipt me-1"></i><?= htmlspecialchars($quotationNo, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                            <?php if (!empty($invoiceNo)): ?>
                              <small class="d-block text-success"><i class="bx bx-file me-1"></i><?= htmlspecialchars($invoiceNo, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                            <?php if (empty($quotationNo) && empty($invoiceNo)): ?>
                              <span class="text-muted small">-</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="d-flex align-items-center gap-1">
                              <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="View Full Details" onclick="viewRequestDetails(<?= htmlspecialchars(json_encode($req, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);">
                                <i class="bx bx-show"></i>
                              </button>
                              <button type="button" class="btn btn-sm btn-icon btn-outline-primary" title="Edit All Fields" onclick="editRequest(<?= htmlspecialchars(json_encode($req, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);">
                                <i class="bx bx-edit-alt"></i>
                              </button>
                              <form method="POST" action="service-requests.php" onsubmit="return confirm('Are you sure you want to delete this service request?');" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="request_id" value="<?= $reqId ?>" />
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Request">
                                  <i class="bx bx-trash"></i>
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

    <!-- ==================== MODAL 1: ADD NEW SERVICE REQUEST ==================== -->
    <div class="modal fade" id="addRequestModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" action="service-requests.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="create" />
            
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title text-white"><i class="bx bx-plus-circle me-1"></i> New Service Request Booking</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              
              <!-- Customer Section -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-user me-1"></i> Customer Information</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                  <input type="text" name="customer_name" id="add_customer_name" class="form-control" placeholder="e.g. Ramesh Patel" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                  <input type="text" name="request_by_mobile_no" id="add_request_by_mobile_no" class="form-control" placeholder="10-digit mobile no" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Email Address</label>
                  <input type="email" name="customer_email" id="add_customer_email" class="form-control" placeholder="customer@example.com" />
                </div>
              </div>

              <!-- Service Details Section -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-cog me-1"></i> Service Details</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">Service Category <span class="text-danger">*</span></label>
                  <select name="service_category" class="form-select" required>
                    <option value="cctv_camera">CCTV Camera Setup & Repair</option>
                    <option value="computer_hardware">Computer Hardware Sales & Repair</option>
                    <option value="amc_contract">Annual Maintenance Contract (AMC)</option>
                    <option value="other">Other Service</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Request Type <span class="text-danger">*</span></label>
                  <select name="request_type" class="form-select" required>
                    <option value="fresh_installation">Fresh Installation</option>
                    <option value="repair_service">Repair & Maintenance</option>
                    <option value="hardware_purchase">Hardware Purchase</option>
                    <option value="amc_new_booking">AMC New Contract Booking</option>
                    <option value="amc_periodic_service">AMC Periodic Maintenance Visit</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Service Name <span class="text-danger">*</span></label>
                  <input type="text" name="service_name" class="form-control" placeholder="e.g. 4 Camera CCTV Installation" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Problem / Service Description</label>
                  <textarea name="description" class="form-control" rows="2" placeholder="Describe the customer requirement or problem..."></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Device / Hardware Details</label>
                  <textarea name="device_details" class="form-control" rows="2" placeholder="e.g. 4 Hikvision 5MP Cameras, 4-Ch DVR or Dell i5 Desktop"></textarea>
                </div>
              </div>

              <!-- Location & Pincode Section -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-map-pin me-1"></i> Address & Service Area Pincode</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Full Address <span class="text-danger">*</span></label>
                  <input type="text" name="request_address" class="form-control" placeholder="Shop/House No, Street name..." required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Service Area Pincode <span class="text-danger">* (Strict Service Area)</span></label>
                  <select name="request_pincode" id="add_request_pincode" class="form-select" required>
                    <option value="">-- Select Registered Pincode --</option>
                    <?php foreach ($serviceAreas as $sa): ?>
                      <option value="<?= htmlspecialchars($sa['pincode'], ENT_QUOTES, 'UTF-8') ?>" data-city="<?= htmlspecialchars($sa['city'], ENT_QUOTES, 'UTF-8') ?>" data-state="<?= htmlspecialchars($sa['state'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($sa['pincode'] . ' - ' . $sa['area_name'] . ' (' . $sa['city'] . ')', ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted fs-xs">Only pincodes listed in service areas table are allowed.</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label">City <i class="bx bx-lock-alt text-muted" title="Locked & auto-filled from selected pincode"></i></label>
                  <input type="text" name="request_city" id="add_request_city" class="form-control bg-light" placeholder="Auto-filled from pincode" readonly required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">State <i class="bx bx-lock-alt text-muted" title="Locked & auto-filled from selected pincode"></i></label>
                  <input type="text" name="request_state" id="add_request_state" class="form-control bg-light" placeholder="Auto-filled from pincode" readonly required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Landmark</label>
                  <input type="text" name="landmark" class="form-control" placeholder="Near Temple / Opposite Park" />
                </div>
              </div>

              <!-- Searchable Quotation & Invoice Links -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-receipt me-1"></i> Quotation & Invoice Linking (Searchable Dropdowns)</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Quotation Number (Searchable)</label>
                  <select name="request_quotation_no" id="add_request_quotation_no" class="form-select select2-searchable">
                    <option value="">-- None / Search Quotation --</option>
                    <?php foreach ($availableQuotations as $q): ?>
                      <?php 
                        $searchText = $q['quotation_number'] . ' | REQ: ' . ($q['service_request_id'] ?: 'N/A') . ' | Cust: ' . $q['customer_name'] . ' | Mob: ' . $q['customer_mobile'] . ($q['customer_email'] ? ' | Email: ' . $q['customer_email'] : '') . ' | Amt: ₹' . number_format($q['total_amount'], 2);
                      ?>
                      <option value="<?= htmlspecialchars($q['quotation_number'], ENT_QUOTES, 'UTF-8') ?>" data-customer="<?= htmlspecialchars($q['customer_name'], ENT_QUOTES, 'UTF-8') ?>" data-mobile="<?= htmlspecialchars($q['customer_mobile'], ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars($q['customer_email'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted fs-xs">Search by Quotation No, Service Request ID, Customer Name, Mobile or Email.</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Invoice Number (Searchable)</label>
                  <select name="request_invoice_no" id="add_request_invoice_no" class="form-select select2-searchable">
                    <option value="">-- None / Search Invoice --</option>
                    <?php foreach ($availableInvoices as $inv): ?>
                      <?php 
                        $searchText = $inv['invoice_number'] . ($inv['service_request_id'] ? ' | REQ: ' . $inv['service_request_id'] : '') . ' | Cust: ' . $inv['customer_name'] . ' | Mob: ' . $inv['customer_mobile'] . ($inv['customer_email'] ? ' | Email: ' . $inv['customer_email'] : '') . ' | Amt: ₹' . number_format($inv['total_amount'], 2);
                      ?>
                      <option value="<?= htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8') ?>" data-customer="<?= htmlspecialchars($inv['customer_name'], ENT_QUOTES, 'UTF-8') ?>" data-mobile="<?= htmlspecialchars($inv['customer_mobile'], ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars($inv['customer_email'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted fs-xs">Search by Invoice No, Service Request ID, Customer Name, Mobile or Email.</small>
                </div>
              </div>

              <!-- Workflow & Assignment Section -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-task me-1"></i> Schedule, Assignment & Priority</h6>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Priority</label>
                  <select name="priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="emergency">Emergency</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Preferred Visit Date</label>
                  <input type="date" name="preferred_visit_date" class="form-control" value="<?= date('Y-m-d') ?>" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Time Slot</label>
                  <select name="preferred_time_slot" class="form-select">
                    <option value="anytime">Anytime</option>
                    <option value="morning">Morning (9 AM - 1 PM)</option>
                    <option value="afternoon">Afternoon (1 PM - 5 PM)</option>
                    <option value="evening">Evening (5 PM - 8 PM)</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Assign Technician</label>
                  <select name="assigned_employee_id" class="form-select">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($activeEmployees as $emp): ?>
                      <option value="<?= $emp['id'] ?>">
                        <?= htmlspecialchars($emp['emp_name'] . ' (' . $emp['emp_role'] . ')', ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">AMC Contract No (If Applicable)</label>
                  <input type="text" name="amc_contract_number" class="form-control" placeholder="e.g. AMC-2026-88" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Initial Request Status</label>
                  <select name="request_status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                  </select>
                </div>
                <div class="col-md-4 align-self-center pt-3">
                  <div class="form-check">
                    <input type="checkbox" name="site_inspection_required" value="1" class="form-check-input" id="checkInspection" />
                    <label class="form-check-label" for="checkInspection">Site Survey / Inspection Required</label>
                  </div>
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Create Service Request</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL 2: EDIT FULL SERVICE REQUEST ==================== -->
    <div class="modal fade" id="editRequestModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" action="service-requests.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="request_id" id="edit_request_id" />
            
            <div class="modal-header bg-dark text-white">
              <h5 class="modal-title text-white"><i class="bx bx-edit me-1"></i> Edit Service Request <span id="edit_req_no_title"></span></h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              
              <!-- Customer Section -->
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-user me-1"></i> Customer Information</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                  <input type="text" name="customer_name" id="edit_customer_name" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                  <input type="text" name="request_by_mobile_no" id="edit_request_by_mobile_no" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Email Address</label>
                  <input type="email" name="customer_email" id="edit_customer_email" class="form-control" />
                </div>
              </div>

              <!-- Service Details Section -->
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-cog me-1"></i> Service Details & Classification</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">Service Category</label>
                  <select name="service_category" id="edit_service_category" class="form-select">
                    <option value="cctv_camera">CCTV Camera Setup & Repair</option>
                    <option value="computer_hardware">Computer Hardware Sales & Repair</option>
                    <option value="amc_contract">Annual Maintenance Contract (AMC)</option>
                    <option value="other">Other Service</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Request Type</label>
                  <select name="request_type" id="edit_request_type" class="form-select">
                    <option value="fresh_installation">Fresh Installation</option>
                    <option value="repair_service">Repair & Maintenance</option>
                    <option value="hardware_purchase">Hardware Purchase</option>
                    <option value="amc_new_booking">AMC New Contract Booking</option>
                    <option value="amc_periodic_service">AMC Periodic Maintenance Visit</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Service Name <span class="text-danger">*</span></label>
                  <input type="text" name="service_name" id="edit_service_name" class="form-control" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Description</label>
                  <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Device Details</label>
                  <textarea name="device_details" id="edit_device_details" class="form-control" rows="2"></textarea>
                </div>
              </div>

              <!-- Location & Pincode Section -->
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-map-pin me-1"></i> Address & Pincode (Strict Service Area)</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Address <span class="text-danger">*</span></label>
                  <input type="text" name="request_address" id="edit_request_address" class="form-control" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Pincode <span class="text-danger">* (Strict Service Area)</span></label>
                  <select name="request_pincode" id="edit_request_pincode" class="form-select" required>
                    <option value="">-- Select Registered Pincode --</option>
                    <?php foreach ($serviceAreas as $sa): ?>
                      <option value="<?= htmlspecialchars($sa['pincode'], ENT_QUOTES, 'UTF-8') ?>" data-city="<?= htmlspecialchars($sa['city'], ENT_QUOTES, 'UTF-8') ?>" data-state="<?= htmlspecialchars($sa['state'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($sa['pincode'] . ' - ' . $sa['area_name'] . ' (' . $sa['city'] . ')', ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">City <i class="bx bx-lock-alt text-muted" title="Locked & auto-filled from selected pincode"></i></label>
                  <input type="text" name="request_city" id="edit_request_city" class="form-control bg-light" placeholder="Auto-filled from pincode" readonly required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">State <i class="bx bx-lock-alt text-muted" title="Locked & auto-filled from selected pincode"></i></label>
                  <input type="text" name="request_state" id="edit_request_state" class="form-control bg-light" placeholder="Auto-filled from pincode" readonly required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Landmark</label>
                  <input type="text" name="landmark" id="edit_landmark" class="form-control" />
                </div>
              </div>

              <!-- Searchable Quotation & Invoice Links for Edit Modal -->
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-receipt me-1"></i> Quotation & Invoice Links (Searchable Dropdowns)</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Quotation Number (Searchable)</label>
                  <select name="request_quotation_no" id="edit_request_quotation_no" class="form-select select2-searchable">
                    <option value="">-- None / Select Quotation --</option>
                    <?php foreach ($availableQuotations as $q): ?>
                      <?php 
                        $searchText = $q['quotation_number'] . ' | REQ: ' . ($q['service_request_id'] ?: 'N/A') . ' | Cust: ' . $q['customer_name'] . ' | Mob: ' . $q['customer_mobile'] . ($q['customer_email'] ? ' | Email: ' . $q['customer_email'] : '') . ' | Amt: ₹' . number_format($q['total_amount'], 2);
                      ?>
                      <option value="<?= htmlspecialchars($q['quotation_number'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted fs-xs">Search by Quotation No, Service Request ID, Customer Name, Mobile or Email.</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Invoice Number (Searchable)</label>
                  <select name="request_invoice_no" id="edit_request_invoice_no" class="form-select select2-searchable">
                    <option value="">-- None / Select Invoice --</option>
                    <?php foreach ($availableInvoices as $inv): ?>
                      <?php 
                        $searchText = $inv['invoice_number'] . ($inv['service_request_id'] ? ' | REQ: ' . $inv['service_request_id'] : '') . ' | Cust: ' . $inv['customer_name'] . ' | Mob: ' . $inv['customer_mobile'] . ($inv['customer_email'] ? ' | Email: ' . $inv['customer_email'] : '') . ' | Amt: ₹' . number_format($inv['total_amount'], 2);
                      ?>
                      <option value="<?= htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted fs-xs">Search by Invoice No, Service Request ID, Customer Name, Mobile or Email.</small>
                </div>
              </div>

              <!-- Assignment & Status Management -->
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-task me-1"></i> Status, Priority, Employee Assignment & Notes</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Request Status <span class="text-danger">*</span></label>
                  <select name="request_status" id="edit_request_status" class="form-select" required>
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="quotation_sent">Quotation Sent</option>
                    <option value="invoice_generated">Invoice Generated</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Assign To Employee</label>
                  <select name="assigned_employee_id" id="edit_assigned_employee_id" class="form-select">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($activeEmployees as $emp): ?>
                      <option value="<?= $emp['id'] ?>">
                        <?= htmlspecialchars($emp['emp_name'] . ' (' . $emp['emp_role'] . ')', ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Priority</label>
                  <select name="priority" id="edit_priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="emergency">Emergency</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">AMC Contract Number</label>
                  <input type="text" name="amc_contract_number" id="edit_amc_contract_number" class="form-control" placeholder="AMC-2026-XX" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Preferred Visit Date</label>
                  <input type="date" name="preferred_visit_date" id="edit_preferred_visit_date" class="form-control" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Time Slot</label>
                  <select name="preferred_time_slot" id="edit_preferred_time_slot" class="form-select">
                    <option value="anytime">Anytime</option>
                    <option value="morning">Morning (9 AM - 1 PM)</option>
                    <option value="afternoon">Afternoon (1 PM - 5 PM)</option>
                    <option value="evening">Evening (5 PM - 8 PM)</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Status Update / Resolution Notes</label>
                  <textarea name="request_status_notes" id="edit_request_status_notes" class="form-control" rows="2" placeholder="Add technician notes, work progress, or resolution details..."></textarea>
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-dark"><i class="bx bx-save me-1"></i> Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL 3: QUICK ASSIGN EMPLOYEE ==================== -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" action="service-requests.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="quick_assign" />
            <input type="hidden" name="request_id" id="assign_request_id" />
            
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title text-white"><i class="bx bx-user-plus me-1"></i> Quick Assign Technician</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Select Employee / Technician <span class="text-danger">*</span></label>
                <select name="assigned_employee_id" id="assign_employee_id" class="form-select">
                  <option value="">-- Remove / Unassign Technician --</option>
                  <?php foreach ($activeEmployees as $emp): ?>
                    <option value="<?= $emp['id'] ?>">
                      <?= htmlspecialchars($emp['emp_name'] . ' (' . $emp['emp_role'] . ' - ' . $emp['emp_mobile'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Assignment Notes</label>
                <textarea name="request_status_notes" id="assign_status_notes" class="form-control" rows="2" placeholder="Instructions or notes for assigned employee..."></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-info text-white"><i class="bx bx-check me-1"></i> Update Assignment</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL 4: VIEW SERVICE REQUEST DETAILS ==================== -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-label-primary">
            <h5 class="modal-title"><i class="bx bx-info-circle me-1"></i> Service Request Details - <span id="view_req_no"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="viewDetailsBody">
            <!-- Populated via JavaScript -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
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

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
      $(document).ready(function () {
        $('#serviceRequestsTable').DataTable({
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
            searchPlaceholder: "Search requests, customer, pincode, status...",
            lengthMenu: "Display _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ service requests",
            paginate: { previous: 'Prev', next: 'Next' }
          },
          order: [[0, 'desc']],
          columnDefs: [
            { orderable: false, targets: [8] }
          ]
        });

        // Initialize Select2 on Add Request Modal
        $('#addRequestModal').on('shown.bs.modal', function () {
          $('#add_request_quotation_no').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addRequestModal'),
            allowClear: true,
            placeholder: '-- Search Quotation (No, Req ID, Name, Mobile, Email) --'
          });
          $('#add_request_invoice_no').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addRequestModal'),
            allowClear: true,
            placeholder: '-- Search Invoice (No, Req ID, Name, Mobile, Email) --'
          });
        });

        // Initialize Select2 on Edit Request Modal
        $('#editRequestModal').on('shown.bs.modal', function () {
          $('#edit_request_quotation_no').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editRequestModal'),
            allowClear: true,
            placeholder: '-- Search Quotation (No, Req ID, Name, Mobile, Email) --'
          });
          $('#edit_request_invoice_no').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editRequestModal'),
            allowClear: true,
            placeholder: '-- Search Invoice (No, Req ID, Name, Mobile, Email) --'
          });
        });

        // Auto-fill and lock City & State when Pincode is selected in Add Modal
        $('#add_request_pincode').on('change', function () {
          var selectedOption = $(this).find('option:selected');
          var city = selectedOption.data('city') || '';
          var state = selectedOption.data('state') || '';
          $('#add_request_city').val(city);
          $('#add_request_state').val(state);
        });

        // Auto-fill and lock City & State when Pincode is selected in Edit Modal
        $('#edit_request_pincode').on('change', function () {
          var selectedOption = $(this).find('option:selected');
          var city = selectedOption.data('city') || '';
          var state = selectedOption.data('state') || '';
          $('#edit_request_city').val(city);
          $('#edit_request_state').val(state);
        });

        // Auto-fill customer details in Add Modal when Quotation or Invoice is selected
        $('#add_request_quotation_no, #add_request_invoice_no').on('change', function () {
          var selectedOption = $(this).find('option:selected');
          var custName = selectedOption.data('customer');
          var mobile = selectedOption.data('mobile');
          var email = selectedOption.data('email');

          if (custName && !$('#add_customer_name').val()) {
            $('#add_customer_name').val(custName);
          }
          if (mobile && !$('#add_request_by_mobile_no').val()) {
            $('#add_request_by_mobile_no').val(mobile);
          }
          if (email && !$('#add_customer_email').val()) {
            $('#add_customer_email').val(email);
          }
        });
      });

      function openAssignModal(reqId, currentEmpId, notes) {
        $('#assign_request_id').val(reqId);
        $('#assign_employee_id').val(currentEmpId);
        $('#assign_status_notes').val(notes || '');
        var assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
        assignModal.show();
      }

      function editRequest(req) {
        $('#edit_request_id').val(req.id);
        $('#edit_req_no_title').text('(' + req.service_request_no + ')');
        $('#edit_customer_name').val(req.customer_name);
        $('#edit_request_by_mobile_no').val(req.request_by_mobile_no);
        $('#edit_customer_email').val(req.customer_email || '');
        $('#edit_service_category').val(req.service_category || 'other');
        $('#edit_request_type').val(req.request_type || 'repair_service');
        $('#edit_service_name').val(req.service_name);
        $('#edit_description').val(req.description || '');
        $('#edit_device_details').val(req.device_details || '');
        $('#edit_request_address').val(req.request_address);

        // Set Pincode and trigger change to auto-fill City & State
        $('#edit_request_pincode').val(req.request_pincode).trigger('change');
        if (!$('#edit_request_city').val() && req.request_city) {
          $('#edit_request_city').val(req.request_city);
        }
        if (!$('#edit_request_state').val() && req.request_state) {
          $('#edit_request_state').val(req.request_state);
        }

        $('#edit_landmark').val(req.landmark || '');
        $('#edit_request_status').val(req.request_status || 'pending');
        $('#edit_assigned_employee_id').val(req.assigned_employee_id || '');
        $('#edit_priority').val(req.priority || 'medium');
        $('#edit_amc_contract_number').val(req.amc_contract_number || '');
        $('#edit_preferred_visit_date').val(req.preferred_visit_date || '');
        $('#edit_preferred_time_slot').val(req.preferred_time_slot || 'anytime');
        $('#edit_request_status_notes').val(req.request_status_notes || '');

        // Set Select2 values for quotation and invoice in edit modal
        $('#edit_request_quotation_no').val(req.request_quotation_no || '').trigger('change');
        $('#edit_request_invoice_no').val(req.request_invoice_no || '').trigger('change');

        var editModal = new bootstrap.Modal(document.getElementById('editRequestModal'));
        editModal.show();
      }

      function viewRequestDetails(req) {
        $('#view_req_no').text(req.service_request_no);
        
        var html = '<div class="row g-3">';
        html += '<div class="col-md-6"><strong>Customer Name:</strong> ' + escapeHtml(req.customer_name) + '</div>';
        html += '<div class="col-md-6"><strong>Mobile Number:</strong> ' + escapeHtml(req.request_by_mobile_no) + '</div>';
        html += '<div class="col-md-6"><strong>Email:</strong> ' + escapeHtml(req.customer_email || 'N/A') + '</div>';
        html += '<div class="col-md-6"><strong>Service Category:</strong> <span class="badge bg-label-primary">' + escapeHtml(req.service_category.toUpperCase()) + '</span></div>';
        html += '<div class="col-md-6"><strong>Service Name:</strong> ' + escapeHtml(req.service_name) + '</div>';
        html += '<div class="col-md-6"><strong>Request Type:</strong> ' + escapeHtml(req.request_type) + '</div>';
        html += '<div class="col-md-12"><strong>Address:</strong> ' + escapeHtml(req.request_address) + ', ' + escapeHtml(req.request_city) + ', ' + escapeHtml(req.request_state) + ' - <span class="badge bg-label-info">' + escapeHtml(req.request_pincode) + ' (Verified Area)</span></div>';
        if (req.landmark) {
          html += '<div class="col-md-12"><strong>Landmark:</strong> ' + escapeHtml(req.landmark) + '</div>';
        }
        html += '<div class="col-md-6"><strong>Priority:</strong> <span class="badge bg-dark">' + escapeHtml(req.priority.toUpperCase()) + '</span></div>';
        html += '<div class="col-md-6"><strong>Status:</strong> <span class="badge bg-success">' + escapeHtml(req.request_status.toUpperCase()) + '</span></div>';
        html += '<div class="col-md-6"><strong>Assigned Technician:</strong> ' + escapeHtml(req.assign_to || 'Not Assigned Yet') + '</div>';
        html += '<div class="col-md-6"><strong>AMC Contract No:</strong> ' + escapeHtml(req.amc_contract_number || 'N/A') + '</div>';
        html += '<div class="col-md-6"><strong>Quotation No:</strong> ' + escapeHtml(req.request_quotation_no || 'N/A') + '</div>';
        html += '<div class="col-md-6"><strong>Invoice No:</strong> ' + escapeHtml(req.request_invoice_no || 'N/A') + '</div>';
        if (req.device_details) {
          html += '<div class="col-md-12"><strong>Hardware / Device Specs:</strong><br><div class="p-2 bg-light rounded text-dark">' + escapeHtml(req.device_details) + '</div></div>';
        }
        if (req.description) {
          html += '<div class="col-md-12"><strong>Problem / Requirement Description:</strong><br><div class="p-2 bg-light rounded text-dark">' + escapeHtml(req.description) + '</div></div>';
        }
        if (req.request_status_notes) {
          html += '<div class="col-md-12"><strong>Technician / Status Notes:</strong><br><div class="p-2 bg-warning-subtle rounded text-dark">' + escapeHtml(req.request_status_notes) + '</div></div>';
        }
        html += '<div class="col-md-6 text-muted small">Created At: ' + escapeHtml(req.created_at || 'N/A') + '</div>';
        html += '<div class="col-md-6 text-muted small">Last Updated: ' + escapeHtml(req.updated_at || 'N/A') + '</div>';
        html += '</div>';

        $('#viewDetailsBody').html(html);
        var viewModal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
        viewModal.show();
      }

      function escapeHtml(str) {
        if (!str) return '';
        return String(str)
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }
    </script>
  </body>
</html>
