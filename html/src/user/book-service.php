<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/dbConnection.php';
require_once __DIR__ . '/../admin/Model/ServiceRequest.php';
require_once __DIR__ . '/../admin/Repository/ServiceRequestRepository.php';
require_once __DIR__ . '/../admin/Service/ServiceRequestManagementService.php';
require_once __DIR__ . '/../admin/Model/Service.php';
require_once __DIR__ . '/../admin/Repository/ServiceRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\ServiceRequestRepository;
use App\Admin\Repository\ServiceRepository;
use App\Admin\Service\ServiceRequestManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = !empty($_SESSION['customer_user']) ? (array) $_SESSION['customer_user'] : null;

// Require user to be logged in before booking a service
if ($currentUser === null) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? 'book-service.php';
    $_SESSION['redirect_after_login'] = $requestUri;
    $_SESSION['login_notice'] = 'Please sign in to your account to book a service.';
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$repository = new ServiceRequestRepository($dbConn);
$serviceMgmt = new ServiceRequestManagementService($repository);
$serviceRepo = new ServiceRepository($dbConn);
$dbServices = $serviceRepo->findAll();

$successMessage = null;
$errorMessage = null;

if (isset($_SESSION['profile_saved_success'])) {
    $successMessage = $_SESSION['profile_saved_success'];
    unset($_SESSION['profile_saved_success']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!empty($submittedToken) && hash_equals($csrfToken, $submittedToken)) {
        $result = $serviceMgmt->createServiceRequest($_POST);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = implode(' ', $result['errors']);
        }
    } else {
        $errorMessage = 'Security validation failed. Please refresh the page and try again.';
    }
}

