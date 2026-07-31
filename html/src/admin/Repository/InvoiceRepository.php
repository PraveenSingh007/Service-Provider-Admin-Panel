<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Invoice;
use App\Admin\Model\InvoiceItem;
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
     * Fetch all invoices ordered by ID ascending.
     *
     * @return Invoice[]
     */
    public function findAll(): array
    {
        $invoices = [];
        $sql = 'SELECT id, invoice_number, quotation_id, service_request_id, customer_name, customer_mobile, customer_email, service_name, subtotal, discount, tax, total_amount, payment_status, payment_method, invoice_date, due_date, notes, created_at, updated_at FROM invoices ORDER BY id ASC';
        $result = $this->connection->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $invId = (int) $row['id'];
                $items = $this->findItemsByInvoiceId($invId);

                $invoices[] = new Invoice(
                    $invId,
                    (string) $row['invoice_number'],
                    $row['quotation_id'] !== null ? (int) $row['quotation_id'] : null,
                    (string) $row['service_request_id'],
                    (string) $row['customer_name'],
                    (string) $row['customer_mobile'],
                    $row['customer_email'] !== null ? (string) $row['customer_email'] : null,
                    (string) $row['service_name'],
                    (float) $row['subtotal'],
                    (float) $row['discount'],
                    (float) $row['tax'],
                    (float) $row['total_amount'],
                    (string) $row['payment_status'],
                    (string) ($row['payment_method'] ?? 'Cash'),
                    (string) $row['invoice_date'],
                    (string) $row['due_date'],
                    $row['notes'] !== null ? (string) $row['notes'] : null,
                    (string) $row['created_at'],
                    (string) $row['updated_at'],
                    $items
                );
            }
            $result->free();
        }

        return $invoices;
    }

    /**
     * Find invoice by primary key ID.
     */
    public function findById(int $id): ?Invoice
    {
        $stmt = $this->connection->prepare('SELECT id, invoice_number, quotation_id, service_request_id, customer_name, customer_mobile, customer_email, service_name, subtotal, discount, tax, total_amount, payment_status, payment_method, invoice_date, due_date, notes, created_at, updated_at FROM invoices WHERE id = ?');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $invId = (int) $row['id'];
            $items = $this->findItemsByInvoiceId($invId);

            $invoice = new Invoice(
                $invId,
                (string) $row['invoice_number'],
                $row['quotation_id'] !== null ? (int) $row['quotation_id'] : null,
                (string) $row['service_request_id'],
                (string) $row['customer_name'],
                (string) $row['customer_mobile'],
                $row['customer_email'] !== null ? (string) $row['customer_email'] : null,
                (string) $row['service_name'],
                (float) $row['subtotal'],
                (float) $row['discount'],
                (float) $row['tax'],
                (float) $row['total_amount'],
                (string) $row['payment_status'],
                (string) ($row['payment_method'] ?? 'Cash'),
                (string) $row['invoice_date'],
                (string) $row['due_date'],
                $row['notes'] !== null ? (string) $row['notes'] : null,
                (string) $row['created_at'],
                (string) $row['updated_at'],
                $items
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
     * Fetch line items for an invoice ID.
     *
     * @return InvoiceItem[]
     */
    public function findItemsByInvoiceId(int $invoiceId): array
    {
        $items = [];
        $stmt = $this->connection->prepare('SELECT id, invoice_id, item_description, quantity, unit_price, total_price FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = new InvoiceItem(
                (int) $row['id'],
                (int) $row['invoice_id'],
                (string) $row['item_description'],
                (int) $row['quantity'],
                (float) $row['unit_price'],
                (float) $row['total_price']
            );
        }

        $stmt->close();
        return $items;
    }

    /**
     * Save an invoice and its line items.
     */
    public function saveInvoice(Invoice $inv, array $itemsData): int
    {
        try {
            if ($inv->getId() !== null && $inv->getId() > 0) {
                $stmt = $this->connection->prepare('UPDATE invoices SET invoice_number = ?, quotation_id = ?, service_request_id = ?, customer_name = ?, customer_mobile = ?, customer_email = ?, service_name = ?, subtotal = ?, discount = ?, tax = ?, total_amount = ?, payment_status = ?, payment_method = ?, invoice_date = ?, due_date = ?, notes = ? WHERE id = ?');
                $iNum = $inv->getInvoiceNumber();
                $qId = $inv->getQuotationId();
                $sReq = $inv->getServiceRequestId();
                $cName = $inv->getCustomerName();
                $cMob = $inv->getCustomerMobile();
                $cEmail = $inv->getCustomerEmail();
                $sName = $inv->getServiceName();
                $sub = $inv->getSubtotal();
                $disc = $inv->getDiscount();
                $tax = $inv->getTax();
                $tot = $inv->getTotalAmount();
                $pStat = $inv->getPaymentStatus();
                $pMeth = $inv->getPaymentMethod();
                $iDate = $inv->getInvoiceDate();
                $dDate = $inv->getDueDate();
                $notes = $inv->getNotes();
                $id = $inv->getId();

                $stmt->bind_param('sisssssddddsssssi', $iNum, $qId, $sReq, $cName, $cMob, $cEmail, $sName, $sub, $disc, $tax, $tot, $pStat, $pMeth, $iDate, $dDate, $notes, $id);
                $stmt->execute();
                $stmt->close();
                $invoiceId = $inv->getId();
            } else {
                $stmt = $this->connection->prepare('INSERT INTO invoices (invoice_number, quotation_id, service_request_id, customer_name, customer_mobile, customer_email, service_name, subtotal, discount, tax, total_amount, payment_status, payment_method, invoice_date, due_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $iNum = $inv->getInvoiceNumber();
                $qId = $inv->getQuotationId();
                $sReq = $inv->getServiceRequestId();
                $cName = $inv->getCustomerName();
                $cMob = $inv->getCustomerMobile();
                $cEmail = $inv->getCustomerEmail();
                $sName = $inv->getServiceName();
                $sub = $inv->getSubtotal();
                $disc = $inv->getDiscount();
                $tax = $inv->getTax();
                $tot = $inv->getTotalAmount();
                $pStat = $inv->getPaymentStatus();
                $pMeth = $inv->getPaymentMethod();
                $iDate = $inv->getInvoiceDate();
                $dDate = $inv->getDueDate();
                $notes = $inv->getNotes();

                $stmt->bind_param('sisssssddddsssss', $iNum, $qId, $sReq, $cName, $cMob, $cEmail, $sName, $sub, $disc, $tax, $tot, $pStat, $pMeth, $iDate, $dDate, $notes);
                $stmt->execute();
                $invoiceId = (int) $this->connection->insert_id;
                $stmt->close();
            }

            if ($invoiceId > 0 && !empty($itemsData)) {
                // Remove old items on update
                $delStmt = $this->connection->prepare('DELETE FROM invoice_items WHERE invoice_id = ?');
                $delStmt->bind_param('i', $invoiceId);
                $delStmt->execute();
                $delStmt->close();

                $itemStmt = $this->connection->prepare('INSERT INTO invoice_items (invoice_id, item_description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                foreach ($itemsData as $item) {
                    $desc = (string) ($item['description'] ?? '');
                    $qty = (int) ($item['quantity'] ?? 1);
                    $uPrice = (float) ($item['unit_price'] ?? 0.0);
                    $tPrice = (float) ($item['total_price'] ?? ($qty * $uPrice));

                    if (!empty($desc)) {
                        $itemStmt->bind_param('isidd', $invoiceId, $desc, $qty, $uPrice, $tPrice);
                        $itemStmt->execute();
                    }
                }
                $itemStmt->close();
            }

            return $invoiceId;
        } catch (Throwable $e) {
            error_log('InvoiceRepository saveInvoice error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(int $invoiceId, string $method = 'UPI'): bool
    {
        try {
            $stmt = $this->connection->prepare('UPDATE invoices SET payment_status = "paid", payment_method = ? WHERE id = ?');
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('si', $method, $invoiceId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('InvoiceRepository markPaid error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete invoice by primary key ID.
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
}
