<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/dbConnection.php';
require_once __DIR__ . '/Model/Customer.php';
require_once __DIR__ . '/Repository/CustomerRepository.php';
require_once __DIR__ . '/Service/CustomerAuthService.php';

use App\Admin\Database\DatabaseConnection;
use App\User\Repository\CustomerRepository;
use App\User\Service\CustomerAuthService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User must be logged in via OTP
if (empty($_SESSION['customer_user'])) {
    header('Location: login.php');
    exit;
}

$currentUser = (array) $_SESSION['customer_user'];
$userId = (int) ($currentUser['id'] ?? 0);
$email = (string) ($currentUser['email'] ?? '');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$userRepo = new CustomerRepository($dbConn);
$authService = new CustomerAuthService($userRepo);

$successMsg = null;
$errorMsg = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!empty($submittedToken) && hash_equals($csrfToken, $submittedToken)) {
        $res = $authService->updatePersonalInformation($userId, $_POST);
        if ($res['success']) {
            $_SESSION['profile_saved_success'] = 'Personal information saved successfully!';
            $redirectTarget = !empty($_SESSION['redirect_after_login']) ? (string) $_SESSION['redirect_after_login'] : 'book-service.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirectTarget);
            exit;
        } else {
            $errorMsg = implode(' ', $res['errors'] ?? [$res['message']]);
        }
    } else {
        $errorMsg = 'Invalid security token. Please refresh the page.';
    }
}

// Pre-fill existing user values if available
$userObj = $userRepo->findById($userId);
$userArray = $userObj ? $userObj->toArray() : $currentUser;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complete Personal Information - Customer Portal</title>

  <!-- Favicon / Browser Tab Logo Icon -->
  <link rel="icon" type="image/png" href="../../../assets/img/logo.png" />
  <link rel="shortcut icon" type="image/png" href="../../../assets/img/logo.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />

  <style>
    body { background-color: #f5f5f9; font-family: 'Public Sans', sans-serif; }
    .profile-card { max-width: 650px; margin: 3rem auto; border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1.5rem rgba(161, 172, 184, 0.2); }
  </style>
</head>
<body>

  <!-- Navigation Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="index.php">
        <img src="../../../assets/img/logo.png" alt="Tech-xpert" style="height: 38px; width: auto; object-fit: contain; border-radius: 6px; background: #fff; padding: 2px;" class="me-2" />
        Tech-xpert Portal
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item me-3"><span class="text-light small"><i class="bx bx-user me-1"></i><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span></li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bx bx-log-out me-1"></i> Log Out</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="card profile-card p-4">
      <div class="card-body">
        
        <div class="text-center mb-4">
          <div class="avatar avatar-xl mx-auto mb-2">
            <span class="avatar-initial rounded-circle bg-label-success fs-2"><i class="bx bx-user-pin"></i></span>
          </div>
          <h4 class="fw-bold mb-1">Personal Information</h4>
          <p class="text-muted small">Please complete your name, mobile number, and address details.</p>
        </div>

        <?php if ($errorMsg !== null): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-1"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="personal-info.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

          <!-- Account Email (Read-Only) -->
          <div class="mb-3">
            <label class="form-label">Email Address (Account ID)</label>
            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" readonly />
          </div>

          <!-- Name Section -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control" placeholder="e.g. Ramesh" value="<?= htmlspecialchars((string)($userArray['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control" placeholder="e.g. Patel" value="<?= htmlspecialchars((string)($userArray['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
            </div>
          </div>

          <!-- Mobile Number -->
          <div class="mb-3">
            <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
            <input type="text" name="mobile_no" class="form-control" placeholder="10-digit mobile number" value="<?= htmlspecialchars((string)($userArray['mobile_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
          </div>

          <!-- Street Address -->
          <div class="mb-4">
            <label class="form-label">Full Address <span class="text-danger">*</span></label>
            <textarea name="address" class="form-control" rows="3" placeholder="House/Shop No., Street name, Landmark, Area..." required><?= htmlspecialchars((string)($userArray['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
            <i class="bx bx-save me-1"></i> Save Personal Information
          </button>
        </form>

      </div>
    </div>
  </div>

  <script src="../../../assets/vendor/libs/jquery/jquery.min.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.min.js"></script>
</body>
</html>
