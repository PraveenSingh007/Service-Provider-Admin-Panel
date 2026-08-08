<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/CallbackRequest.php';
require_once __DIR__ . '/Repository/CallbackRequestRepository.php';
require_once __DIR__ . '/permissions.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\CallbackRequestRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$activePage = 'callback-requests';

$user = !empty($_SESSION['user']) ? (array) $_SESSION['user'] : (!empty($_SESSION['admin_user']) ? (array) $_SESSION['admin_user'] : null);

if ($user === null) {
    header('Location: index.php');
    exit;
}

$userRole = (string) ($user['role'] ?? 'admin');
if (!hasModulePermission($userRole, 'callback_requests')) {
    header('Location: dashboard.php?error=unauthorized');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$cbRepo = new CallbackRequestRepository($dbConn);

$successMsg = null;
$errorMsg = null;

// Handle Actions (Update Status or Delete)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $errorMsg = 'Security validation failed. Please refresh the page.';
    } else {
        $action = (string) $_POST['action'];
        $cbId = (int) ($_POST['callback_id'] ?? 0);

        if ($action === 'update_status') {
            $newStatus = (string) ($_POST['status'] ?? 'pending');
            if ($cbRepo->updateStatus($cbId, $newStatus)) {
                $successMsg = 'Callback request status updated successfully to ' . ucfirst($newStatus) . '!';
            } else {
                $errorMsg = 'Failed to update callback request status.';
            }
        } elseif ($action === 'delete') {
            if ($cbRepo->delete($cbId)) {
                $successMsg = 'Callback request entry deleted successfully!';
            } else {
                $errorMsg = 'Failed to delete callback request entry.';
            }
        }
    }
}

$filterStatus = isset($_GET['status']) ? (string) $_GET['status'] : null;
$callbackRequests = $cbRepo->findAll($filterStatus);

// Statistics
$allRequests = $cbRepo->findAll();
$totalCount = count($allRequests);
$pendingCount = 0;
$contactedCount = 0;
$completedCount = 0;
$cancelledCount = 0;

