<?php

declare(strict_types=1);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/src/Model/Invoice.php';
require_once __DIR__ . '/src/Model/InvoiceItem.php';
require_once __DIR__ . '/src/Repository/InvoiceRepository.php';
require_once __DIR__ . '/src/Repository/QuotationRepository.php';
require_once __DIR__ . '/src/Service/InvoiceManagementService.php';

use App\Database\DatabaseConnection;
use App\Repository\InvoiceRepository;
use App\Repository\QuotationRepository;
use App\Service\InvoiceManagementService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$invoiceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dbConn = DatabaseConnection::createFromEnv()->getConnection();
$invRepo = new InvoiceRepository($dbConn);
$quoRepo = new QuotationRepository($dbConn);
$service = new InvoiceManagementService($invRepo, $quoRepo);

$invoice = $service->getInvoiceById($invoiceId);
if ($invoice === null) {
    die('Invoice not found.');
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tax Invoice - <?= htmlspecialchars($invoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></title>
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
      .invoice-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        padding: 40px;
        max-width: 900px;
        margin: 0 auto;
      }
      @media print {
        body { background: #fff; padding: 0; }
        .no-print { display: none !important; }
        .invoice-card { box-shadow: none; border: none; padding: 0; }
      }
    </style>
  </head>
  <body>
    <div class="container no-print mb-4 d-flex justify-content-between align-items-center" style="max-width: 900px;">
      <a href="invoices.php" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Back to Invoices
      </a>
      <div class="d-flex gap-2">
        <?php if ($invoice->getPaymentStatus() !== 'paid'): ?>
          <form method="POST" action="invoices.php" onsubmit="return confirm('Mark this invoice as PAID?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="action" value="mark_paid" />
            <input type="hidden" name="id" value="<?= (int)$invoice->getId() ?>" />
            <button type="submit" class="btn btn-success">
              <i class="icon-base bx bx-check-circle me-1"></i> Mark Paid
            </button>
          </form>
        <?php endif; ?>
        <button onclick="window.print();" class="btn btn-primary">
          <i class="icon-base bx bx-printer me-1"></i> Print / Download PDF
        </button>
      </div>
    </div>

    <!-- Printable Official Tax Invoice Document -->
    <div class="invoice-card">
      <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
          <h2 class="fw-bold text-success mb-1">SNEAT SERVICES</h2>
          <p class="text-muted mb-0">GSTIN: 22AAAAA0000A1Z5 | Official Service Provider</p>
          <small class="text-muted">Raipur, Chhattisgarh, India</small>
        </div>
        <div class="text-end">
          <h3 class="fw-bold text-dark mb-1">TAX INVOICE</h3>
          <span class="badge bg-label-success fs-6"><?= htmlspecialchars($invoice->getInvoiceNumber(), ENT_QUOTES, 'UTF-8') ?></span>
          <div class="mt-2">
            <?php if ($invoice->getPaymentStatus() === 'paid'): ?>
              <span class="badge bg-success fs-6">PAID</span>
            <?php else: ?>
              <span class="badge bg-warning fs-6">UNPAID</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Customer & Invoice Info -->
      <div class="row mb-4">
        <div class="col-6">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Billed To (Customer)</h6>
          <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($invoice->getCustomerName(), ENT_QUOTES, 'UTF-8') ?></div>
          <div>📱 <?= htmlspecialchars($invoice->getCustomerMobile(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php if (!empty($invoice->getCustomerEmail())): ?>
            <div>✉️ <?= htmlspecialchars($invoice->getCustomerEmail(), ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>
        <div class="col-6 text-end">
          <h6 class="fw-bold text-secondary text-uppercase mb-2">Invoice Details</h6>
          <div><strong>Request ID:</strong> <span class="badge bg-label-info"><?= htmlspecialchars($invoice->getServiceRequestId(), ENT_QUOTES, 'UTF-8') ?></span></div>
          <div><strong>Invoice Date:</strong> <?= htmlspecialchars($invoice->getInvoiceDate(), ENT_QUOTES, 'UTF-8') ?></div>
          <div><strong>Due Date:</strong> <?= htmlspecialchars($invoice->getDueDate(), ENT_QUOTES, 'UTF-8') ?></div>
          <div><strong>Payment Method:</strong> <?= htmlspecialchars($invoice->getPaymentMethod(), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>

      <!-- Particulars Table -->
      <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 50%;">Item / Service Description</th>
              <th style="width: 15%;" class="text-center">Qty</th>
              <th style="width: 17%;" class="text-end">Unit Price (₹)</th>
              <th style="width: 18%;" class="text-end">Total Amount (₹)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($invoice->getItems())): ?>
              <?php foreach ($invoice->getItems() as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item->getItemDescription(), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-center"><?= $item->getQuantity() ?></td>
                  <td class="text-end">₹<?= number_format($item->getUnitPrice(), 2) ?></td>
                  <td class="text-end fw-semibold">₹<?= number_format($item->getTotalPrice(), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center text-muted">No line items recorded.</td></tr>
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
              <td class="text-end fw-semibold">₹<?= number_format($invoice->getSubtotal(), 2) ?></td>
            </tr>
            <?php if ($invoice->getDiscount() > 0): ?>
              <tr>
                <td>Discount:</td>
                <td class="text-end text-danger">- ₹<?= number_format($invoice->getDiscount(), 2) ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td>GST Tax (18%):</td>
              <td class="text-end fw-semibold">₹<?= number_format($invoice->getTax(), 2) ?></td>
            </tr>
            <tr class="border-top border-2">
              <td class="fs-5 fw-bold text-success">Total Amount:</td>
              <td class="text-end fs-5 fw-bold text-success">₹<?= number_format($invoice->getTotalAmount(), 2) ?></td>
            </tr>
          </table>
        </div>
      </div>

      <?php if (!empty($invoice->getNotes())): ?>
        <div class="alert alert-light border mb-4">
          <strong>Terms & Notes:</strong> <?= htmlspecialchars($invoice->getNotes(), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <div class="border-top pt-4 mt-4 d-flex justify-content-between text-muted" style="font-size: 12px;">
        <div>Authorized Signatory: ___________________</div>
        <div>Thank you for your prompt payment!</div>
      </div>
    </div>
  </body>
</html>
