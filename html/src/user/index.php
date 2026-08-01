<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/dbConnection.php';
require_once __DIR__ . '/../admin/Model/ServiceArea.php';
require_once __DIR__ . '/../admin/Repository/ServiceAreaRepository.php';
require_once __DIR__ . '/../admin/Model/Service.php';
require_once __DIR__ . '/../admin/Repository/ServiceRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\ServiceAreaRepository;
use App\Admin\Repository\ServiceRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$currentUser = !empty($_SESSION['customer_user']) ? (array) $_SESSION['customer_user'] : null;

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$serviceAreaRepo = new ServiceAreaRepository($dbConn);
$serviceRepo = new ServiceRepository($dbConn);

$serviceAreas = $serviceAreaRepo->findAll();
$services = $serviceRepo->findAll();

$callbackSuccess = false;
$callbackError = null;
$callbackReqNo = '';

// Process Callback Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_callback') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!empty($submittedToken) && hash_equals($csrfToken, $submittedToken)) {
        $cbName = trim((string)($_POST['callback_name'] ?? ''));
        $cbMobile = trim((string)($_POST['callback_mobile'] ?? ''));
        $cbService = trim((string)($_POST['callback_service'] ?? 'cctv_camera'));
        $cbTime = trim((string)($_POST['callback_time'] ?? 'anytime'));
        $cbNote = trim((string)($_POST['callback_note'] ?? ''));

        if (empty($cbName) || empty($cbMobile)) {
            $callbackError = 'Please provide both your name and mobile number.';
        } elseif (!preg_match('/^[0-9]{10}$/', $cbMobile)) {
            $callbackError = 'Please enter a valid 10-digit mobile number.';
        } else {
            $reqNo = 'CB-' . date('Ymd') . '-' . rand(1000, 9999);
            $serviceName = 'Callback Request - ' . ucfirst(str_replace('_', ' ', $cbService));
            $desc = "Call Back Request. Preferred Time Slot: " . ucfirst($cbTime) . ($cbNote ? ". Note: " . $cbNote : "");
            
            $pincode = !empty($serviceAreas) ? $serviceAreas[0]->getPincode() : '492001';

            try {
                $sql = "INSERT INTO service_requests 
                    (service_request_no, customer_name, request_by_mobile_no, service_name, service_category, request_type, description, request_address, request_pincode, preferred_time_slot, request_status) 
                    VALUES (?, ?, ?, ?, ?, 'repair_service', ?, 'Callback Request via Website', ?, ?, 'pending')";
                $stmt = $dbConn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssssssss', $reqNo, $cbName, $cbMobile, $serviceName, $cbService, $desc, $pincode, $cbTime);
                    if ($stmt->execute()) {
                        $callbackSuccess = true;
                        $callbackReqNo = $reqNo;
                    } else {
                        $callbackError = 'Failed to record callback request. Please try again.';
                    }
                    $stmt->close();
                }
            } catch (\Throwable $ex) {
                $callbackError = 'Database connection error: ' . $ex->getMessage();
            }
        }
    } else {
        $callbackError = 'Security validation failed. Please refresh the page and try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>tech-xpert Portal | Premium CCTV, Computer Hardware & AMC Services</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  
  <style>
    :root {
      --primary-color: #696cff;
      --primary-hover: #5f61e6;
      --secondary-color: #8592a3;
      --accent-color: #ffab00;
      --dark-navy: #131722;
      --card-shadow: 0 0.5rem 1.5rem rgba(161, 172, 184, 0.18);
    }

    body {
      background-color: #f5f5f9;
      font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: #566a7f;
      overflow-x: hidden;
    }

    /* Sticky Modern Header */
    .navbar-custom {
      background: rgba(19, 23, 34, 0.94) !important;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      position: sticky;
      top: 0;
      z-index: 1040;
    }

    .brand-icon-box {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #696cff 0%, #393bbf 100%);
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.4rem;
      box-shadow: 0 4px 12px rgba(105, 108, 255, 0.4);
    }

    .nav-link-custom {
      font-weight: 500;
      color: rgba(255, 255, 255, 0.8) !important;
      padding: 0.5rem 1rem !important;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .nav-link-custom:hover, .nav-link-custom.active {
      color: #ffffff !important;
      background: rgba(105, 108, 255, 0.15);
    }

    /* Top Services Hero Carousel */
    .hero-carousel-container {
      position: relative;
      border-radius: 0 0 2rem 2rem;
      overflow: hidden;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }

    .hero-slide-item {
      height: 500px;
      background-size: cover;
      background-position: center;
      position: relative;
    }

    @media (max-width: 768px) {
      .hero-slide-item {
        height: 420px;
      }
    }

    .hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.72) 55%, rgba(15, 23, 42, 0.3) 100%);
      display: flex;
      align-items: center;
    }

    .slide-badge {
      background: rgba(105, 108, 255, 0.25);
      border: 1px solid rgba(105, 108, 255, 0.5);
      color: #8c8eff;
      backdrop-filter: blur(8px);
      padding: 0.4rem 1rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-uppercase: uppercase;
      display: inline-block;
    }

    .carousel-indicators [data-bs-target] {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin: 0 6px;
      background-color: #fff;
      opacity: 0.4;
      transition: all 0.3s ease;
    }

    .carousel-indicators .active {
      width: 34px;
      border-radius: 8px;
      opacity: 1;
      background-color: #696cff;
    }

    .carousel-control-prev, .carousel-control-next {
      width: 5%;
      opacity: 0.8;
    }

    .carousel-control-btn {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      transition: all 0.25s ease;
    }

    .carousel-control-btn:hover {
      background: #696cff;
      border-color: #696cff;
      transform: scale(1.1);
    }

    /* Why Choose Us Feature Cards */
    .feature-card {
      background: #ffffff;
      border-radius: 1.25rem;
      padding: 1.75rem 1.5rem;
      border: 1px solid rgba(105, 108, 255, 0.08);
      box-shadow: 0 4px 20px rgba(161, 172, 184, 0.12);
      transition: all 0.3s ease;
      height: 100%;
    }

    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 30px rgba(105, 108, 255, 0.2);
      border-color: rgba(105, 108, 255, 0.3);
    }

    .feature-icon-box {
      width: 58px;
      height: 58px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 1.25rem;
    }

    /* Service Cards Grid */
    .service-card {
      background: #ffffff;
      border: none;
      border-radius: 1.25rem;
      box-shadow: 0 0.4rem 1.5rem rgba(161, 172, 184, 0.15);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 100%;
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 1.2rem 2.5rem rgba(105, 108, 255, 0.22);
    }

    .service-img-container {
      position: relative;
      height: 240px;
      overflow: hidden;
      flex-shrink: 0;
      background: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .service-card-img {
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      display: block;
      margin: 0 auto;
      transition: transform 0.4s ease;
    }

    .service-card:hover .service-card-img {
      transform: scale(1.05);
    }

    .service-card-body {
      display: flex;
      flex-direction: column;
      flex: 1 1 auto;
      padding: 1.5rem;
    }

    .category-pill {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: rgba(19, 23, 34, 0.82);
      backdrop-filter: blur(8px);
      color: #ffffff;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.35rem 0.85rem;
      border-radius: 30px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Automatic Brands Marquee Scroll (Replaces Service Locations) */
    .brands-section-wrapper {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
      border-radius: 1.5rem;
      padding: 3rem 2rem;
      box-shadow: 0 0.5rem 1.8rem rgba(161, 172, 184, 0.12);
      border: 1px solid rgba(105, 108, 255, 0.12);
      position: relative;
      overflow: hidden;
    }

    .marquee-container {
      overflow: hidden;
      width: 100%;
      position: relative;
      padding: 1rem 0;
      mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
      -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
    }

    .marquee-track {
      display: flex;
      gap: 1.5rem;
      width: max-content;
      animation: logoMarquee 28s linear infinite;
    }

    .marquee-container:hover .marquee-track {
      animation-play-state: paused;
    }

    @keyframes logoMarquee {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    .brand-badge-card {
      background: #ffffff;
      border: 1px solid rgba(161, 172, 184, 0.22);
      border-radius: 1.1rem;
      padding: 0.9rem 1.6rem;
      display: flex;
      align-items: center;
      gap: 0.85rem;
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
      transition: all 0.3s ease;
      min-width: 195px;
      user-select: none;
    }

    .brand-badge-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(105, 108, 255, 0.2);
      border-color: #696cff;
    }

    .brand-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 800;
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    /* Floating Callback Button */
    .floating-callback-btn {
      position: fixed;
      bottom: 2.2rem;
      right: 2.2rem;
      z-index: 1050;
      width: 62px;
      height: 62px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffab00 0%, #ff8c00 100%);
      color: #ffffff;
      border: none;
      box-shadow: 0 10px 25px rgba(255, 171, 0, 0.55);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.7rem;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      animation: pulsePulse 2.2s infinite;
    }

    .floating-callback-btn:hover {
      transform: scale(1.12);
      color: #fff;
      box-shadow: 0 14px 35px rgba(255, 171, 0, 0.75);
    }

    @keyframes pulsePulse {
      0% { box-shadow: 0 0 0 0 rgba(255, 171, 0, 0.7); }
      70% { box-shadow: 0 0 0 16px rgba(255, 171, 0, 0); }
      100% { box-shadow: 0 0 0 0 rgba(255, 171, 0, 0); }
    }

    /* Custom Call Back Banner */
    .callback-cta-banner {
      background: linear-gradient(135deg, #1e1e2d 0%, #111319 100%);
      border-radius: 1.5rem;
      padding: 3rem 2rem;
      color: #ffffff;
      box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.25);
      position: relative;
      overflow: hidden;
    }

    .callback-cta-banner::after {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(105, 108, 255, 0.3) 0%, rgba(0,0,0,0) 70%);
      pointer-events: none;
    }

    /* Modal Tweaks */
    .modal-content-custom {
      border-radius: 1.25rem;
      border: none;
      box-shadow: 0 1.5rem 3.5rem rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }

    .modal-header-custom {
      background: linear-gradient(135deg, #696cff 0%, #393bbf 100%);
      color: #fff;
      padding: 1.5rem 2rem;
      border: none;
    }
  </style>
</head>
<body>

  <!-- Sticky Modern Navigation Header -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4 d-flex align-items-center me-4" href="index.php">
        <img src="/sneat/assets/img/logo.png" alt="tech-xpert" style="height: 42px; width: auto; object-fit: contain; border-radius: 8px; background: #ffffff; padding: 2px;" class="me-2 shadow-sm" />
        <span class="text-white">tech-</span><span style="color: #696cff;">xpert</span>
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item me-1">
            <a class="nav-link nav-link-custom active" href="index.php"><i class="bx bx-home-alt me-1"></i> Home</a>
          </li>
          <li class="nav-item me-1">
            <a class="nav-link nav-link-custom" href="book-service.php"><i class="bx bx-calendar-plus me-1"></i> Book Service</a>
          </li>
          <li class="nav-item me-1">
            <a class="nav-link nav-link-custom" href="my-requests.php"><i class="bx bx-search-alt me-1"></i> Track Requests</a>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <!-- Request a Call Back Button in Header -->
          <button type="button" class="btn btn-warning fw-bold text-dark rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestCallbackModal">
            <i class="bx bx-phone-call me-1 bx-tada"></i> Request Call Back
          </button>

          <?php if ($currentUser !== null): ?>
            <a href="personal-info.php" class="btn btn-outline-light btn-sm rounded-pill fw-semibold ms-2">
              <i class="bx bx-user-circle me-1"></i> <?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill fw-semibold">
              <i class="bx bx-log-out"></i>
            </a>
          <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm rounded-pill fw-bold px-3 ms-2">
              <i class="bx bx-log-in-circle me-1"></i> Sign In
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- Alert Notifications for Callback Result -->
  <?php if ($callbackError !== null): ?>
    <div class="container mt-3">
      <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-0" role="alert">
        <i class="bx bx-error-circle me-2 fs-5 align-middle"></i> <?= htmlspecialchars($callbackError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Services Image Carousel at Top -->
  <div class="hero-carousel-container">
    <div id="topServicesCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4500">
      
      <!-- Slide Indicators -->
      <div class="carousel-indicators mb-4">
        <button type="button" data-bs-target="#topServicesCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#topServicesCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#topServicesCarousel" data-bs-slide-to="2"></button>
      </div>

      <div class="carousel-inner">
        <!-- Slide 1: CCTV Surveillance -->
        <div class="carousel-item active hero-slide-item" style="background-image: url('/sneat/html/uploads/services/cctv_service.png');">
          <div class="hero-overlay">
            <div class="container">
              <div class="row align-items-center">
                <div class="col-lg-8 text-white">
                  <span class="slide-badge mb-3"><i class="bx bx-camcorder me-1"></i> 24/7 Security & Surveillance</span>
                  <h1 class="display-4 fw-extrabold mb-3 text-white">Smart CCTV Installation & HD Monitoring</h1>
                  <p class="lead mb-4 text-light opacity-90">Protect your home, office, and enterprise with crystal-clear IP cameras, night vision, and instant mobile view setup.</p>
                  <div class="d-flex flex-wrap gap-3">
                    <a href="book-service.php?category=cctv_camera" class="btn btn-primary btn-lg fw-bold rounded-pill px-4 shadow"><i class="bx bx-calendar-check me-2"></i> Book CCTV Service</a>
                    <button type="button" class="btn btn-outline-light btn-lg fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="cctv_camera">
                      <i class="bx bx-phone-call me-2"></i> Request Call Back
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Slide 2: Computer Hardware -->
        <div class="carousel-item hero-slide-item" style="background-image: url('/sneat/html/uploads/services/computer_service.png');">
          <div class="hero-overlay">
            <div class="container">
              <div class="row align-items-center">
                <div class="col-lg-8 text-white">
                  <span class="slide-badge mb-3"><i class="bx bx-laptop me-1"></i> Expert IT & Hardware Repair</span>
                  <h1 class="display-4 fw-extrabold mb-3 text-white">Computer Repair, Upgrades & Support</h1>
                  <p class="lead mb-4 text-light opacity-90">Fast doorstep repair for desktops, laptops, servers, and networking hardware by certified field engineers.</p>
                  <div class="d-flex flex-wrap gap-3">
                    <a href="book-service.php?category=computer_hardware" class="btn btn-primary btn-lg fw-bold rounded-pill px-4 shadow"><i class="bx bx-wrench me-2"></i> Book Hardware Repair</a>
                    <button type="button" class="btn btn-outline-light btn-lg fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="computer_hardware">
                      <i class="bx bx-phone-call me-2"></i> Request Call Back
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Slide 3: AMC Services -->
        <div class="carousel-item hero-slide-item" style="background-image: url('/sneat/html/uploads/services/amc_service.png');">
          <div class="hero-overlay">
            <div class="container">
              <div class="row align-items-center">
                <div class="col-lg-8 text-white">
                  <span class="slide-badge mb-3"><i class="bx bx-shield-alt-2 me-1"></i> Hassle-Free AMC Protection</span>
                  <h1 class="display-4 fw-extrabold mb-3 text-white">Annual Maintenance Contracts (AMC)</h1>
                  <p class="lead mb-4 text-light opacity-90">Zero downtime with proactive quarterly checkups, priority technician dispatch, and zero service charges.</p>
                  <div class="d-flex flex-wrap gap-3">
                    <a href="book-service.php?category=amc_contract" class="btn btn-primary btn-lg fw-bold rounded-pill px-4 shadow"><i class="bx bx-award me-2"></i> Explore AMC Plans</a>
                    <button type="button" class="btn btn-outline-light btn-lg fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="amc_contract">
                      <i class="bx bx-phone-call me-2"></i> Request Call Back
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Controls -->
      <button class="carousel-control-prev" type="button" data-bs-target="#topServicesCarousel" data-bs-slide="prev">
        <span class="carousel-control-btn"><i class="bx bx-chevron-left"></i></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#topServicesCarousel" data-bs-slide="next">
        <span class="carousel-control-btn"><i class="bx bx-chevron-right"></i></span>
      </button>
    </div>
  </div>

  <!-- Main Content Container -->
  <div class="container my-5">

    <!-- Why Choose Us Features -->
    <div class="row g-4 mb-5">
      <div class="col-md-3 col-sm-6">
        <div class="feature-card text-center">
          <div class="feature-icon-box bg-label-primary text-primary mx-auto">
            <i class="bx bx-time-five"></i>
          </div>
          <h5 class="fw-bold mb-2">Rapid Response</h5>
          <p class="text-muted fs-7 mb-0">Technician dispatch within 2 hours of booking confirm.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="feature-card text-center">
          <div class="feature-icon-box bg-label-success text-success mx-auto">
            <i class="bx bx-user-check"></i>
          </div>
          <h5 class="fw-bold mb-2">Certified Engineers</h5>
          <p class="text-muted fs-7 mb-0">Background-verified & experienced hardware engineers.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="feature-card text-center">
          <div class="feature-icon-box bg-label-warning text-warning mx-auto">
            <i class="bx bx-dollar-circle"></i>
          </div>
          <h5 class="fw-bold mb-2">Transparent Pricing</h5>
          <p class="text-muted fs-7 mb-0">Clear upfront quotation with no hidden or extra charges.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="feature-card text-center">
          <div class="feature-icon-box bg-label-info text-info mx-auto">
            <i class="bx bx-badge-check"></i>
          </div>
          <h5 class="fw-bold mb-2">Genuine Warranty</h5>
          <p class="text-muted fs-7 mb-0">100% genuine OEM replacement parts with warranty.</p>
        </div>
      </div>
    </div>

    <!-- Our Offered Services Section -->
    <div class="mb-5">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary px-3 py-2 rounded-pill fs-7 fw-bold text-uppercase mb-2">Solutions We Provide</span>
        <h2 class="fw-extrabold text-dark">Our Offered Services</h2>
        <p class="text-muted max-w-600 mx-auto">Professional technician visit, maintenance, installation, and repairs tailored to your needs.</p>
      </div>
      
      <div class="row g-4">
        <?php if (count($services) === 0): ?>
          <div class="col-12 text-center text-muted py-5">No service offerings available at the moment.</div>
        <?php else: ?>
          <?php foreach ($services as $srv): ?>
            <?php
            $srvName = $srv->getServiceName();
            $srvImg = $srv->getServiceImage();
            
            $cleanPath = ltrim((string)$srvImg, '/');
            if (strpos($cleanPath, 'html/') === 0) {
                $cleanPath = substr($cleanPath, 5);
            }
            $imgSrc = '/sneat/html/' . ($cleanPath ?: 'uploads/services/cctv_service.png');
            
            $catParam = 'other';
            $badgeText = 'General Service';
            if (stripos($srvName, 'CCTV') !== false) { 
                $catParam = 'cctv_camera'; 
                $badgeText = 'Security System';
            } elseif (stripos($srvName, 'Computer') !== false || stripos($srvName, 'Hardware') !== false) { 
                $catParam = 'computer_hardware'; 
                $badgeText = 'Hardware & IT';
            } elseif (stripos($srvName, 'AMC') !== false || stripos($srvName, 'Contract') !== false) { 
                $catParam = 'amc_contract'; 
                $badgeText = 'Maintenance AMC';
            }
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="card service-card">
                <div class="service-img-container">
                  <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" class="service-card-img" alt="<?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?>" />
                  <span class="category-pill"><i class="bx bx-check-circle me-1 text-primary"></i> <?= htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card-body service-card-body">
                  <h5 class="fw-bold text-dark mb-4"><?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?></h5>
                  
                  <div class="mt-auto d-flex gap-2 pt-2">
                    <a href="book-service.php?category=<?= urlencode($catParam) ?>&service_id=<?= $srv->getId() ?>" class="btn btn-primary fw-bold flex-grow-1 py-2 shadow-sm">
                      <i class="bx bx-calendar-plus me-1"></i> Book Now
                    </a>
                    <button type="button" class="btn btn-outline-primary fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="<?= htmlspecialchars($catParam, ENT_QUOTES, 'UTF-8') ?>" title="Quick Call Back">
                      <i class="bx bx-phone-call"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Brands Logo Automatic Scroll Section (Replaces Authorized Service Locations) -->
    <div class="brands-section-wrapper mb-5">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary px-3 py-2 rounded-pill fs-7 fw-bold text-uppercase mb-2">Industry Partners & Products</span>
        <h3 class="fw-extrabold text-dark mb-1">Top Brands We Install & Support</h3>
        <p class="text-muted">We work directly with leading security, IT hardware, and networking manufacturers.</p>
      </div>

      <div class="marquee-container">
        <div class="marquee-track">
          <!-- Brand Badges (Track 1) -->
          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #E60012;"><i class="bx bx-video"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Hikvision</div>
              <div class="text-muted fs-7">CCTV & Cameras</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #004B87;"><i class="bx bx-camcorder"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">CP Plus</div>
              <div class="text-muted fs-7">HD Surveillance</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #0082C8;"><i class="bx bx-shield"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Dahua</div>
              <div class="text-muted fs-7">IP Cameras</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #007DB8;"><i class="bx bx-desktop"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Dell</div>
              <div class="text-muted fs-7">Desktops & Servers</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #0096D6;"><i class="bx bx-laptop"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">HP</div>
              <div class="text-muted fs-7">Laptops & Printers</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #E2231A;"><i class="bx bx-chip"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Lenovo</div>
              <div class="text-muted fs-7">Workstations</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #1BA0D7;"><i class="bx bx-wifi"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Cisco</div>
              <div class="text-muted fs-7">Networking Gear</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #0068B5;"><i class="bx bx-microchip"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Intel</div>
              <div class="text-muted fs-7">Processors & Boards</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #EE3124;"><i class="bx bx-lock-alt"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Honeywell</div>
              <div class="text-muted fs-7">Security Systems</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #00A389;"><i class="bx bx-broadcast"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">TP-Link</div>
              <div class="text-muted fs-7">Routers & Switches</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #111111;"><i class="bx bx-hard-drive"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Asus</div>
              <div class="text-muted fs-7">Hardware & Motherboards</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #008752;"><i class="bx bx-plug"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">APC</div>
              <div class="text-muted fs-7">UPS & Power Backup</div>
            </div>
          </div>

          <!-- Duplicate Brand Badges (Track 2 for seamless loop) -->
          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #E60012;"><i class="bx bx-video"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Hikvision</div>
              <div class="text-muted fs-7">CCTV & Cameras</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #004B87;"><i class="bx bx-camcorder"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">CP Plus</div>
              <div class="text-muted fs-7">HD Surveillance</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #0082C8;"><i class="bx bx-shield"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Dahua</div>
              <div class="text-muted fs-7">IP Cameras</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #007DB8;"><i class="bx bx-desktop"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Dell</div>
              <div class="text-muted fs-7">Desktops & Servers</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #0096D6;"><i class="bx bx-laptop"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">HP</div>
              <div class="text-muted fs-7">Laptops & Printers</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #E2231A;"><i class="bx bx-chip"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Lenovo</div>
              <div class="text-muted fs-7">Workstations</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #1BA0D7;"><i class="bx bx-wifi"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Cisco</div>
              <div class="text-muted fs-7">Networking Gear</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #0068B5;"><i class="bx bx-microchip"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Intel</div>
              <div class="text-muted fs-7">Processors & Boards</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #EE3124;"><i class="bx bx-lock-alt"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Honeywell</div>
              <div class="text-muted fs-7">Security Systems</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #00A389;"><i class="bx bx-broadcast"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">TP-Link</div>
              <div class="text-muted fs-7">Routers & Switches</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #111111;"><i class="bx bx-hard-drive"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Asus</div>
              <div class="text-muted fs-7">Hardware & Motherboards</div>
            </div>
          </div>

          <div class="brand-badge-card">
            <div class="brand-icon" style="background: #008752;"><i class="bx bx-plug"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">APC</div>
              <div class="text-muted fs-7">UPS & Power Backup</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Call Back CTA Banner -->
    <div class="callback-cta-banner text-center text-lg-start">
      <div class="row align-items-center">
        <div class="col-lg-8 mb-3 mb-lg-0">
          <h3 class="fw-bold text-white mb-2"><i class="bx bx-headphone text-warning me-2"></i> Need Instant Assistance or Custom Advice?</h3>
          <p class="mb-0 text-light opacity-90">Speak directly with our technical support team. Request a call back and we'll contact you within 15 minutes.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <button type="button" class="btn btn-warning btn-lg fw-bold rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#requestCallbackModal">
            <i class="bx bx-phone-call me-2"></i> Request Call Back
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- Floating Action Button for Call Back -->
  <button type="button" class="floating-callback-btn" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" title="Request Call Back">
    <i class="bx bx-phone-call"></i>
  </button>

  <!-- Interactive Modal: Request a Call Back -->
  <div class="modal fade" id="requestCallbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content modal-content-custom">
        <div class="modal-header modal-header-custom d-flex align-items-center justify-content-between">
          <h5 class="modal-title fw-bold text-white mb-0"><i class="bx bx-phone-call me-2"></i> Request a Call Back</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="request_callback" />
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
          
          <div class="modal-body p-4">
            <p class="text-muted fs-7 mb-4">Provide your details below and our service expert will give you a call back shortly.</p>
            
            <div class="mb-3">
              <label for="cb_name" class="form-label fw-bold text-dark fs-7">Your Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="cb_name" name="callback_name" required placeholder="e.g. John Doe" value="<?= htmlspecialchars($currentUser['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            
            <div class="mb-3">
              <label for="cb_mobile" class="form-label fw-bold text-dark fs-7">Mobile Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bx bx-phone"></i></span>
                <input type="tel" class="form-control" id="cb_mobile" name="callback_mobile" required pattern="[0-9]{10}" placeholder="10-digit mobile number" value="<?= htmlspecialchars($currentUser['mobile_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
              </div>
            </div>

            <div class="mb-3">
              <label for="cb_service" class="form-label fw-bold text-dark fs-7">Service Interested In</label>
              <select class="form-select" id="cb_service" name="callback_service">
                <option value="cctv_camera">CCTV Camera & Security</option>
                <option value="computer_hardware">Computer Hardware & IT Support</option>
                <option value="amc_contract">Annual Maintenance Contract (AMC)</option>
                <option value="other">General Inquiry / Other</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="cb_time" class="form-label fw-bold text-dark fs-7">Preferred Callback Time</label>
              <select class="form-select" id="cb_time" name="callback_time">
                <option value="anytime">Anytime / As Soon As Possible</option>
                <option value="morning">Morning (9 AM - 12 PM)</option>
                <option value="afternoon">Afternoon (12 PM - 4 PM)</option>
                <option value="evening">Evening (4 PM - 8 PM)</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="cb_note" class="form-label fw-bold text-dark fs-7">Requirement Notes (Optional)</label>
              <textarea class="form-control" id="cb_note" name="callback_note" rows="2" placeholder="Brief details about issue or service required..."></textarea>
            </div>
          </div>

          <div class="modal-footer bg-light px-4 py-3 border-top-0">
            <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bx bx-paper-plane me-1"></i> Submit Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Callback Success Confirmation Modal -->
  <?php if ($callbackSuccess): ?>
    <div class="modal fade" id="callbackSuccessModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom text-center p-4">
          <div class="modal-body">
            <div class="mb-3 text-success">
              <i class="bx bx-check-circle" style="font-size: 4.5rem;"></i>
            </div>
            <h3 class="fw-extrabold text-dark mb-2">Request Received!</h3>
            <p class="text-muted mb-3">Thank you! Your call back request has been recorded successfully.</p>
            <div class="badge bg-label-primary fs-6 px-3 py-2 rounded-pill mb-4">
              Request Ref: <strong><?= htmlspecialchars($callbackReqNo, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <p class="fs-7 text-muted mb-4">Our expert support engineer will call your mobile number within 15 minutes.</p>
            <button type="button" class="btn btn-primary fw-bold px-4 rounded-pill" data-bs-dismiss="modal">Got It</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="bg-dark text-white py-5 mt-5" style="background-color: #131722 !important;">
    <div class="container">
      <div class="row g-4 mb-4">
        <div class="col-lg-4">
          <a class="navbar-brand fw-bold fs-4 d-flex align-items-center mb-3" href="index.php">
            <img src="/sneat/assets/img/logo.png" alt="tech-xpert" style="height: 48px; width: auto; object-fit: contain; border-radius: 8px; background: #ffffff; padding: 3px;" class="me-2 shadow-sm" />
            <span class="text-white">tech-</span><span style="color: #696cff;">xpert</span>
          </a>
          <p class="text-muted fs-7">Your trusted partner for professional CCTV installation, computer hardware maintenance, network setup, and Annual Maintenance Contracts (AMC).</p>
        </div>
        <div class="col-lg-4">
          <h6 class="fw-bold text-white mb-3">Quick Navigation</h6>
          <ul class="list-unstyled text-muted fs-7 mb-0">
            <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1"></i> Home Portal</a></li>
            <li class="mb-2"><a href="book-service.php" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1"></i> Book a Service Request</a></li>
            <li class="mb-2"><a href="my-requests.php" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1"></i> Track Existing Request</a></li>
            <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1"></i> Request Immediate Call Back</a></li>
          </ul>
        </div>
        <div class="col-lg-4">
          <h6 class="fw-bold text-white mb-3">Customer Support</h6>
          <p class="text-muted fs-7 mb-2"><i class="bx bx-phone me-2 text-primary"></i> Toll-Free Support: +91 (800) 123-4567</p>
          <p class="text-muted fs-7 mb-2"><i class="bx bx-envelope me-2 text-primary"></i> Support Email: support@techxpert.com</p>
          <p class="text-muted fs-7"><i class="bx bx-time me-2 text-primary"></i> Hours: Monday - Saturday (9:00 AM - 8:00 PM)</p>
        </div>
      </div>
      <hr class="border-secondary opacity-25 my-4" />
      <div class="text-center text-muted fs-7">
        <p class="mb-0">&copy; <?= date('Y') ?> tech-xpert Portal. All Rights Reserved.</p>
      </div>
    </div>
  </footer>

  <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.js"></script>
  <script>
    $(document).ready(function() {
      // Auto open modal if callback parameter is present
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('callback') === '1') {
        var cbModal = new bootstrap.Modal(document.getElementById('requestCallbackModal'));
        cbModal.show();
      }

      // Auto show success modal if callback submitted successfully
      <?php if ($callbackSuccess): ?>
        var successModal = new bootstrap.Modal(document.getElementById('callbackSuccessModal'));
        successModal.show();
      <?php endif; ?>

      // Pre-select service when clicking callback from specific service card
      $('#requestCallbackModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        if (button && button.data('service')) {
          var serviceCat = button.data('service');
          $(this).find('#cb_service').val(serviceCat);
        }
      });
    });
  </script>
</body>
</html>
