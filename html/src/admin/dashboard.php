<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/Model/Service.php';
require_once __DIR__ . '/Model/User.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Model/Quotation.php';
require_once __DIR__ . '/Model/Invoice.php';
require_once __DIR__ . '/Model/InvoiceItem.php';
require_once __DIR__ . '/Model/InvoiceVersion.php';
require_once __DIR__ . '/Model/DailyExpense.php';
require_once __DIR__ . '/Repository/ServiceRepository.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';
require_once __DIR__ . '/Repository/QuotationRepository.php';
require_once __DIR__ . '/Repository/InvoiceRepository.php';
require_once __DIR__ . '/Repository/DailyExpenseRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\DailyExpenseRepository;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Repository\InvoiceRepository;
use App\Admin\Repository\QuotationRepository;
use App\Admin\Repository\ServiceRepository;
use App\Admin\Repository\UserRepository;

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

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    header('Location: index.php');
    exit;
}

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$serviceRepo = new ServiceRepository($dbConn);
$userRepo = new UserRepository($dbConn);
$empRepo = new EmployeeRepository($dbConn);
$quotationRepo = new QuotationRepository($dbConn);
$invoiceRepo = new InvoiceRepository($dbConn);
$expenseRepo = new DailyExpenseRepository($dbConn);

$totalServices = count($serviceRepo->findAll());
$totalEmployees = count($empRepo->findAll());
$totalQuotations = count($quotationRepo->findAll());
$totalInvoices = count($invoiceRepo->findAll());