foreach ($allRequests as $item) {
    switch ($item->getStatus()) {
        case 'pending': $pendingCount++; break;
        case 'contacted': $contactedCount++; break;
        case 'completed': $completedCount++; break;
        case 'cancelled': $cancelledCount++; break;
    }
}
?>
<!doctype html>
<html lang="en" class="light-style layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Callback Requests Management | Admin Panel</title>
  
  <!-- Favicon / Browser Tab Logo Icon -->
  <link rel="icon" type="image/png" href="../../../assets/img/logo.png" />
  <link rel="shortcut icon" type="image/png" href="../../../assets/img/logo.png" />
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/boxicons.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../../../assets/css/demo.min.css" />
  <link rel="stylesheet" href="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.css" />
  
  <!-- DataTables & Buttons CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />
  
  <style>
    /* Premium Export Buttons for DataTables */
    .btn-icon-export {
      padding: 0.45rem 0.85rem !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      border-radius: 0.375rem !important;
      font-size: 0.85rem !important;
      font-weight: 600 !important;
      transition: all 0.2s ease-in-out !important;
    }
    .btn-icon-export i {
      font-size: 1.15rem !important;
      color: #ffffff !important;
      display: inline-block !important;
      vertical-align: middle !important;
    }
    .btn-icon-export:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.18);
    }
    .btn-export-copy { background-color: #61BEF1 !important; color: #ffffff !important; border: none !important; }
    .btn-export-copy:hover { background-color: #5f61e6 !important; color: #ffffff !important; }
    .btn-export-csv { background-color: #03c3ec !important; color: #ffffff !important; border: none !important; }
    .btn-export-csv:hover { background-color: #02acd0 !important; color: #ffffff !important; }
    .btn-export-excel { background-color: #71dd37 !important; color: #ffffff !important; border: none !important; }
    .btn-export-excel:hover { background-color: #64c631 !important; color: #ffffff !important; }
    .btn-export-pdf { background-color: #ff3e1d !important; color: #ffffff !important; border: none !important; }
    .btn-export-pdf:hover { background-color: #e6381a !important; color: #ffffff !important; }
    .btn-export-print { background-color: #8592a3 !important; color: #ffffff !important; border: none !important; }
    .btn-export-print:hover { background-color: #788393 !important; color: #ffffff !important; }
  </style>

  <script src="../../../assets/vendor/js/helpers.min.js"></script>
  <script src="../../../assets/js/config.min.js"></script>
</head>
<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      
      <!-- Sidebar -->
      <?php include __DIR__ . '/sidebar.php'; ?>

      <div class="layout-page">
        <!-- Navbar -->
        <?php include __DIR__ . '/navbar.php'; ?>

        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h4 class="fw-bold py-1 mb-1"><span class="text-muted fw-light">Dashboard /</span> Callback Requests</h4>
                <p class="text-muted mb-0">View, manage, and respond to website immediate call-back requests.</p>
              </div>
            </div>

            <?php if ($successMsg !== null): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-1"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <?php if ($errorMsg !== null): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-1"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <!-- Stat Summary Cards -->
            <div class="row g-4 mb-4">
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span class="text-heading">Total Callbacks</span>
                        <div class="d-flex align-items-center my-1">
                          <h4 class="mb-0 me-2"><?= $totalCount ?></h4>
                        </div>
                        <small class="text-muted">All time inquiries</small>
                      </div>
                      <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary text-primary">
                          <i class="bx bx-phone-call fs-4"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span class="text-heading">Pending Callbacks</span>
                        <div class="d-flex align-items-center my-1">
                          <h4 class="mb-0 me-2 text-danger"><?= $pendingCount ?></h4>
                        </div>
                        <small class="text-muted">Awaiting response</small>
                      </div>
                      <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger text-danger">
                          <i class="bx bx-time-five fs-4"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span class="text-heading">Contacted</span>
                        <div class="d-flex align-items-center my-1">
                          <h4 class="mb-0 me-2 text-warning"><?= $contactedCount ?></h4>
                        </div>
                        <small class="text-muted">In discussion</small>
                      </div>
                      <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning text-warning">
                          <i class="bx bx-conversation fs-4"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span class="text-heading">Completed</span>
                        <div class="d-flex align-items-center my-1">
                          <h4 class="mb-0 me-2 text-success"><?= $completedCount ?></h4>
                        </div>
                        <small class="text-muted">Resolved & serviced</small>
                      </div>
                      <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success text-success">
                          <i class="bx bx-check-double fs-4"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Callback Requests Datatable -->
            <div class="card">
              <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 class="card-title mb-0">Callback Requests List</h5>
                <div class="btn-group btn-group-sm" role="group">
                  <a href="callback-requests.php" class="btn btn-outline-secondary <?= $filterStatus === null ? 'active' : '' ?>">All</a>
                  <a href="callback-requests.php?status=pending" class="btn btn-outline-danger <?= $filterStatus === 'pending' ? 'active' : '' ?>">Pending (<?= $pendingCount ?>)</a>
                  <a href="callback-requests.php?status=contacted" class="btn btn-outline-warning <?= $filterStatus === 'contacted' ? 'active' : '' ?>">Contacted</a>
                  <a href="callback-requests.php?status=completed" class="btn btn-outline-success <?= $filterStatus === 'completed' ? 'active' : '' ?>">Completed</a>
                </div>
              </div>

              <div class="table-responsive text-nowrap p-3">
                <table id="callbacksTable" class="table table-hover table-striped w-100">
                  <thead>
                    <tr>
                      <th>Callback No</th>
                      <th>Customer Name</th>
                      <th>Mobile Number</th>
                      <th>Service Category</th>
                      <th>Preferred Time</th>
                      <th>Notes / Query</th>
                      <th>Status</th>
                      <th>Request Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody class="table-border-bottom-0">
                    <?php if (empty($callbackRequests)): ?>
                      <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No callback requests found matching criteria.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($callbackRequests as $cb): ?>
                        <?php
                        $st = $cb->getStatus();
                        $badgeClass = 'bg-label-secondary';
                        if ($st === 'pending') $badgeClass = 'bg-label-danger';
                        elseif ($st === 'contacted') $badgeClass = 'bg-label-warning';
                        elseif ($st === 'completed') $badgeClass = 'bg-label-success';
                        elseif ($st === 'cancelled') $badgeClass = 'bg-label-dark';
                        ?>
                        <tr>
                          <td><strong><?= htmlspecialchars($cb->getCallbackNo(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                          <td>
                            <span class="fw-semibold"><?= htmlspecialchars($cb->getCustomerName(), ENT_QUOTES, 'UTF-8') ?></span>
                          </td>
                          <td>
                            <a href="tel:<?= htmlspecialchars($cb->getMobileNo(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-xs btn-outline-primary fw-bold">
                              <i class="bx bx-phone me-1"></i> <?= htmlspecialchars($cb->getMobileNo(), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                          </td>
                          <td>
                            <span class="badge bg-label-info text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $cb->getServiceCategory()), ENT_QUOTES, 'UTF-8') ?></span>
                          </td>
                          <td><i class="bx bx-time me-1 text-muted"></i> <?= htmlspecialchars(ucfirst($cb->getPreferredTimeSlot()), ENT_QUOTES, 'UTF-8') ?></td>
                          <td>
                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($cb->getNote() ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>">
                              <?= htmlspecialchars($cb->getNote() ?: 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                          </td>
                          <td>
                            <!-- Instant Inline Status Dropdown Select -->
                            <form method="POST" action="callback-requests.php" class="d-inline-block">
                              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                              <input type="hidden" name="action" value="update_status" />
                              <input type="hidden" name="callback_id" value="<?= $cb->getId() ?>" />
                              
                              <select name="status" class="form-select form-select-sm fw-bold <?= $st === 'pending' ? 'border-danger text-danger bg-label-danger' : ($st === 'contacted' ? 'border-warning text-warning bg-label-warning' : ($st === 'completed' ? 'border-success text-success bg-label-success' : 'border-secondary text-secondary bg-label-secondary')) ?>" onchange="this.form.submit()" style="cursor: pointer; min-width: 130px;">
                                <option value="pending" <?= $st === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                <option value="contacted" <?= $st === 'contacted' ? 'selected' : '' ?>>📞 Contacted</option>
                                <option value="completed" <?= $st === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                                <option value="cancelled" <?= $st === 'cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                              </select>
                            </form>
                          </td>
                          <td><small class="text-muted"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($cb->getCreatedAt() ?? 'now')), ENT_QUOTES, 'UTF-8') ?></small></td>
                          <td>
                            <!-- Direct Visible Action Buttons -->
                            <div class="d-flex align-items-center gap-1">
                              <a href="tel:<?= htmlspecialchars($cb->getMobileNo(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-success fw-bold px-2 py-1" title="Call Customer">
                                <i class="bx bx-phone-call me-1"></i> Call
                              </a>

                              <form method="POST" action="callback-requests.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this callback request entry?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="callback_id" value="<?= $cb->getId() ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Entry">
                                  <i class="bx bx-trash me-1"></i> Delete
                                </button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
          
          <footer class="content-footer footer bg-footer-theme">
            <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
              <div class="mb-2 mb-md-0">&copy; <?= date('Y') ?> Tech-xpert Admin Panel.</div>
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
      $('#callbacksTable').DataTable({
        dom: '<"row mb-3 align-items-center"<"col-md-4"l><"col-md-8 d-flex justify-content-end align-items-center gap-2"fB>>' +
             '<"table-responsive"t>' +
             '<"row mt-3 align-items-center"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>',
        buttons: [
          {
            extend: 'copyHtml5',
            className: 'btn btn-icon-export btn-export-copy me-1',
            text: '<i class="bx bx-copy me-1"></i>',
            titleAttr: 'Copy to Clipboard',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
          },
          {
            extend: 'csvHtml5',
            className: 'btn btn-icon-export btn-export-csv me-1',
            text: '<i class="bx bx-file me-1"></i>',
            titleAttr: 'Export as CSV',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
          },
          {
            extend: 'excelHtml5',
            className: 'btn btn-icon-export btn-export-excel me-1',
            text: '<i class="bx bx-spreadsheet me-1"></i>',
            titleAttr: 'Export as Excel',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
          },
          {
            extend: 'pdfHtml5',
            className: 'btn btn-icon-export btn-export-pdf me-1',
            text: '<i class="bx bxs-file-pdf me-1"></i>',
            titleAttr: 'Export as PDF',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
          },
          {
            extend: 'print',
            className: 'btn btn-icon-export btn-export-print',
            text: '<i class="bx bx-printer me-1"></i>',
            titleAttr: 'Print Table',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
          }
        ],
        order: [[7, 'desc']], // Sort by Request Date descending
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Search callbacks...",
          lengthMenu: "Show _MENU_ entries"
        }
      });
    });
  </script>
</body>
</html>
