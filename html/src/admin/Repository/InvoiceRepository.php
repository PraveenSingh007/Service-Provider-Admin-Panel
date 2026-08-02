<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Invoice;
use App\Admin\Model\InvoiceItem;
use App\Admin\Model\InvoiceVersion;
use mysqli;
use Throwable;

class InvoiceRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetch all invoices with their versions (ordered by ID ascending).
     *
     * @return Invoice[]
     */
    public function findAll(): array
    {
        $invoices = [];
        $sql = 'SELECT id, invoice_number, quotation_id, service_request_id, customer_name, customer_mobile, customer_email, service_name, current_version, status, created_at, updated_at FROM invoices ORDER BY id ASC';
        $result = $this->connection->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $invId = (int) $row['id'];
                $versions = $this->findVersionsByInvoiceId($invId);

                $invoices[] = new Invoice(
                    $invId,
                    (string) $row['invoice_number'],
                    $row['quotation_id'] !== null ? (int) $row['quotation_id'] : null,
                    (string) $row['service_request_id'],
                    (string) $row['customer_name'],
                    (string) $row['customer_mobile'],
                    $row['customer_email'] !== null ? (string) $row['customer_email'] : null,
                    (string) $row['service_name'],
                    (int) $row['current_version'],
                    (string) $row['status'],
                    (string) $row['created_at'],
                    (string) $row['updated_at'],
                    $versions
                );
            }
            $result->free();
        }

        return $invoices;
    }

    /**
     * Find invoice by primary key ID (with all versions loaded).
     */
    public function findById(int $id): ?Invoice
    {
        $stmt = $this->connection->prepare('SELECT id, invoice_number, quotation_id, service_request_id, customer_name, customer_mobile, customer_email, service_name, current_version, status, created_at, updated_at FROM invoices WHERE id = ?');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $invId = (int) $row['id'];
            $versions = $this->findVersionsByInvoiceId($invId);

            $invoice = new Invoice(
                $invId,
                (string) $row['invoice_number'],
                $row['quotation_id'] !== null ? (int) $row['quotation_id'] : null,
                (string) $row['service_request_id'],
                (string) $row['customer_name'],
                (string) $row['customer_mobile'],
                $row['customer_email'] !== null ? (string) $row['customer_email'] : null,
                (string) $row['service_name'],
                (int) $row['current_version'],
                (string) $row['status'],
                (string) $row['created_at'],
                (string) $row['updated_at'],
                $versions
            );
            $stmt->close();
            return $invoice;
        }

        $stmt->close();
        return null;
    }

    /**
     * Find existing invoice linked to a quotation ID.
     */
    public function findByQuotationId(int $quotationId): ?Invoice
    {
        $stmt = $this->connection->prepare('SELECT id FROM invoices WHERE quotation_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $quotationId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $invId = (int) $row['id'];
            $stmt->close();
            return $this->findById($invId);
        }

        $stmt->close();
        return null;
    }

    /**
     * Find existing invoice linked to a service_request_id.
     */
    public function findByServiceRequestId(string $serviceRequestId): ?Invoice
    {
        $stmt = $this->connection->prepare('SELECT id FROM invoices WHERE service_request_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $serviceRequestId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $invId = (int) $row['id'];
            $stmt->close();
            return $this->findById($invId);
        }

        $stmt->close();
        return null;
    }

    /**
     * Fetch all version records for a specific invoice.
     *
     * @return InvoiceVersion[]
     */
    public function findVersionsByInvoiceId(int $invoiceId): array
    {
        $versions = [];
        $stmt = $this->connection->prepare('SELECT id, invoice_id, version_number, quotation_version, subtotal, discount, tax, total_amount, payment_status, payment_method, invoice_date, due_date, revision_notes, created_by, created_at, updated_at FROM invoice_versions WHERE invoice_id = ? ORDER BY version_number ASC');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $versionId = (int) $row['id'];
            $items = $this->findItemsByVersionId($versionId);

            $versions[] = new InvoiceVersion(
                $versionId,
                (int) $row['invoice_id'],
                (int) $row['version_number'],
                (float) $row['subtotal'],
                (float) $row['discount'],
                (float) $row['tax'],
                (float) $row['total_amount'],
                (string) $row['payment_status'],
                (string) ($row['payment_method'] ?? 'Cash'),
                (string) $row['invoice_date'],
                (string) $row['due_date'],
                $row['revision_notes'] !== null ? (string) $row['revision_notes'] : null,
                $row['created_by'] !== null ? (string) $row['created_by'] : null,
                (string) $row['created_at'],
                $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
                $items,
                $row['quotation_version'] !== null ? (int) $row['quotation_version'] : null
            );
        }

        $stmt->close();
        return $versions;
    }

    /**
     * Fetch items for a specific invoice version.
     *
     * @return InvoiceItem[]
     */
    public function findItemsByVersionId(int $versionId): array
    {
        $items = [];
        $stmt = $this->connection->prepare('SELECT id, version_id, item_description, quantity, unit_price, discount_percent, gst_percent, total_price FROM invoice_items WHERE version_id = ? ORDER BY id ASC');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $versionId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = new InvoiceItem(
                (int) $row['id'],
                (int) $row['version_id'],
                (string) $row['item_description'],
                (int) $row['quantity'],
                (float) $row['unit_price'],
                (float) $row['total_price'],
                (float) ($row['discount_percent'] ?? 0.0),
                (float) ($row['gst_percent'] ?? 0.0)
            );
        }

        $stmt->close();
        return $items;
    }

    /**
     * Create or update an invoice header record.
     */
    public function saveInvoiceHeader(Invoice $inv): int
    {
        try {
            if ($inv->getId() !== null && $inv->getId() > 0) {
                $stmt = $this->connection->prepare('UPDATE invoices SET invoice_number = ?, quotation_id = ?, service_request_id = ?, customer_name = ?, customer_mobile = ?, customer_email = ?, service_name = ?, current_version = ?, status = ? WHERE id = ?');
                $iNum = $inv->getInvoiceNumber();
                $qId = $inv->getQuotationId();
                $sReq = $inv->getServiceRequestId();
                $cName = $inv->getCustomerName();
                $cMob = $inv->getCustomerMobile();
                $cEmail = $inv->getCustomerEmail();
                $sName = $inv->getServiceName();
                $curVer = $inv->getCurrentVersion();
                $status = $inv->getStatus();
                $id = $inv->getId();

                $stmt->bind_param('sisssssisi', $iNum, $qId, $sReq, $cName, $cMob, $cEmail, $sName, $curVer, $status, $id);
                $stmt->execute();
                $stmt->close();
                return $inv->getId();
            } else {
                $stmt = $this->connection->prepare('INSERT INTO invoices (invoice_number, quotation_id, service_request_id, customer_name, customer_mobile, customer_email, service_name, current_version, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $iNum = $inv->getInvoiceNumber();
                $qId = $inv->getQuotationId();
                $sReq = $inv->getServiceRequestId();
                $cName = $inv->getCustomerName();
                $cMob = $inv->getCustomerMobile();
                $cEmail = $inv->getCustomerEmail();
                $sName = $inv->getServiceName();
                $curVer = $inv->getCurrentVersion();
                $status = $inv->getStatus();

                $stmt->bind_param('sisssssis', $iNum, $qId, $sReq, $cName, $cMob, $cEmail, $sName, $curVer, $status);
                $stmt->execute();
                $insertId = (int) $this->connection->insert_id;
                $stmt->close();
                return $insertId;
            }
        } catch (Throwable $e) {
            error_log('InvoiceRepository saveInvoiceHeader error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Save a new invoice version and its line items.
     */
    public function saveVersion(InvoiceVersion $version, array $itemsData): int
    {
        try {
            $stmt = $this->connection->prepare('INSERT INTO invoice_versions (invoice_id, version_number, quotation_version, subtotal, discount, tax, total_amount, payment_status, payment_method, invoice_date, due_date, revision_notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $invId = $version->getInvoiceId();
            $verNum = $version->getVersionNumber();
            $qVer = $version->getQuotationVersion();
            $sub = $version->getSubtotal();
            $disc = $version->getDiscount();
            $tax = $version->getTax();
            $tot = $version->getTotalAmount();
            $pStat = $version->getPaymentStatus();
            $pMeth = $version->getPaymentMethod();
            $iDate = $version->getInvoiceDate();
            $dDate = $version->getDueDate();
            $notes = $version->getRevisionNotes();
            $creator = $version->getCreatedBy();

            $stmt->bind_param('iiiddddssssss', $invId, $verNum, $qVer, $sub, $disc, $tax, $tot, $pStat, $pMeth, $iDate, $dDate, $notes, $creator);
            $stmt->execute();
            $versionId = (int) $this->connection->insert_id;
            $stmt->close();

            if ($versionId > 0 && !empty($itemsData)) {
                $itemStmt = $this->connection->prepare('INSERT INTO invoice_items (version_id, item_description, quantity, unit_price, discount_percent, gst_percent, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)');
                foreach ($itemsData as $item) {
                    $desc = (string) ($item['description'] ?? '');
                    $qty = (int) ($item['quantity'] ?? 1);
                    $uPrice = (float) ($item['unit_price'] ?? 0.0);
                    $discPct = (float) ($item['discount_percent'] ?? 0.0);
                    $gstPct = (float) ($item['gst_percent'] ?? 0.0);

                    $baseTotal = $qty * $uPrice;
                    $discAmt = $baseTotal * ($discPct / 100);
                    $taxable = $baseTotal - $discAmt;
                    $gstAmt = $taxable * ($gstPct / 100);
                    $calculatedTotal = round($taxable + $gstAmt, 2);

                    $tPrice = isset($item['total_price']) ? (float) $item['total_price'] : $calculatedTotal;

                    if (!empty($desc)) {
                        $itemStmt->bind_param('isidddd', $versionId, $desc, $qty, $uPrice, $discPct, $gstPct, $tPrice);
                        $itemStmt->execute();
                    }
                }
                $itemStmt->close();
            }

            return $versionId;
        } catch (Throwable $e) {
            error_log('InvoiceRepository saveVersion error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing invoice version's payment status and method.
     */
    public function updateVersionPayment(int $versionId, string $paymentStatus, string $paymentMethod): bool
    {
        try {
            $stmt = $this->connection->prepare('UPDATE invoice_versions SET payment_status = ?, payment_method = ? WHERE id = ?');
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('ssi', $paymentStatus, $paymentMethod, $versionId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('InvoiceRepository updateVersionPayment error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a specific invoice version as paid.
     */
    public function markVersionPaid(int $versionId, string $method = 'UPI'): bool
    {
        return $this->updateVersionPayment($versionId, 'paid', $method);
    }

    /**
     * Mark all versions of an invoice as paid.
     */
    public function markAllVersionsPaid(int $invoiceId, string $method = 'UPI'): bool
    {
        try {
            $stmt = $this->connection->prepare('UPDATE invoice_versions SET payment_status = "paid", payment_method = ? WHERE invoice_id = ?');
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('si', $method, $invoiceId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('InvoiceRepository markAllVersionsPaid error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a specific version of an invoice.
     */
    public function deleteVersion(int $invoiceId, int $versionNumber): bool
    {
        try {
            // Find the version record
            $verStmt = $this->connection->prepare('SELECT id FROM invoice_versions WHERE invoice_id = ? AND version_number = ?');
            if (!$verStmt) {
                return false;
            }
            $verStmt->bind_param('ii', $invoiceId, $versionNumber);
            $verStmt->execute();
            $verRes = $verStmt->get_result();
            $versionId = 0;
            if ($row = $verRes->fetch_assoc()) {
                $versionId = (int) $row['id'];
            }
            $verStmt->close();

            if ($versionId <= 0) {
                return false;
            }

            // Delete items first (cascade should handle it, but be explicit)
            $delItemsStmt = $this->connection->prepare('DELETE FROM invoice_items WHERE version_id = ?');
            if ($delItemsStmt) {
                $delItemsStmt->bind_param('i', $versionId);
                $delItemsStmt->execute();
                $delItemsStmt->close();
            }

            // Delete the version
            $delVerStmt = $this->connection->prepare('DELETE FROM invoice_versions WHERE id = ?');
            if ($delVerStmt) {
                $delVerStmt->bind_param('i', $versionId);
                $delVerStmt->execute();
                $delVerStmt->close();
            }

            // Update invoice header
            $remaining = $this->findVersionsByInvoiceId($invoiceId);
            if (empty($remaining)) {
                // If no versions left, delete the invoice header too
                $this->delete($invoiceId);
            } else {
                $latestVer = end($remaining);
                $upStmt = $this->connection->prepare('UPDATE invoices SET current_version = ? WHERE id = ?');
                if ($upStmt) {
                    $cVer = $latestVer->getVersionNumber();
                    $upStmt->bind_param('ii', $cVer, $invoiceId);
                    $upStmt->execute();
                    $upStmt->close();
                }
            }

            return true;
        } catch (Throwable $e) {
            error_log('InvoiceRepository deleteVersion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete invoice by primary key ID (cascading deletes versions and items).
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM invoices WHERE id = ?');
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('InvoiceRepository delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate the next sequential invoice number.
     */
    public function findNextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";
        $stmt = $this->connection->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1");
        $pattern = $prefix . '%';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();

        $nextNum = 1;
        if ($row = $result->fetch_assoc()) {
            $lastNum = (int) str_replace($prefix, '', (string)$row['invoice_number']);
            $nextNum = $lastNum + 1;
        }
        $stmt->close();

        return $prefix . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
    }
}
