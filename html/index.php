<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/src/Model/User.php';
require_once __DIR__ . '/src/Repository/UserRepository.php';
require_once __DIR__ . '/src/Service/AuthService.php';
require_once __DIR__ . '/src/Controller/AuthController.php';

use App\Controller\AuthController;
use App\Database\DatabaseConnection;
use App\Repository\UserRepository;
use App\Service\AuthService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in user to dashboard
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = (string) $_SESSION['csrf_token'];
$authError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dbConn = null;
        try {
            $dbConn = DatabaseConnection::createFromEnv()->getConnection();
        } catch (\Throwable $e) {
            // DB connection optional; fallback handles demo credentials
        }

        $userRepo = new UserRepository($dbConn);
        $authService = new AuthService($userRepo);
        $controller = new AuthController($authService);

        $result = $controller->login($_POST, $csrfToken);

        $isXmlHttpRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isXmlHttpRequest) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($result['status']);
            echo json_encode($result['response']);
            exit;
        }

        if ($result['response']['success']) {
            header('Location: ' . ($result['response']['redirect'] ?? 'dashboard.php'));
            exit;
        }

        $errors = (array) ($result['response']['errors'] ?? []);
        $authError = count($errors) > 0 ? implode(', ', $errors) : (string) $result['response']['message'];
    } catch (\Throwable $e) {
        $authError = 'An unexpected error occurred during login. Please try again.';
    }
}
?>
<!doctype html>

<html
  lang="en"
  class="layout-wide customizer-hide"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Login - Sneat Admin Dashboard</title>

    <meta name="description" content="Sneat Admin Panel Login Page" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>

  <body>
    <!-- Content -->
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <div class="card px-sm-6 px-0">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center">
                <a href="index.php" class="app-brand-link gap-2">
                  <span class="app-brand-text demo text-heading fw-bold">Sneat</span>
                </a>
              </div>
              <!-- /Logo -->

              <h4 class="mb-1">Admin Panel</h4>
              <p class="mb-6">Please sign-in to your account.</p>

              <form id="formAuthentication" class="mb-6" action="index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="mb-6">
                  <label for="email" class="form-label">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="admin@example.com"
                    required
                    autofocus />
                </div>
                <div class="mb-6 form-password-toggle">
                  <label class="form-label" for="password">Password</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      required
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                  </div>
                </div>
                <div class="mb-6">
                  <button class="btn btn-primary d-grid w-100" type="submit" id="btnLogin">Login</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / Content -->

    <!-- Error Snackbar Container -->
    <div id="snackbar-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
      <div id="errorSnackbar" class="toast align-items-center text-white bg-danger border-0 <?= !empty($authError) ? 'show' : '' ?>" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center">
            <span class="me-2 fs-5">⚠️</span>
            <span id="snackbar-message"><?= htmlspecialchars($authError ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close" onclick="hideSnackbar()"></button>
        </div>
      </div>
    </div>

    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>

    <!-- Login & Snackbar Script -->
    <script>
      function hideSnackbar() {
        const snackbarEl = document.getElementById('errorSnackbar');
        if (snackbarEl) {
          snackbarEl.classList.remove('show');
        }
      }

      function showSnackbar(msg) {
        const snackbarEl = document.getElementById('errorSnackbar');
        const messageEl = document.getElementById('snackbar-message');
        if (snackbarEl && messageEl) {
          messageEl.textContent = msg;
          snackbarEl.classList.add('show');
          setTimeout(hideSnackbar, 5000);
        }
      }

      document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formAuthentication');
        const snackbarEl = document.getElementById('errorSnackbar');

        if (snackbarEl && snackbarEl.classList.contains('show')) {
          setTimeout(hideSnackbar, 5000);
        }

        if (form) {
          form.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            if (btn) { btn.disabled = true; }

            const formData = new FormData(form);

            fetch('index.php', {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: formData
            })
            .then(function (res) {
              return res.json();
            })
            .then(function (data) {
              if (btn) { btn.disabled = false; }
              if (data.success) {
                window.location.href = data.redirect || 'dashboard.php';
              } else {
                const errorMsg = (data.errors && data.errors.length)
                  ? data.errors.join(', ')
                  : (data.message || 'Login failed. Please try again.');
                showSnackbar(errorMsg);
              }
            })
            .catch(function () {
              if (btn) { btn.disabled = false; }
              showSnackbar('An unexpected connection error occurred.');
            });
          });
        }
      });
    </script>
  </body>
</html>
