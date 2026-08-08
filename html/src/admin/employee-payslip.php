<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/Model/Employee.php';
require_once __DIR__ . '/Model/Salary.php';
require_once __DIR__ . '/Model/Company.php';
require_once __DIR__ . '/Repository/EmployeeRepository.php';
require_once __DIR__ . '/Repository/AttendanceRepository.php';
require_once __DIR__ . '/Repository/SalaryRepository.php';
require_once __DIR__ . '/Repository/CompanyRepository.php';
require_once __DIR__ . '/Service/SalaryManagementService.php';

use App\Admin\Database\DatabaseConnection;
use App\Admin\Repository\AttendanceRepository;
use App\Admin\Repository\CompanyRepository;
use App\Admin\Repository\EmployeeRepository;
use App\Admin\Repository\SalaryRepository;
use App\Admin\Service\SalaryManagementService;

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

$salaryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($salaryId <= 0) {
    die('Invalid Salary ID.');
}

$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$empRepo = new EmployeeRepository($dbConn);
$attnRepo = new AttendanceRepository($dbConn);
$salaryRepo = new SalaryRepository($dbConn);
$salaryService = new SalaryManagementService($salaryRepo, $empRepo, $attnRepo);
$companyRepo = new CompanyRepository($dbConn);
$company = $companyRepo->getCompany();

$salary = $salaryRepo->findById($salaryId);
if ($salary === null) {
    die('Salary record not found.');
}

$employee = $empRepo->findById($salary->getEmployeeId());
if ($employee === null) {
    die('Employee not found.');
}

