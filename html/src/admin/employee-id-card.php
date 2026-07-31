<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\EmployeeRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$empId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$repository = new EmployeeRepository($dbConn);
$employee = $repository->findById($empId);

if ($employee === null) {
    die('Employee not found.');
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ID Card - <?= htmlspecialchars($employee->getEmpName(), ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.css" />
    <style>
      body {
        font-family: 'Public Sans', sans-serif;
        background-color: #f4f5fa;
        margin: 0;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .no-print-toolbar {
        margin-bottom: 25px;
      }
      .btn-print {
        background-color: #696cff;
        color: #ffffff;
        border: none;
        padding: 10px 24px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(105, 108, 255, 0.4);
        transition: all 0.2s ease-in-out;
      }
      .btn-print:hover {
        background-color: #5f61e6;
        transform: translateY(-2px);
      }

      /* ID Card Styles */
      .id-card-wrapper {
        width: 340px;
        height: 520px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        position: relative;
        border: 1px solid #e2e8f0;
      }
      .id-card-header {
        background: linear-gradient(135deg, #696cff 0%, #393bbf 100%);
        color: #ffffff;
        padding: 24px 20px 45px 20px;
        text-align: center;
        position: relative;
      }
      .id-card-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: 1px;
      }
      .id-card-header p {
        margin: 4px 0 0 0;
        font-size: 11px;
        opacity: 0.85;
        text-transform: uppercase;
        letter-spacing: 1.5px;
      }
      .id-card-photo-container {
        position: relative;
        margin-top: -40px;
        text-align: center;
      }
      .id-card-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        object-fit: cover;
        background-color: #e2e8f0;
      }
      .id-card-body {
        padding: 15px 24px 20px 24px;
        text-align: center;
      }
      .emp-name {
        font-size: 18px;
        font-weight: 700;
        color: #2b2c40;
        margin: 5px 0 2px 0;
      }
      .emp-role {
        font-size: 12px;
        font-weight: 600;
        color: #696cff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
      }
      .emp-code-badge {
        display: inline-block;
        background-color: #e7e7ff;
        color: #696cff;
        padding: 4px 14px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 15px;
      }
      .info-grid {
        text-align: left;
        font-size: 12px;
        color: #566a7f;
        border-top: 1px dashed #e2e8f0;
        padding-top: 12px;
      }
      .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
      }
      .info-label {
        font-weight: 600;
        color: #8592a3;
      }
      .info-val {
        font-weight: 500;
        color: #32475c;
        text-align: right;
      }
      .id-card-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #f8f9fa;
        padding: 10px 20px;
        border-top: 1px solid #eef2f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .barcode-placeholder {
        font-family: monospace;
        font-size: 14px;
        font-weight: bold;
        letter-spacing: 3px;
        color: #32475c;
      }

      @media print {
        body {
          background-color: #ffffff;
          padding: 0;
        }
        .no-print-toolbar {
          display: none;
        }
        .id-card-wrapper {
          box-shadow: none;
          border: 1px solid #ccc;
        }
      }
    </style>
  </head>
  <body>
    <div class="no-print-toolbar">
      <button onclick="window.print();" class="btn-print">
        🖨️ Print Employee ID Card
      </button>
    </div>

    <div class="id-card-wrapper">
      <div class="id-card-header">
        <h2>SNEAT SERVICES</h2>
        <p>Official Identity Card</p>
      </div>

      <div class="id-card-photo-container">
        <?php if (!empty($employee->getEmpPhoto())): ?>
          <img src="<?= htmlspecialchars($employee->getEmpPhoto(), ENT_QUOTES, 'UTF-8') ?>" alt="Photo" class="id-card-photo" />
        <?php else: ?>
          <img src="../../../assets/img/avatars/1.png" alt="Photo" class="id-card-photo" />
        <?php endif; ?>
      </div>

      <div class="id-card-body">
        <div class="emp-name"><?= htmlspecialchars($employee->getEmpName(), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="emp-role"><?= htmlspecialchars($employee->getEmpRole(), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="emp-code-badge"><?= htmlspecialchars($employee->getEmpCode(), ENT_QUOTES, 'UTF-8') ?></div>

        <div class="info-grid">
          <div class="info-row">
            <span class="info-label">Mobile:</span>
            <span class="info-val"><?= htmlspecialchars($employee->getEmpMobile(), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <?php if (!empty($employee->getEmpAadhar())): ?>
            <div class="info-row">
              <span class="info-label">Aadhaar No:</span>
              <span class="info-val"><?= htmlspecialchars((string)$employee->getEmpAadhar(), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($employee->getEmpPan())): ?>
            <div class="info-row">
              <span class="info-label">PAN No:</span>
              <span class="info-val"><?= htmlspecialchars((string)$employee->getEmpPan(), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          <?php endif; ?>
          <div class="info-row">
            <span class="info-label">Joining:</span>
            <span class="info-val"><?= htmlspecialchars($employee->getJoiningDate(), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </div>
      </div>

      <div class="id-card-footer">
        <div class="barcode-placeholder">||||| ||| |||||||</div>
        <small style="font-size: 10px; color: #8592a3;">Authorized Signature</small>
      </div>
    </div>
  </body>
</html>
