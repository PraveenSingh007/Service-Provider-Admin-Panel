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

// Hero Videos Playlist (Shuffled on every page refresh for dynamic variation)
$heroVideoFiles = [
    '../../../assets/video/hero-bg-1.mp4',
    '../../../assets/video/hero-bg-2.mp4',
    '../../../assets/video/hero-bg-3.mp4',
    '../../../assets/video/hero-bg-4.mp4',
    '../../../assets/video/hero-bg-5.mp4',
    '../../../assets/video/hero-bg-6.mp4',
];
shuffle($heroVideoFiles);

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
                // Ensure callback_requests table exists automatically if not yet created
                @$dbConn->query("CREATE TABLE IF NOT EXISTS callback_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    callback_no VARCHAR(50) NOT NULL UNIQUE,
                    customer_name VARCHAR(150) NOT NULL,
                    mobile_no VARCHAR(20) NOT NULL,
                    service_category VARCHAR(100) DEFAULT 'other',
                    preferred_time_slot VARCHAR(50) DEFAULT 'anytime',
                    note TEXT NULL,
                    status ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $sql = "INSERT INTO callback_requests 
                    (callback_no, customer_name, mobile_no, service_category, preferred_time_slot, note, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $dbConn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssssss', $reqNo, $cbName, $cbMobile, $cbService, $cbTime, $cbNote);
                    if ($stmt->execute()) {
                        $callbackSuccess = true;
                        $callbackReqNo = $reqNo;
                    } else {
                        $callbackError = 'Failed to record callback request. Please try again.';
                    }
                    $stmt->close();
                }
            } catch (\Throwable $ex) {
                $callbackError = 'Database error: ' . $ex->getMessage();
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
  <!-- Primary Meta Tags -->
  <title>CCTV & IT Support | Top-Rated AMC Services | tech-xpert</title>
  <meta name="description" content="Professional CCTV setup, IT hardware repair, AMC services, & AC maintenance. Fast 2-hour doorstep service in India. Book a technician today.">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="https://tech-xpert.in/">
  
  <!-- LocalBusiness Schema Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "tech-xpert Portal",
    "url": "https://tech-xpert.in/",
    "description": "Professional CCTV, IT Hardware & AMC Services.",
    "address": {"@type": "Raipur", "addressCountry": "IN"},
    "telephone": "+91-8085041130, +91-8602234489" 
  }
  </script>
  
  <!-- Favicon / Browser Tab Logo Icon -->
  <link rel="icon" type="image/png" href="../../../assets/img/logo.png" />
  <link rel="shortcut icon" type="image/png" href="../../../assets/img/logo.png" />
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  
  <style>
    :root {
      --primary-color: #61BEF1;
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

    /* Modern Header */
    .navbar-custom {
      background: #131722 !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      position: relative;
      z-index: 1040;
      margin: 0 !important;
    }

    .brand-icon-box {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #61BEF1 0%, #393bbf 100%);
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

    /* Top Hero Video Banner (Exact 350px Height, 100% Full-Width Ambient View) */
    .hero-carousel-container {
      position: relative;
      width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      border-radius: 0 !important;
      overflow: hidden;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18);
      background-color: #0b0f19;
      clear: both;
    }

    .hero-video-banner {
      width: 100%;
      height: 550px;
      position: relative;
      background-color: #0b0f19;
      overflow: hidden;
    }

    .hero-video-element {
      width: 100%;
      height: 550px;
      object-fit: cover;
      object-position: center;
      display: block;
    }

    @media (max-width: 767.98px) {
      .hero-video-banner {
        height: 220px;
      }
      .hero-video-element {
        height: 220px;
      }
    }

    /* Hero Sound Control Toggle Button */
    .hero-sound-btn {
      position: absolute;
      top: 16px;
      right: 20px;
      bottom: auto;
      z-index: 20;
      background: rgba(19, 23, 34, 0.8) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: #ffffff !important;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 0.45rem 1rem;
      border-radius: 50px;
      transition: all 0.25s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
    }

    .hero-sound-btn:hover {
      background: #61BEF1 !important;
      border-color: #61BEF1 !important;
      color: #ffffff !important;
      transform: translateY(-2px) scale(1.03);
    }

    .hero-overlay {
      position: relative;
      width: 100%;
      z-index: 2;
      background: linear-gradient(135deg, rgba(11, 15, 25, 0.94) 0%, rgba(19, 23, 34, 0.82) 50%, rgba(11, 15, 25, 0.92) 100%);
      padding: 3rem 0;
    }

    .slide-badge {
      background: rgba(105, 108, 255, 0.2);
      border: 1px solid rgba(105, 108, 255, 0.45);
      color: #9da0ff;
      backdrop-filter: blur(8px);
      padding: 0.35rem 0.9rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-uppercase: uppercase;
      display: inline-block;
    }

    .hero-carousel-title {
      font-size: 2.25rem;
      font-weight: 800;
      line-height: 1.25;
      color: #ffffff;
      letter-spacing: -0.5px;
    }

    .hero-carousel-desc {
      font-size: 1.05rem;
      line-height: 1.6;
      color: rgba(255, 255, 255, 0.88);
      max-width: 620px;
    }

    .hero-btn-main {
      padding: 0.65rem 1.4rem;
      font-size: 0.95rem;
      font-weight: 700;
      border-radius: 50px;
      transition: all 0.25s ease;
    }

    .hero-btn-sub {
      padding: 0.65rem 1.4rem;
      font-size: 0.95rem;
      font-weight: 600;
      border-radius: 50px;
      transition: all 0.25s ease;
    }

    /* Showcase Image Box Desktop & Tablet */
    .hero-image-card {
      width: 100%;
      max-width: 380px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(16px);
      border-radius: 1.25rem;
      padding: 10px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.1);
      transition: transform 0.3s ease;
    }

    .hero-image-card:hover {
      transform: translateY(-4px);
    }

    .hero-card-img {
      width: 100%;
      height: 240px;
      object-fit: cover;
      object-position: center;
      border-radius: 0.9rem;
      display: block;
    }

    /* Controls & Indicators */
    .carousel-indicators {
      bottom: 12px;
      margin-bottom: 0;
      z-index: 5;
    }

    .carousel-indicators [data-bs-target] {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin: 0 5px;
      background-color: #fff;
      opacity: 0.4;
      transition: all 0.3s ease;
    }

    .carousel-indicators .active {
      width: 28px;
      border-radius: 6px;
      opacity: 1;
      background-color: #61BEF1;
    }

    .carousel-control-prev, .carousel-control-next {
      width: 60px;
      opacity: 0.85;
      z-index: 5;
    }

    .carousel-control-btn {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      transition: all 0.25s ease;
    }

    .carousel-control-btn:hover {
      background: #61BEF1;
      border-color: #61BEF1;
      transform: scale(1.08);
      color: #fff;
    }

    /* Responsive Mobile Adjustments */
    @media (max-width: 991.98px) {
      .hero-carousel-title {
        font-size: 1.75rem;
      }
      .hero-card-img {
        height: 200px;
      }
    }

    @media (max-width: 767.98px) {
      .hero-slide-item {
        min-height: auto;
      }
      .hero-overlay {
        padding: 1.75rem 0 2.5rem 0;
      }
      .hero-carousel-title {
        font-size: 1.3rem;
        line-height: 1.3;
        margin-bottom: 0.5rem !important;
      }
      .hero-carousel-desc {
        font-size: 0.85rem;
        line-height: 1.45;
        margin-bottom: 0.85rem !important;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .slide-badge {
        font-size: 0.72rem;
        padding: 0.25rem 0.7rem;
        margin-bottom: 0.5rem !important;
      }
      .hero-mobile-img-box {
        width: 100%;
        height: 150px;
        border-radius: 0.85rem;
        overflow: hidden;
        margin-bottom: 0.85rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
      }
      .hero-mobile-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
      }
      .hero-btn-main, .hero-btn-sub {
        font-size: 0.82rem;
        padding: 0.45rem 0.85rem;
        flex: 1 1 auto;
        text-align: center;
        white-space: nowrap;
      }
      .carousel-control-prev, .carousel-control-next {
        width: 38px;
      }
      .carousel-control-btn {
        width: 34px;
        height: 34px;
        font-size: 1.1rem;
      }
    }

    /* Site Footer Full Width 100% Like Header */
    .site-footer {
      width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      border-radius: 0 !important;
      background-color: #131722 !important;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      position: relative;
      left: 0;
      right: 0;
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
      border-color: #61BEF1;
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

        .brand-logo-card {
      background: #ffffff;
      border: 1px solid rgba(161, 172, 184, 0.25);
      border-radius: 1.1rem;
      padding: 0.75rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      min-width: 180px;
      height: 90px;
      flex-shrink: 0;
    }

    .brand-logo-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 28px rgba(105, 108, 255, 0.25);
      border-color: #61BEF1;
    }

    .brand-logo-img {
      max-height: 65px;
      max-width: 150px;
      width: auto;
      height: auto;
      object-fit: contain;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.06));
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

    /* Custom Call Back Banner Full Width Like Header */
    .callback-cta-banner {
      width: 100% !important;
      margin: 3rem 0 0 0 !important;
      border-radius: 0 !important;
      background: linear-gradient(135deg, #131722 0%, #1a1f2e 100%) !important;
      color: #ffffff;
      box-shadow: none;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
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

    /* Modal Tweaks & High-Visibility Close Buttons */
    .modal-content-custom {
      border-radius: 1.25rem;
      border: none;
      box-shadow: 0 1.5rem 3.5rem rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }

    .modal-header-custom {
      background: linear-gradient(135deg, #61BEF1 0%, #393bbf 100%);
      color: #fff;
      padding: 1.5rem 2rem;
      border: none;
    }

    /* Clean High-Visibility Modal Close Button */
    .modal-close-btn {
      width: 32px !important;
      height: 32px !important;
      border-radius: 50% !important;
      background: rgba(255, 255, 255, 0.2) !important;
      border: 1px solid rgba(255, 255, 255, 0.4) !important;
      color: #ffffff !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 0 !important;
      margin: 0 !important;
      font-size: 1.35rem !important;
      line-height: 1 !important;
      cursor: pointer !important;
      transition: all 0.2s ease-in-out !important;
      outline: none !important;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
    }
    .modal-close-btn:hover {
      background: #ff3e1d !important;
      border-color: #ff3e1d !important;
      color: #ffffff !important;
      transform: scale(1.08);
      box-shadow: 0 4px 10px rgba(255, 62, 29, 0.4) !important;
    }
  </style>
</head>
<body>

  <!-- Sticky Modern Navigation Header -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4 d-flex align-items-center me-4" href="index.php">
        <img src="../../../assets/img/logo.png" alt="tech-xpert" style="height: 42px; width: auto; object-fit: contain; border-radius: 8px; background: #ffffff; padding: 2px;" class="me-2 shadow-sm" />
        <span class="text-white">tech-</span><span style="color: #61BEF1;">xpert</span>
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
            <i class="bx bx-phone-call me-1 bx-tada"></i>
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

  <!-- Top Hero Multi-Video Carousel (Randomized Order on Page Refresh) -->
  <div class="hero-carousel-container">
    <div id="heroVideoCarousel" class="carousel slide carousel-fade" data-bs-ride="false">
      
      <!-- Slide Indicators -->
      <div class="carousel-indicators">
        <?php foreach ($heroVideoFiles as $idx => $vPath): ?>
          <button type="button" data-bs-target="#heroVideoCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" <?= $idx === 0 ? 'aria-current="true"' : '' ?> aria-label="Video <?= $idx + 1 ?>"></button>
        <?php endforeach; ?>
      </div>

      <div class="carousel-inner">
        <?php foreach ($heroVideoFiles as $idx => $vPath): ?>
          <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
            <div class="hero-video-banner">
              <video id="heroVideo<?= $idx + 1 ?>" class="hero-video-element" <?= $idx === 0 ? 'autoplay' : '' ?> playsinline preload="auto">
                <source src="<?= $vPath ?>" type="video/mp4">
              </video>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Controls -->
      <button class="carousel-control-prev" type="button" data-bs-target="#heroVideoCarousel" data-bs-slide="prev">
        <span class="carousel-control-btn"><i class="bx bx-chevron-left"></i></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroVideoCarousel" data-bs-slide="next">
        <span class="carousel-control-btn"><i class="bx bx-chevron-right"></i></span>
      </button>

      <!-- Interactive Sound Toggle Button -->
      <button id="videoSoundToggleBtn" class="btn hero-sound-btn" type="button" title="Toggle Sound" style="background: #61BEF1;">
        <i class="bx bx-volume-full me-1 text-warning"></i> On
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
            $srvId = (int)$srv->getId();
            $srvName = $srv->getServiceName();
            $srvImg = $srv->getServiceImage();
            
            $dbDesc = $srv->getServiceDescription();
            $defaultDesc = 'Comprehensive technician visit, inspection, diagnostics, and doorstep repairs for ' . $srvName . '.';

            $meta = $serviceMetadata[$srvId] ?? [
                'badge' => 'Professional Sales & Service',
                'desc' => $defaultDesc,
                'category' => 'other'
            ];
            
            // Load service_description directly from database table `services`
            if (!empty($dbDesc)) {
                $meta['desc'] = $dbDesc;
            }

            $cleanPath = ltrim((string)$srvImg, '/');
            if (strpos($cleanPath, 'html/') === 0) {
                $cleanPath = substr($cleanPath, 5);
            }
            $imgSrc = '../../' . ($cleanPath ?: 'uploads/services/cctv_service.png');
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="card service-card">
                <div class="service-img-container">
                  <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" class="service-card-img" alt="<?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?>" />
                  <span class="category-pill"><i class="bx bx-check-circle me-1 text-primary"></i> <?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card-body service-card-body">
                  <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?></h5>
                  <p class="text-muted fs-7 mb-4 flex-grow-1"><?= htmlspecialchars($meta['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                  
                  <div class="mt-auto d-flex gap-2 pt-2">
                    <a href="book-service.php?category=<?= urlencode($meta['category']) ?>&service_id=<?= $srvId ?>" class="btn btn-primary fw-bold flex-grow-1 py-2 shadow-sm">
                      <i class="bx bx-calendar-plus me-1"></i> Book Now
                    </a>
                    <button type="button" class="btn btn-outline-primary fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="<?= htmlspecialchars($meta['category'], ENT_QUOTES, 'UTF-8') ?>" title="Quick Call Back">
                      <i class="bx bx-phone-call"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    <!-- Brands Logo Automatic Scroll Section (Replaces Authorized Service Locations) -->
    <div class="brands-section-wrapper mb-5">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary px-3 py-2 rounded-pill fs-7 fw-bold text-uppercase mb-2">Industry Partners & Brands</span>
        <h3 class="fw-extrabold text-dark mb-1">Top Brands We Install & Support</h3>
        <p class="text-muted">We work directly with leading security, IT hardware, HVAC, and commercial equipment manufacturers.</p>
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

  </div> <!-- End Main Content Container -->

  <!-- Call Back CTA Banner (Full Width 100% Like Header) -->
  <section class="callback-cta-banner text-center text-lg-start">
    <div class="container py-5">
      <div class="row align-items-center">
        <div class="col-lg-8 mb-3 mb-lg-0">
          <h3 class="fw-bold text-white mb-2"><i class="bx bx-headphone text-warning me-2"></i> Need Instant Assistance or Custom Advice?</h3>
          <p class="mb-0 text-light opacity-90">Speak directly with our technical support team. Request a call back and we'll contact you within 15 minutes.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <button type="button" class="btn btn-warning btn-lg fw-bold rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#requestCallbackModal">
            <i class="bx bx-phone-call me-2"></i> 
          </button>
        </div>
      </div>
    </div>
  </section>

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
          <button type="button" class="modal-close-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
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
                <option value="">-- Select Service Interested In --</option>
                <?php foreach ($services as $srv): ?>
                  <option value="<?= htmlspecialchars($srv->getServiceName(), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($srv->getServiceName(), ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
                <option value="Other / General Inquiry">Other / General Inquiry</option>
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

  <!-- Footer (Full Width 100% Like Header) -->
  <footer class="site-footer text-white py-5">
    <div class="container">
      <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
          <a class="navbar-brand fw-bold fs-4 d-flex align-items-center mb-3" href="index.php">
            <img src="../../../assets/img/logo.png" alt="tech-xpert" style="height: 44px; width: auto; object-fit: contain; border-radius: 8px; background: #ffffff; padding: 2px;" class="me-2 shadow-sm" />
            <span class="text-white">tech-</span><span style="color: #61BEF1;">xpert</span>
          </a>
          <p class="text-muted fs-7 mb-0">Your trusted partner for professional CCTV installation, computer hardware maintenance, network setup, and Annual Maintenance Contracts (AMC).</p>
        </div>
        <div class="col-lg-4 col-md-6">
          <h6 class="fw-bold text-white mb-3 text-uppercase fs-7 tracking-wider">Quick Navigation</h6>
          <ul class="list-unstyled text-muted fs-7 mb-0">
            <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1 text-primary"></i> Home Portal</a></li>
            <li class="mb-2"><a href="book-service.php" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1 text-primary"></i> Book a Service Request</a></li>
            <li class="mb-2"><a href="my-requests.php" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1 text-primary"></i> Track Existing Request</a></li>
            <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" class="text-muted text-decoration-none"><i class="bx bx-chevron-right me-1 text-primary"></i> Request Immediate Call Back</a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-12">
          <h6 class="fw-bold text-white mb-3 text-uppercase fs-7 tracking-wider">Customer Support</h6>
          <p class="text-muted fs-7 mb-2"><i class="bx bx-phone me-2 text-primary"></i> Mobile: +91-8085041130, +91-8602234489</p>
          <p class="text-muted fs-7 mb-2"><i class="bx bx-envelope me-2 text-primary"></i> Support Email: support.techxpert@gmail.com</p>
          <p class="text-muted fs-7 mb-0"><i class="bx bx-time me-2 text-primary"></i> Hours: Monday - Saturday (9:00 AM - 8:00 PM)</p>
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

      // Multi-Video Carousel Playlist Controller with Auto-Mute After 1 Complete Loop
      const videoCarouselEl = document.getElementById('heroVideoCarousel');
      const soundBtn = document.getElementById('videoSoundToggleBtn');

      if (videoCarouselEl) {
        const videos = Array.from(videoCarouselEl.querySelectorAll('.hero-video-element'));
        let isMuted = false;           // Default sound enabled
        let userOverrodeSound = false;  // Tracks if user manually toggled sound
        let playedIndicesInFirstLoop = new Set(); // Tracks unique slides completed in 1st loop

        if (videos.length > 0) {
          const videoCarousel = new bootstrap.Carousel(videoCarouselEl, {
            interval: false,
            wrap: true
          });

          videos.forEach(v => { v.muted = false; });

          function playVideo(v) {
            if (v) {
              v.currentTime = 0;
              v.muted = isMuted;
              var playPromise = v.play();
              if (playPromise !== undefined) {
                playPromise.catch(function(e) {
                  if (e.name === 'NotAllowedError') {
                    v.muted = true;
                    v.play();
                  }
                });
              }
            }
          }

          function updateSoundBtnUI() {
            if (!soundBtn) return;
            if (isMuted) {
              soundBtn.innerHTML = '<i class="bx bx-volume-mute me-1"></i> Off';
              soundBtn.style.background = 'rgba(19, 23, 34, 0.8)';
            } else {
              soundBtn.innerHTML = '<i class="bx bx-volume-full me-1 text-warning"></i> On';
              soundBtn.style.background = '#61BEF1';
            }
          }

          // Unmute upon first user interaction on page if browser blocked sound autoplay initially
          const enableAudioOnUserGesture = function() {
            if (!isMuted && !userOverrodeSound) {
              videos.forEach(v => { v.muted = false; });
              const activeItem = videoCarouselEl.querySelector('.carousel-item.active');
              if (activeItem) {
                const activeVid = activeItem.querySelector('.hero-video-element');
                if (activeVid) {
                  activeVid.muted = false;
                  activeVid.play();
                }
              }
            }
            window.removeEventListener('click', enableAudioOnUserGesture);
            window.removeEventListener('touchstart', enableAudioOnUserGesture);
          };
          window.addEventListener('click', enableAudioOnUserGesture, { once: true });
          window.addEventListener('touchstart', enableAudioOnUserGesture, { once: true });

          // Toggle Sound Button handler
          if (soundBtn) {
            soundBtn.addEventListener('click', function(e) {
              e.stopPropagation();
              isMuted = !isMuted;
              userOverrodeSound = true;
              
              videos.forEach(v => {
                v.muted = isMuted;
              });

              updateSoundBtnUI();

              if (!isMuted) {
                const activeItem = videoCarouselEl.querySelector('.carousel-item.active');
                if (activeItem) {
                  const activeVid = activeItem.querySelector('.hero-video-element');
                  if (activeVid) {
                    activeVid.muted = false;
                    activeVid.play();
                  }
                }
              }
            });
          }

          // Advance to next video when current video ends
          videos.forEach((v, idx) => {
            v.addEventListener('ended', function() {
              playedIndicesInFirstLoop.add(idx);

              // Auto-mute audio after 1 complete loop of all slides (unless user overrode sound)
              if (playedIndicesInFirstLoop.size >= videos.length && !userOverrodeSound) {
                isMuted = true;
                videos.forEach(vid => { vid.muted = true; });
                updateSoundBtnUI();
              }

              videoCarousel.next();
            });
          });

          // Play active slide video when slide changes
          videoCarouselEl.addEventListener('slid.bs.carousel', function(e) {
            videos.forEach(v => v.pause());
            if (videos[e.to]) {
              playVideo(videos[e.to]);
            }
          });
        }
      }
    });
  </script>
</body>
</html>
