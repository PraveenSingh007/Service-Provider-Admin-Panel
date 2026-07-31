<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/dbConnection.php';
require_once __DIR__ . '/../admin/Repository/ServiceAreaRepository.php';
require_once __DIR__ . '/../admin/Repository/ServiceRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\ServiceAreaRepository;
use App\Admin\Repository\ServiceRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = !empty($_SESSION['customer_user']) ? (array) $_SESSION['customer_user'] : null;

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$serviceAreaRepo = new ServiceAreaRepository($dbConn);
$serviceRepo = new ServiceRepository($dbConn);

$serviceAreas = $serviceAreaRepo->findAll();
$services = $serviceRepo->findAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Service Portal - CCTV, Computer Hardware & AMC Services</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
  
  <style>
    body { background-color: #f5f5f9; font-family: 'Public Sans', sans-serif; }
    .hero-banner { background: linear-gradient(135deg, #696cff 0%, #393bbf 100%); color: #fff; padding: 4rem 1rem; border-radius: 0 0 2rem 2rem; }
    .service-card { transition: all 0.25s ease-in-out; border: none; border-radius: 1rem; box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.15); }
    .service-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1.5rem rgba(105, 108, 255, 0.25); }
  </style>
</head>
<body>

  <!-- Navigation Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="bx bx-wrench me-2 text-primary"></i>Service Provider Portal</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item me-3"><a class="nav-link active" href="index.php">Home</a></li>
          <li class="nav-item me-3"><a class="nav-link" href="book-service.php">Book Service</a></li>
          <li class="nav-item me-3"><a class="nav-link" href="my-requests.php">Track Request</a></li>
          
          <?php if ($currentUser !== null): ?>
            <li class="nav-item me-2">
              <a href="personal-info.php" class="btn btn-sm btn-outline-light"><i class="bx bx-user me-1"></i><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></a>
            </li>
            <li class="nav-item">
              <a href="logout.php" class="btn btn-sm btn-danger"><i class="bx bx-log-out me-1"></i> Log Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item me-2">
              <a href="login.php" class="btn btn-primary btn-sm fw-bold"><i class="bx bx-log-in-circle me-1"></i> Sign In with OTP</a>
            </li>
          <?php endif; ?>
          <li class="nav-item ms-2">
            <a class="btn btn-outline-secondary btn-sm" href="../admin/index.php"><i class="bx bx-shield-quarter me-1"></i> Admin</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <div class="hero-banner text-center">
    <div class="container">
      <h1 class="display-5 fw-bold text-white mb-3">Professional CCTV, Computer & AMC Services</h1>
      <p class="lead mb-4">Fast installation, expert repairs, and Annual Maintenance Contracts at your doorstep.</p>
      <a href="book-service.php" class="btn btn-light btn-lg text-primary fw-bold px-4 py-2 shadow-sm"><i class="bx bx-calendar-plus me-1"></i> Book a Service Request</a>
    </div>
  </div>

  <!-- Main Container -->
  <div class="container my-5">
    <h3 class="fw-bold mb-4 text-center">Our Core Services</h3>
    
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="card h-100 p-4 service-card text-center">
          <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded-circle bg-label-primary fs-2"><i class="bx bx-camera-home"></i></span>
          </div>
          <h5 class="fw-bold">CCTV Camera Installation & Repair</h5>
          <p class="text-muted">HD IP Camera setups, DVR/NVR configuration, indoor/outdoor wiring, and quick fault repair.</p>
          <a href="book-service.php?category=cctv_camera" class="btn btn-outline-primary mt-auto">Book CCTV Service</a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 p-4 service-card text-center">
          <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded-circle bg-label-success fs-2"><i class="bx bx-laptop"></i></span>
          </div>
          <h5 class="fw-bold">Computer Hardware & Sales</h5>
          <p class="text-muted">Desktop & laptop repairs, RAM/SSD upgrades, hardware sales, and OS maintenance.</p>
          <a href="book-service.php?category=computer_hardware" class="btn btn-outline-success mt-auto">Book Computer Service</a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 p-4 service-card text-center">
          <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded-circle bg-label-warning fs-2"><i class="bx bx-award"></i></span>
          </div>
          <h5 class="fw-bold">Annual Maintenance Contract (AMC)</h5>
          <p class="text-muted">Comprehensive yearly maintenance for CCTV systems and office computer infrastructure.</p>
          <a href="book-service.php?category=amc_contract" class="btn btn-outline-warning mt-auto">Book AMC Contract</a>
        </div>
      </div>
    </div>

    <!-- Active Service Areas Banner -->
    <div class="card bg-white p-4 rounded-3 shadow-sm mb-5">
      <h5 class="fw-bold mb-3"><i class="bx bx-map-pin me-2 text-primary"></i> Authorized Service Locations</h5>
      <p class="text-muted">We provide service across registered pincodes:</p>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($serviceAreas as $sa): ?>
          <span class="badge bg-label-primary fs-7 p-2"><i class="bx bx-check me-1"></i><?= htmlspecialchars($sa->getPincode() . ' - ' . $sa->getAreaName(), ENT_QUOTES, 'UTF-8') ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-dark text-white text-center py-4 mt-5">
    <div class="container">
      <p class="mb-0">&copy; <?= date('Y') ?> Service Provider Portal. All Rights Reserved.</p>
    </div>
  </footer>

  <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.js"></script>
</body>
</html>
