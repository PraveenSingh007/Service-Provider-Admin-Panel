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

// Redirect if already logged in
if (!empty($_SESSION['customer_user'])) {
    $cUser = (array) $_SESSION['customer_user'];
    if (empty($cUser['is_profile_completed'])) {
        header('Location: personal-info.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['csrf_token'];

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$repository = new CustomerRepository($dbConn);
$authService = new CustomerAuthService($repository);

$targetEmail = (string) ($_SESSION['pending_otp_email'] ?? '');
$step = ($targetEmail !== '') ? 'otp' : 'email'; // 'email' or 'otp'

$successMsg = null;
$errorMsg = null;
$demoOtp = null;

$loginNotice = null;
if (!empty($_SESSION['login_notice'])) {
    $loginNotice = (string) $_SESSION['login_notice'];
    unset($_SESSION['login_notice']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $errorMsg = 'Invalid security token. Please refresh the page.';
    } else {
        $action = (string) $_POST['action'];

        if ($action === 'send_otp') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $res = $authService->requestOtp($email);
            if ($res['success']) {
                $_SESSION['pending_otp_email'] = $email;
                $targetEmail = $email;
                $step = 'otp';
                $successMsg = $res['message'];
            } else {
                $errorMsg = implode(' ', $res['errors'] ?? [$res['message']]);
            }
        } elseif ($action === 'verify_otp') {
            $email = trim((string) ($_POST['email'] ?? $targetEmail));
            $otpCode = trim((string) ($_POST['otp_code'] ?? ''));
            
            $res = $authService->verifyOtpAndLogin($email, $otpCode);
            if ($res['success']) {
                unset($_SESSION['pending_otp_email']);
                if (empty($res['is_profile_completed'])) {
                    header('Location: personal-info.php');
                } else {
                    $redirectTarget = !empty($_SESSION['redirect_after_login']) ? (string) $_SESSION['redirect_after_login'] : 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirectTarget);
                }
                exit;
            } else {
                $step = 'otp';
                $errorMsg = implode(' ', $res['errors'] ?? [$res['message']]);
            }
        } elseif ($action === 'change_email') {
            unset($_SESSION['pending_otp_email']);
            $targetEmail = '';
            $step = 'email';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Sign In with Email OTP</title>

  <!-- Favicon / Browser Tab Logo Icon -->
  <link rel="icon" type="image/png" href="../../../assets/img/logo.png" />
  <link rel="shortcut icon" type="image/png" href="../../../assets/img/logo.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" />
  <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />

  <style>
    body { background-color: #f5f5f9; font-family: 'Public Sans', sans-serif; display: flex; align-items: center; min-height: 100vh; margin: 0; }
    .auth-card { max-width: 450px; width: 100%; border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1.5rem rgba(161, 172, 184, 0.2); }
    .otp-input { font-size: 1.5rem; letter-spacing: 0.5rem; text-align: center; font-weight: 700; }
  </style>
</head>
<body>

  <div class="container d-flex justify-content-center">
    <div class="card auth-card p-4 my-4">
      <div class="card-body">
        
        <div class="text-center mb-4">
          <div class="avatar avatar-xl mx-auto mb-2">
            <span class="avatar-initial rounded-circle bg-label-primary fs-2"><i class="bx bx-envelope"></i></span>
          </div>
          <h4 class="fw-bold mb-1">Sign In</h4>
          <!-- <p class="text-muted small">Enter your email address to receive OTP</p> -->
        </div>

        <?php if ($loginNotice !== null): ?>
          <div class="alert alert-warning alert-dismissible fade show small shadow-sm" role="alert">
            <i class="bx bx-lock-alt me-1 align-middle"></i> <strong>Sign-in Required:</strong> <?= htmlspecialchars($loginNotice, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($successMsg !== null): ?>
          <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="bx bx-check-circle me-1"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <?php if ($errorMsg !== null): ?>
          <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="bx bx-error-circle me-1"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>



        <?php if ($step === 'email'): ?>
          <!-- STEP 1: EMAIL ENTRY FORM -->
          <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="send_otp" />

            <div class="mb-4">
              <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control form-control-lg" placeholder="name@example.com" value="<?= htmlspecialchars($targetEmail, ENT_QUOTES, 'UTF-8') ?>" required autofocus />
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" id="sendOtpBtn">
              <span id="sendOtpText">Get OTP on Email</span>
              <span id="sendOtpSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
              <i class="bx bx-right-arrow-alt ms-1"></i>
            </button>
          </form>

        <?php else: ?>
          <!-- STEP 2: OTP VERIFICATION FORM -->
          <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="verify_otp" />
            <input type="hidden" name="email" value="<?= htmlspecialchars($targetEmail, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="mb-3">
              <label class="form-label fw-semibold">Enter 6-Digit OTP</label>
              <p class="text-muted small mb-2">Sent to <strong><?= htmlspecialchars($targetEmail, ENT_QUOTES, 'UTF-8') ?></strong></p>
              <input type="text" name="otp_code" class="form-control form-control-lg otp-input" maxlength="6" placeholder="123456" required autofocus autocomplete="off" />
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm mb-3">
              <i class="bx bx-check-circle me-1"></i> Verify OTP & Login
            </button>
          </form>

          <div class="text-center mt-2 pt-2 border-top">
            <form method="POST" action="login.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
              <input type="hidden" name="action" value="change_email" />
              <button type="submit" class="btn btn-link btn-sm text-muted p-0"><i class="bx bx-left-arrow-alt me-1"></i> Change Email</button>
            </form>
          </div>

        <?php endif; ?>

        <div class="text-center mt-4">
          <a href="index.php" class="text-muted small"><i class="bx bx-arrow-back me-1"></i> Back to Home</a>
        </div>

      </div>
    </div>
  </div>

  <script src="../../../assets/vendor/libs/jquery/jquery.min.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var sendOtpBtn = document.getElementById('sendOtpBtn');
      var sendOtpText = document.getElementById('sendOtpText');
      var sendOtpSpinner = document.getElementById('sendOtpSpinner');

      if (sendOtpBtn) {
        var sendOtpForm = sendOtpBtn.closest('form');
        if (sendOtpForm) {
          sendOtpForm.addEventListener('submit', function (event) {
            if (!sendOtpForm.checkValidity()) {
              return;
            }
            sendOtpBtn.disabled = true;
            sendOtpText.textContent = 'Sending...';
            sendOtpSpinner.classList.remove('d-none');
          });
        }
      }
    });
  </script>
</body>
</html>