$currentMonthExpenses = $expenseRepo->findByDateRange(date('Y-m-01'), date('Y-m-d'));
$totalMonthExpenseAmt = 0.0;
foreach ($currentMonthExpenses as $exp) {
    $totalMonthExpenseAmt += $exp->getAmount();
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

    <title>Dashboard - tech-xpert Admin</title>
    <meta name="description" content="tech-xpert Admin Dashboard" />

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
    <link rel="stylesheet" href="../../../assets/vendor/libs/apex-charts/apex-charts.css" />

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
        $activePage = 'dashboard';
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
                        <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>
          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
            <div class="container-fluid px-3 px-md-4 flex-grow-1 container-p-y">
              <!-- Welcome & Quick Metrics Row -->
              <div class="row">
                <!-- Welcome Card -->
                <div class="col-lg-12 mb-4">
                  <div class="card bg-label-primary border-0 shadow-sm">
                    <div class="d-flex align-items-center row">
                      <div class="col-sm-8">
                        <div class="card-body">
                          <h4 class="card-title text-primary mb-2">Welcome back, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>! 👋</h4>
                          <p class="mb-0 text-muted">
                            Manage your services, site engineers, staff attendance, payroll, quotations, invoices, and daily expenses all in one place.
                          </p>
                        </div>
                      </div>
                      <div class="col-sm-4 text-center text-sm-end pe-4 d-none d-sm-block">
                        <img src="../../../assets/img/illustrations/man-with-laptop.png" height="110" alt="Dashboard Illustration" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- System Stat Widgets (Filtered by User Role Permissions) -->
                <?php if (hasModulePermission($role, 'callback_requests')): ?>
                  <?php
                  $dashPendingCb = 0;
                  $cbRes = $dbConn->query("SELECT COUNT(*) as cnt FROM callback_requests WHERE status = 'pending'");
                  if ($cbRes) { $row = $cbRes->fetch_assoc(); $dashPendingCb = (int) ($row['cnt'] ?? 0); }
                  ?>
                  <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card h-100 p-3 text-center d-flex flex-column justify-content-between align-items-center">
                      <div class="w-100">
                        <div class="avatar bg-label-danger rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                          <i class="icon-base bx bx-phone-call text-danger icon-lg"></i>
                        </div>
                        <small class="text-muted d-block fw-semibold text-truncate">Callbacks</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1"><?= $dashPendingCb ?></h4>
                      </div>
                      <a href="callback-requests.php" class="btn btn-xs btn-outline-danger mt-3 w-100">View</a>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (hasModulePermission($role, 'services')): ?>
                  <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card h-100 p-3 text-center d-flex flex-column justify-content-between align-items-center">
                      <div class="w-100">
                        <div class="avatar bg-label-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                          <i class="icon-base bx bx-cog text-primary icon-lg"></i>
                        </div>
                        <small class="text-muted d-block fw-semibold text-truncate">Services</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1"><?= $totalServices ?></h4>
                      </div>
                      <a href="services.php" class="btn btn-xs btn-outline-primary mt-3 w-100">Manage</a>
                    </div>
                  </div>
                <?php endif; ?>



                <?php if (hasModulePermission($role, 'employees')): ?>
                  <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card h-100 p-3 text-center d-flex flex-column justify-content-between align-items-center">
                      <div class="w-100">
                        <div class="avatar bg-label-success rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                          <i class="icon-base bx bx-group text-success icon-lg"></i>
                        </div>
                        <small class="text-muted d-block fw-semibold text-truncate">Employees</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1"><?= $totalEmployees ?></h4>
                      </div>
                      <a href="employees.php" class="btn btn-xs btn-outline-success mt-3 w-100">Manage</a>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (hasModulePermission($role, 'quotations')): ?>
                  <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card h-100 p-3 text-center d-flex flex-column justify-content-between align-items-center">
                      <div class="w-100">
                        <div class="avatar bg-label-warning rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                          <i class="icon-base bx bx-receipt text-warning icon-lg"></i>
                        </div>
                        <small class="text-muted d-block fw-semibold text-truncate">Quotations</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1"><?= $totalQuotations ?></h4>
                      </div>
                      <a href="quotations.php" class="btn btn-xs btn-outline-warning mt-3 w-100">Manage</a>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (hasModulePermission($role, 'invoices')): ?>
                  <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card h-100 p-3 text-center d-flex flex-column justify-content-between align-items-center">
                      <div class="w-100">
                        <div class="avatar bg-label-secondary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                          <i class="icon-base bx bx-file text-secondary icon-lg"></i>
                        </div>
                        <small class="text-muted d-block fw-semibold text-truncate">Invoices</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1"><?= $totalInvoices ?></h4>
                      </div>
                      <a href="invoices.php" class="btn btn-xs btn-outline-secondary mt-3 w-100">Manage</a>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (hasModulePermission($role, 'daily_expenses')): ?>
                  <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card h-100 p-3 text-center d-flex flex-column justify-content-between align-items-center">
                      <div class="w-100">
                        <div class="avatar bg-label-danger rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                          <i class="icon-base bx bx-wallet text-danger icon-lg"></i>
                        </div>
                        <small class="text-muted d-block fw-semibold text-truncate">Month Expenses</small>
                        <h4 class="fw-bold text-danger mb-0 mt-1">₹<?= number_format($totalMonthExpenseAmt, 0) ?></h4>
                      </div>
                      <a href="daily-expenses.php" class="btn btn-xs btn-outline-danger mt-3 w-100">Manage</a>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
                <!-- Total Revenue -->
                <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
                  <div class="card">
                    <div class="row row-bordered g-0">
                      <div class="col-lg-8">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <div class="card-title mb-0">
                            <h5 class="m-0 me-2">Total Revenue</h5>
                          </div>
                        </div>
                        <div id="totalRevenueChart" class="px-3"></div>
                      </div>
                      <div class="col-lg-4">
                        <div class="card-body px-xl-9 py-12 d-flex align-items-center flex-column">
                          <div class="text-center mb-6">
                            <div class="btn-group">
                              <button type="button" class="btn btn-outline-primary">2025</button>
                            </div>
                          </div>

                          <div id="growthChart"></div>
                          <div class="text-center fw-medium my-6">62% Company Growth</div>

                          <div class="d-flex gap-11 justify-content-between">
                            <div class="d-flex">
                              <div class="avatar me-2">
                                <span class="avatar-initial rounded-2 bg-label-primary"
                                  ><i class="icon-base bx bx-dollar icon-lg text-primary"></i
                                ></span>
                              </div>
                              <div class="d-flex flex-column">
                                <small>2025</small>
                                <h6 class="mb-0">$32.5k</h6>
                              </div>
                            </div>
                            <div class="d-flex">
                              <div class="avatar me-2">
                                <span class="avatar-initial rounded-2 bg-label-info"
                                  ><i class="icon-base bx bx-wallet icon-lg text-info"></i
                                ></span>
                              </div>
                              <div class="d-flex flex-column">
                                <small>2024</small>
                                <h6 class="mb-0">$41.2k</h6>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Total Revenue -->
                <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
                  <div class="row">
                    <div class="col-6 mb-6 payments">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                              <img src="../../../assets/img/icons/unicons/paypal.png" alt="paypal" class="rounded" />
                            </div>
                          </div>
                          <p class="mb-1">Payments</p>
                          <h4 class="card-title mb-3">$2,456</h4>
                          <small class="text-danger fw-medium"
                            ><i class="icon-base bx bx-down-arrow-alt"></i> -14.82%</small
                          >
                        </div>
                      </div>
                    </div>
                    <div class="col-6 mb-6 transactions">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                              <img src="../../../assets/img/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
                            </div>
                          </div>
                          <p class="mb-1">Transactions</p>
                          <h4 class="card-title mb-3">$14,857</h4>
                          <small class="text-success fw-medium"
                            ><i class="icon-base bx bx-up-arrow-alt"></i> +28.14%</small
                          >
                        </div>
                      </div>
                    </div>
                    <div class="col-12 mb-6 profile-report">
                      <div class="card h-100">
                        <div class="card-body">
                          <div
                            class="d-flex justify-content-between align-items-center flex-sm-row flex-column gap-10 flex-wrap">
                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                              <div class="card-title mb-6">
                                <h5 class="text-nowrap mb-1">Profile Report</h5>
                                <span class="badge bg-label-warning">YEAR 2025</span>
                              </div>
                              <div class="mt-sm-auto">
                                <span class="text-success text-nowrap fw-medium"
                                  ><i class="icon-base bx bx-up-arrow-alt"></i> 68.2%</span
                                >
                                <h4 class="mb-0">$84,686k</h4>
                              </div>
                            </div>
                            <div id="profileReportChart"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <!-- Order Statistics -->
                <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                      <div class="card-title mb-0">
                        <h5 class="mb-1 me-2">Order Statistics</h5>
                        <p class="card-subtitle">42.82k Total Sales</p>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-6">
                        <div class="d-flex flex-column align-items-center gap-1">
                          <h3 class="mb-1">8,258</h3>
                          <small>Total Orders</small>
                        </div>
                        <div id="orderStatisticsChart"></div>
                      </div>
                      <ul class="p-0 m-0">
                        <li class="d-flex align-items-center mb-5">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary"
                              ><i class="icon-base bx bx-mobile-alt"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">Electronic</h6>
                              <small>Mobile, Earbuds, TV</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">82.5k</h6>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-5">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success"
                              ><i class="icon-base bx bx-closet"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">Fashion</h6>
                              <small>T-shirt, Jeans, Shoes</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">23.8k</h6>
                            </div>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <!--/ Order Statistics -->

                <!-- Expense Overview -->
                <div class="col-md-6 col-lg-4 order-1 mb-6">
                  <div class="card h-100">
                    <div class="card-header nav-align-top">
                      <ul class="nav nav-pills flex-wrap row-gap-2" role="tablist">
                        <li class="nav-item">
                          <button
                            type="button"
                            class="nav-link active"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-tabs-line-card-income"
                            aria-controls="navs-tabs-line-card-income"
                            aria-selected="true">
                            Income
                          </button>
                        </li>
                      </ul>
                    </div>
                    <div class="card-body">
                      <div class="tab-content p-0">
                        <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                          <div class="d-flex mb-6">
                            <div class="avatar flex-shrink-0 me-3">
                              <img src="../../../assets/img/icons/unicons/wallet.png" alt="User" />
                            </div>
                            <div>
                              <p class="mb-0">Total Balance</p>
                              <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">$459.10</h6>
                                <small class="text-success fw-medium">
                                  <i class="icon-base bx bx-chevron-up icon-lg"></i>
                                  42.9%
                                </small>
                              </div>
                            </div>
                          </div>
                          <div id="incomeChart"></div>
                          <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                            <div class="flex-shrink-0">
                              <div id="expensesOfWeek"></div>
                            </div>
                            <div>
                              <h6 class="mb-0">Income this week</h6>
                              <small>$39k less than last week</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Expense Overview -->

                <!-- Transactions -->
                <div class="col-md-6 col-lg-4 order-2 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                      <h5 class="card-title m-0 me-2">Transactions</h5>
                    </div>
                    <div class="card-body pt-4">
                      <ul class="p-0 m-0">
                        <li class="d-flex align-items-center mb-6">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../../../assets/img/icons/unicons/paypal.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Paypal</small>
                              <h6 class="fw-normal mb-0">Send money</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">+82.6</h6>
                              <span class="text-body-secondary">USD</span>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-6">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../../../assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Wallet</small>
                              <h6 class="fw-normal mb-0">Mac'D</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">+270.69</h6>
                              <span class="text-body-secondary">USD</span>
                            </div>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <!--/ Transactions -->
              </div>
            </div>
            <!-- / Content -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../../assets/vendor/js/menu.js"></script>

    <!-- Vendors JS -->
    <script src="../../../assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="../../../assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="../../../assets/js/dashboards-analytics.js"></script>
  </body>
</html>