$serviceAreas = $serviceMgmt->getAvailableServiceAreas();
$selectedCategory = (string) ($_GET['category'] ?? '');
$selectedServiceId = (int) ($_GET['service_id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book Service - Customer Portal</title>
  
  <!-- Favicon / Browser Tab Logo Icon -->
  <link rel="icon" type="image/png" href="../../../assets/img/logo.png" />
  <link rel="shortcut icon" type="image/png" href="../../../assets/img/logo.png" />
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />
</head>
<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4 d-flex align-items-center me-3" href="index.php">
        <img src="../../../assets/img/logo.png" alt="Tech-xpert" style="height: 38px; width: auto; object-fit: contain; border-radius: 6px; background: #fff; padding: 2px;" class="me-2" />
        <span>Tech-</span><span style="color: #61BEF1;">xpert</span>
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item me-3"><a class="nav-link" href="index.php">Home</a></li>
          <li class="nav-item me-3"><a class="nav-link active" href="book-service.php">Book Service</a></li>
          <li class="nav-item me-3"><a class="nav-link" href="my-requests.php">Track Request</a></li>
          <li class="nav-item me-3">
            <a href="index.php#requestCallbackModal" class="btn btn-warning btn-sm fw-bold text-dark rounded-pill px-3 shadow-sm" onclick="window.location.href='index.php?callback=1'; return false;">
              <i class="bx bx-phone-call me-1"></i> Request Call Back
            </a>
          </li>
          
          <?php if ($currentUser !== null): ?>
            <li class="nav-item me-2">
              <a href="personal-info.php" class="btn btn-sm btn-outline-light"><i class="bx bx-user me-1"></i><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></a>
            </li>
            <li class="nav-item">
              <a href="logout.php" class="btn btn-sm btn-danger"><i class="bx bx-log-out me-1"></i> Log Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="login.php" class="btn btn-primary btn-sm fw-bold"><i class="bx bx-log-in-circle me-1"></i> Sign In</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-4">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        
        <?php if ($successMessage !== null): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2 fs-4 align-middle"></i>
             <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            <!-- <div class="mt-2"><a href="my-requests.php<?= $currentUser ? '?mobile=' . urlencode((string)($currentUser['mobile_no'] ?? '')) : '' ?>" class="btn btn-sm btn-success">View Request Status</a></div> -->
          </div>
        <?php endif; ?>

        <?php if ($errorMessage !== null): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2 fs-4 align-middle"></i>
            <strong>Error:</strong> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm p-4">
          <div class="card-body">
            <h4 class="card-title fw-bold mb-3"><i class="bx bx-calendar-plus text-primary me-2"></i>Book Service </h4>
            <p class="text-muted mb-4">Fill out the form below to book your service.</p>

            <form method="POST" action="book-service.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
              
              <!-- Customer Info -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-user me-1"></i> Your Contact Information</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label">Full Name <span class="text-danger">*</span></label>
                  <input type="text" name="customer_name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars((string)($currentUser['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                  <input type="text" name="request_by_mobile_no" class="form-control" placeholder="10-digit mobile number" value="<?= htmlspecialchars((string)($currentUser['mobile_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                </div>
                <div class="col-md-12">
                  <label class="form-label">Email Address</label>
                  <input type="email" name="customer_email" class="form-control" placeholder="your.email@example.com" value="<?= htmlspecialchars((string)($currentUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                </div>
              </div>

              <!-- Service Details -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-cog me-1"></i> Service Selection</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label">Service Category <span class="text-danger">*</span></label>
                  <select name="service_category" class="form-select" required>
                    <option value="">-- Select Service Category --</option>
                    <?php foreach ($dbServices as $srv): ?>
                      <?php
                      $sName = $srv->getServiceName();
                      $sId = (int) $srv->getId();
                      $sVal = strtolower(str_replace([' ', '&', '/', '-'], '_', $sName));
                      $isSel = ($selectedServiceId === $sId || $selectedCategory === $sVal || stripos($selectedCategory, strtolower(explode(' ', $sName)[0])) !== false) ? 'selected' : '';
                      ?>
                      <option value="<?= htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') ?>" <?= $isSel ?>>
                        <?= htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <!-- </div> -->
                <div class="col-md-6">
                  <label class="form-label">Request Type <span class="text-danger">*</span></label>
                  <select name="request_type" class="form-select" required>
                    <option value="fresh_installation">Fresh Installation</option>
                    <option value="repair_service">Repair & Maintenance</option>
                    <option value="amc_new_booking">AMC</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Service Title / Summary <span class="text-danger">*</span></label>
                  <input type="text" name="service_name" class="form-control" placeholder="e.g. CCTV Camera Repair or PC Upgrades" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Problem / Requirement Description</label>
                  <textarea name="description" class="form-control" rows="2" placeholder="Describe what service you need..."></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Hardware Specs / Camera Details</label>
                  <textarea name="device_details" class="form-control" rows="2" placeholder="e.g. 4 Cameras or Dell Laptop i5 8GB RAM"></textarea>
                </div>
              </div>

              <!-- Location & Pincode Selection -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-map-pin me-1"></i> Location & Service Area Pincode</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-8">
                  <label class="form-label">Street Address <span class="text-danger">*</span></label>
                  <input type="text" name="request_address" class="form-control" placeholder="House/Shop No., Street Name, Area..." value="<?= htmlspecialchars((string)($currentUser['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Landmark</label>
                  <input type="text" name="landmark" class="form-control" placeholder="e.g. Near City Hospital" value="<?= htmlspecialchars((string)($currentUser['landmark'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Service Pincode <span class="text-danger">* (Select Authorized Pincode)</span></label>
                  <select name="request_pincode" id="user_pincode" class="form-select" required>
                    <option value="">-- Select Your Pincode --</option>
                    <?php foreach ($serviceAreas as $sa): ?>
                      <?php $pSelected = ($currentUser['pincode'] ?? '') === $sa['pincode'] ? 'selected' : ''; ?>
                      <option value="<?= htmlspecialchars($sa['pincode'], ENT_QUOTES, 'UTF-8') ?>" data-city="<?= htmlspecialchars($sa['city'], ENT_QUOTES, 'UTF-8') ?>" data-state="<?= htmlspecialchars($sa['state'], ENT_QUOTES, 'UTF-8') ?>" <?= $pSelected ?>>
                        <?= htmlspecialchars($sa['pincode'] . ' - ' . $sa['area_name'] . ' (' . $sa['city'] . ')', ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">City <i class="bx bx-lock-alt text-muted"></i></label>
                  <input type="text" name="request_city" id="user_city" class="form-control bg-light" placeholder="Auto-filled" value="<?= htmlspecialchars((string)($currentUser['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly required />
                </div>
                <div class="col-md-3">
                  <label class="form-label">State <i class="bx bx-lock-alt text-muted"></i></label>
                  <input type="text" name="request_state" id="user_state" class="form-control bg-light" placeholder="Auto-filled" value="<?= htmlspecialchars((string)($currentUser['state'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly required />
                </div>
              </div>

              <!-- Schedule & Site Inspection Preference -->
              <h6 class="fw-bold text-primary mb-3"><i class="bx bx-calendar-event me-1"></i> Visit Schedule & Inspection Preference</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label">Preferred Visit Date</label>
                  <input type="date" name="preferred_visit_date" class="form-control" min="<?= date('Y-m-d') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Preferred Time Slot</label>
                  <select name="preferred_time_slot" class="form-select">
                    <option value="anytime">Anytime (09:00 AM - 07:00 PM)</option>
                    <option value="morning">Morning (09:00 AM - 12:00 PM)</option>
                    <option value="afternoon">Afternoon (12:00 PM - 04:00 PM)</option>
                    <option value="evening">Evening (04:00 PM - 07:00 PM)</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="site_inspection_required" id="siteInspectionSwitch" value="1" />
                    <label class="form-check-label fw-semibold" for="siteInspectionSwitch">
                      Site Inspection Required <span class="text-muted fw-normal">(Tick if a technician needs to visit for on-site measurement / evaluation)</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold"><i class="bx bx-send me-1"></i> Submit Service Request</button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../../assets/vendor/libs/jquery/jquery.min.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.min.js"></script>
  <script>
    $('#user_pincode').on('change', function () {
      var selected = $(this).find('option:selected');
      var city = selected.data('city') || '';
      var state = selected.data('state') || '';
      if (city) $('#user_city').val(city);
      if (state) $('#user_state').val(state);
    });

    if ($('#user_pincode').val()) {
      $('#user_pincode').trigger('change');
    }
  </script>
</body>
</html>
