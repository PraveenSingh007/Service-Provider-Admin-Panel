<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/src/Model/Quotation.php';
require_once __DIR__ . '/src/Model/QuotationVersion.php';
require_once __DIR__ . '/src/Model/QuotationItem.php';
require_once __DIR__ . '/src/Repository/QuotationRepository.php';
require_once __DIR__ . '/src/Model/Company.php';
require_once __DIR__ . '/src/Repository/CompanyRepository.php';
require_once __DIR__ . '/src/Service/QuotationManagementService.php';

use App\Database\DatabaseConnection;
use App\Repository\CompanyRepository;
use App\Repository\QuotationRepository;
use App\Service\QuotationManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$quotationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$repository = new QuotationRepository($dbConn);
$service = new QuotationManagementService($repository);
$companyRepo = new CompanyRepository($dbConn);
$company = $companyRepo->getCompany();

$quotation = $service->getQuotationById($quotationId);
if ($quotation === null) {
    die('Quotation not found.');
}

$versions = $service->getQuotationVersions($quotationId);
$selectedVersionNum = isset($_GET['version']) ? (int) $_GET['version'] : $quotation->getCurrentVersion();

$activeVersion = null;
foreach ($versions as $v) {
    if ($v->getVersionNumber() === $selectedVersionNum) {
        $activeVersion = $v;
        break;
    }
}
if ($activeVersion === null && !empty($versions)) {
    $activeVersion = end($versions);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quotation History - <?= htmlspecialchars($quotation->getQuotationNumber(), ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <style>
      body {
        font-family: 'Public Sans', sans-serif;
        background-color: #f4f5fa;
        margin: 0;
        padding: 30px;
      }
      .quotation-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        padding: 35px;
        max-width: 900px;
        margin: 0 auto;
      }
      .version-tab-btn {
        border-radius: 20px;
        padding: 6px 18px;
        font-weight: 600;
      }
      @media print {
        body { background: #fff; padding: 0; }
        .no-print { display: none !important; }
        .quotation-card { box-shadow: none; border: none; padding: 0; }
      }
    </style>
  </head>
  <body>
    <div class="container no-print mb-4 d-flex justify-content-between align-items-center" style="max-width: 900px;">
      <a href="quotations.php" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Back to Quotations
      </a>
      <div class="d-flex gap-2">
        <a href="generate-invoice.php?quotation_id=<?= $quotation->getId() ?>" class="btn btn-success">
          <i class="icon-base bx bx-file me-1"></i> Generate Invoice
        </a>
        <a href="add-quotation.php?id=<?= $quotation->getId() ?>" class="btn btn-warning">
          <i class="icon-base bx bx-plus-circle me-1"></i> Add Revision (v<?= $quotation->getCurrentVersion() + 1 ?>)
        </a>
        <button onclick="window.print();" class="btn btn-primary">
          <i class="icon-base bx bx-printer me-1"></i> Print / PDF
        </button>
      </div>
    </div>

    <!-- Version Selection Toolbar -->
    <div class="container no-print mb-4" style="max-width: 900px;">
      <div class="card p-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-bold me-2"><i class="icon-base bx bx-history me-1"></i> Revision History:</span>
          <?php foreach ($versions as $ver): ?>
            <?php $isAct = ($activeVersion !== null && $ver->getVersionNumber() === $activeVersion->getVersionNumber()); ?>
            <a href="quotation-details.php?id=<?= $quotation->getId() ?>&version=<?= $ver->getVersionNumber() ?>"
               class="btn btn-sm <?= $isAct ? 'btn-primary' : 'btn-outline-secondary' ?> version-tab-btn">
              Version <?= $ver->getVersionNumber() ?>
              <?php if ($ver->getVersionNumber() === $quotation->getCurrentVersion()): ?>
                <span class="badge bg-white text-primary ms-1">Latest</span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Printable Official Quotation Document -->
    <div class="quotation-card">
      <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
          <h2 class="fw-bold text-primary mb-1">SNEAT SERVICES</h2>
          <p class="text-muted mb-0">Professional Service Provider Admin Panel</p>
        </div>
        <div class="text-end">
          <h3 class="fw-bold text-dark mb-1">QUOTATION</h3>
          <span class="badge bg-label-primary fs-6"><?= htmlspecialchars($quotation->getQuotationNumber(), ENT_QUOTES, 'UTF-8') ?></span>
          <div class="mt-1 text-muted">
            <strong>Version:</strong> <?= $activeVersion !== null ? $activeVersion->getVersionNumber() : 1 ?> of <?= $quotation->getCurrentVersion() ?>
          </div>
        </div>
      </div>

      <!-- Customer & Request Info -->
      <div class="row mb-4">
        <div class="col-6">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Service Request Info</h6>
          <div><strong>Request ID:</strong> <span class="badge bg-label-info"><?= htmlspecialchars($quotation->getServiceRequestId(), ENT_QUOTES, 'UTF-8') ?></span></div>
          <div><strong>Service Name:</strong> <?= htmlspecialchars($quotation->getServiceName(), ENT_QUOTES, 'UTF-8') ?></div>
          <div><strong>Date Created:</strong> <?= htmlspecialchars($activeVersion !== null ? (string)$activeVersion->getCreatedAt() : (string)$quotation->getCreatedAt(), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="col-6 text-end">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Customer Details</h6>
          <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($quotation->getCustomerName(), ENT_QUOTES, 'UTF-8') ?></div>
          <div>📱 <?= htmlspecialchars($quotation->getCustomerMobile(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php if (!empty($quotation->getCustomerEmail())): ?>
            <div>✉️ <?= htmlspecialchars($quotation->getCustomerEmail(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($activeVersion !== null && !empty($activeVersion->getRevisionNotes())): ?>
        <div class="alert alert-warning mb-4">
          <strong><i class="icon-base bx bx-info-circle me-1"></i> Revision Remarks for Version <?= $activeVersion->getVersionNumber() ?>:</strong>
          <?= htmlspecialchars($activeVersion->getRevisionNotes(), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <!-- Particulars Table -->
      <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 50%;">Item / Labor Particulars</th>
              <th style="width: 15%;" class="text-center">Qty</th>
              <th style="width: 17%;" class="text-end">Unit Price (₹)</th>
              <th style="width: 18%;" class="text-end">Total Amount (₹)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($activeVersion !== null && !empty($activeVersion->getItems())): ?>
              <?php foreach ($activeVersion->getItems() as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item->getItemDescription(), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-center"><?= $item->getQuantity() ?></td>
                  <td class="text-end">₹<?= number_format($item->getUnitPrice(), 2) ?></td>
                  <td class="text-end fw-semibold">₹<?= number_format($item->getTotalPrice(), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center text-muted">No items recorded for this version.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Financial Totals -->
      <div class="row justify-content-end">
        <div class="col-md-5">
          <table class="table table-borderless">
            <tr>
              <td>Subtotal:</td>
              <td class="text-end fw-semibold">₹<?= number_format($activeVersion !== null ? $activeVersion->getSubtotal() : 0, 2) ?></td>
            </tr>
            <?php if ($activeVersion !== null && $activeVersion->getDiscount() > 0): ?>
              <tr>
                <td>Discount:</td>
                <td class="text-end text-danger">- ₹<?= number_format($activeVersion->getDiscount(), 2) ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td>GST Tax (18%):</td>
              <td class="text-end fw-semibold">₹<?= number_format($activeVersion !== null ? $activeVersion->getTax() : 0, 2) ?></td>
            </tr>
            <tr class="border-top border-2">
              <td class="fs-5 fw-bold text-primary">Final Total:</td>
              <td class="text-end fs-5 fw-bold text-primary">₹<?= number_format($activeVersion !== null ? $activeVersion->getTotalAmount() : 0, 2) ?></td>
            </tr>
          </table>
        </div>
      </div>

      <div class="border-top pt-4 mt-4 text-muted" style="font-size: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>Prepared by: <strong><?= htmlspecialchars($activeVersion !== null && $activeVersion->getCreatedBy() !== null ? $activeVersion->getCreatedBy() : 'Admin', ENT_QUOTES, 'UTF-8') ?></strong></div>
          <div class="fw-semibold text-primary">Thank you for choosing <?= htmlspecialchars($company !== null ? $company->getCompanyName() : 'Sneat Services Pvt Ltd', ENT_QUOTES, 'UTF-8') ?>!</div>
        </div>
        <?php if ($company !== null): ?>
          <div class="text-center text-secondary border-top pt-2 mt-2">
            <?= htmlspecialchars($company->getCompanyName(), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($company->getRegistrationNo())): ?> | Reg: <?= htmlspecialchars($company->getRegistrationNo(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            <?php if (!empty($company->getGstNo())): ?> | GSTIN: <?= htmlspecialchars($company->getGstNo(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            <?php if (!empty($company->getPhone())): ?> | Ph: <?= htmlspecialchars($company->getPhone(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            <?php if (!empty($company->getEmail())): ?> | Email: <?= htmlspecialchars($company->getEmail(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </body>
</html>