$formattedMonth = date('F Y', strtotime($salary->getSalaryMonth() . '-01'));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payslip - <?= htmlspecialchars($employee->getEmpName(), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($salary->getSalaryMonth(), ENT_QUOTES, 'UTF-8') ?>)</title>
    
    <link rel="icon" type="image/x-icon" href="../../../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../../assets/vendor/fonts/iconify-icons.min.css" />
    <link rel="stylesheet" href="../../../assets/vendor/css/core.min.css" />
    <link rel="stylesheet" href="../../../assets/css/demo.min.css" />
    
    <style>
      @media print {
        body { background: #fff !important; }
        .no-print { display: none !important; }
        .payslip-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
      }
      .payslip-card {
        max-width: 850px;
        margin: 0 auto;
        border-radius: 0.5rem;
      }
      .company-logo {
        max-height: 55px;
      }
      .table-payslip th {
        background-color: #f8f9fa !important;
        font-weight: 600;
      }
    </style>
  </head>
  <body class="bg-light">
    <div class="container py-4">
      <!-- Actions Bar -->
      <div class="d-flex justify-content-between align-items-center mb-4 no-print max-width-850 mx-auto" style="max-width: 850px;">
        <a href="employee-salaries.php?month=<?= htmlspecialchars($salary->getSalaryMonth(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
          <i class="icon-base bx bx-arrow-back me-1"></i> Back to Salaries
        </a>
        <button onclick="window.print();" class="btn btn-primary">
          <i class="icon-base bx bx-printer me-1"></i> Print Payslip / Download PDF
        </button>
      </div>

      <!-- Payslip Box -->
      <div class="card p-5 payslip-card shadow-sm bg-white">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-4 mb-4">
          <div class="col-md-7">
            <?php if ($company && !empty($company->getLogo())): ?>
              <img src="../../uploads/<?= htmlspecialchars($company->getLogo(), ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" class="company-logo mb-2" />
            <?php endif; ?>
            <h3 class="fw-bold text-primary mb-1"><?= htmlspecialchars($company ? $company->getName() : 'Tech-xpert', ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="text-muted small mb-0"><?= htmlspecialchars($company ? $company->getAddress() : 'Services & Solutions', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-muted small mb-0">Phone: <?= htmlspecialchars($company ? $company->getMobile() : '', ENT_QUOTES, 'UTF-8') ?> | Email: <?= htmlspecialchars($company ? $company->getEmail() : '', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <h4 class="fw-bold text-uppercase tracking-wide text-dark mb-1">Salary Payslip</h4>
            <div class="badge bg-label-primary fs-6 px-3 py-2">Pay Period: <?= htmlspecialchars($formattedMonth, ENT_QUOTES, 'UTF-8') ?></div>
            <p class="text-muted small mt-2 mb-0">Issued Date: <?= htmlspecialchars($salary->getPaymentDate() ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>

        <!-- Employee Info Grid -->
        <div class="row g-3 mb-4 bg-light p-3 rounded">
          <div class="col-md-6">
            <div class="d-flex mb-2">
              <span class="text-muted w-40 me-2">Employee Name:</span>
              <strong class="text-dark"><?= htmlspecialchars($employee->getEmpName(), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="d-flex mb-2">
              <span class="text-muted w-40 me-2">Employee Code:</span>
              <span class="badge bg-label-info"><?= htmlspecialchars($employee->getEmpCode(), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="d-flex">
              <span class="text-muted w-40 me-2">Designation / Role:</span>
              <span><?= htmlspecialchars($employee->getEmpRole(), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex mb-2">
              <span class="text-muted w-40 me-2">Mobile Number:</span>
              <span><?= htmlspecialchars($employee->getEmpMobile(), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="d-flex mb-2">
              <span class="text-muted w-40 me-2">PAN / Aadhaar:</span>
              <span><?= htmlspecialchars($employee->getEmpPan() ?? '—', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($employee->getEmpAadhar() ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="d-flex">
              <span class="text-muted w-40 me-2">Payment Status:</span>
              <?php if ($salary->getPaymentStatus() === 'paid'): ?>
                <span class="badge bg-success">PAID (<?= htmlspecialchars($salary->getPaymentDate() ?? '', ENT_QUOTES, 'UTF-8') ?>)</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">PENDING</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Attendance Summary -->
        <h6 class="fw-bold text-primary mb-3"><i class="icon-base bx bx-calendar-check me-1"></i> Attendance Breakdown</h6>
        <div class="row g-2 text-center mb-4">
          <div class="col">
            <div class="border rounded p-2 bg-white">
              <small class="text-muted d-block">Working Days</small>
              <strong class="fs-5 text-dark"><?= $salary->getTotalDays() ?></strong>
            </div>
          </div>
          <div class="col">
            <div class="border rounded p-2 bg-white">
              <small class="text-muted d-block">Present</small>
              <strong class="fs-5 text-success"><?= $salary->getPresentDays() ?></strong>
            </div>
          </div>
          <div class="col">
            <div class="border rounded p-2 bg-white">
              <small class="text-muted d-block">Absent</small>
              <strong class="fs-5 text-danger"><?= $salary->getAbsentDays() ?></strong>
            </div>
          </div>
          <div class="col">
            <div class="border rounded p-2 bg-white">
              <small class="text-muted d-block">Half Days</small>
              <strong class="fs-5 text-warning"><?= $salary->getHalfDays() ?></strong>
            </div>
          </div>
          <div class="col">
            <div class="border rounded p-2 bg-white">
              <small class="text-muted d-block">Leaves</small>
              <strong class="fs-5 text-info"><?= $salary->getLeaveDays() ?></strong>
            </div>
          </div>
        </div>

        <!-- Earnings & Deductions Breakdown -->
        <h6 class="fw-bold text-primary mb-3"><i class="icon-base bx bx-calculator me-1"></i> Salary Breakdown</h6>
        <div class="table-responsive mb-4">
          <table class="table table-bordered align-middle table-payslip">
            <thead>
              <tr>
                <th>Earnings Particulars</th>
                <th class="text-end">Amount (₹)</th>
                <th>Deductions / Adjustments</th>
                <th class="text-end">Amount (₹)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Monthly Base Salary</td>
                <td class="text-end">₹<?= number_format($salary->getBaseSalary(), 2) ?></td>
                <td>Absence / Unpaid Deductions</td>
                <td class="text-end text-danger">₹<?= number_format(max(0, $salary->getBaseSalary() - $salary->getCalculatedSalary()), 2) ?></td>
              </tr>
              <tr>
                <td>Earned Salary (Attendance Basis)</td>
                <td class="text-end fw-semibold">₹<?= number_format($salary->getCalculatedSalary(), 2) ?></td>
                <td>Other Deductions</td>
                <td class="text-end text-danger">₹<?= number_format($salary->getDeductions(), 2) ?></td>
              </tr>
              <tr>
                <td>Performance Bonus / Allowance</td>
                <td class="text-end text-success">₹<?= number_format($salary->getBonus(), 2) ?></td>
                <td>—</td>
                <td class="text-end">—</td>
              </tr>
              <tr class="table-light fw-bold fs-6">
                <td class="text-dark">Gross Earnings</td>
                <td class="text-end text-dark">₹<?= number_format($salary->getCalculatedSalary() + $salary->getBonus(), 2) ?></td>
                <td class="text-dark">Total Deductions</td>
                <td class="text-end text-danger">₹<?= number_format(max(0, $salary->getBaseSalary() - $salary->getCalculatedSalary()) + $salary->getDeductions(), 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Net Salary Callout -->
        <div class="row align-items-center bg-primary text-white p-3 rounded mb-4 me-0 ms-0">
          <div class="col-8">
            <h5 class="mb-0 text-white fw-bold">Net Salary Payable</h5>
            <small class="text-white-50"><?= htmlspecialchars($salary->getNotes() ?? 'Calculated automatically on attendance records.', ENT_QUOTES, 'UTF-8') ?></small>
          </div>
          <div class="col-4 text-end">
            <h3 class="mb-0 text-white fw-bold">₹<?= number_format($salary->getNetSalary(), 2) ?></h3>
          </div>
        </div>

        <!-- Signatures -->
        <div class="row mt-5 pt-3 text-center">
          <div class="col-6">
            <div class="border-top pt-2 w-75 mx-auto">
              <small class="text-muted fw-bold">Employee Signature</small>
            </div>
          </div>
          <div class="col-6">
            <div class="border-top pt-2 w-75 mx-auto">
              <small class="text-muted fw-bold">Authorized Signatory</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
