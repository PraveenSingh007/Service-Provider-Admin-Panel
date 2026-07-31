<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Quotation;
use App\Admin\Model\QuotationItem;
use App\Admin\Model\QuotationVersion;
use mysqli;
use Throwable;

class QuotationRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetch all quotations ordered by ID ascending.
     *
     * @return Quotation[]
     */
    public function findAll(): array
    {
        $quotations = [];
        $sql = 'SELECT id, quotation_number, service_request_id, customer_name, customer_mobile, customer_email, service_name, current_version, total_amount, status, created_at, updated_at FROM quotations ORDER BY id ASC';
        $result = $this->connection->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $quotations[] = new Quotation(
                    (int) $row['id'],
                    (string) $row['quotation_number'],
                    (string) $row['service_request_id'],
                    (string) $row['customer_name'],
                    (string) $row['customer_mobile'],
                    $row['customer_email'] !== null ? (string) $row['customer_email'] : null,
                    (string) $row['service_name'],
                    (int) $row['current_version'],
                    (float) $row['total_amount'],
                    (string) $row['status'],
                    (string) $row['created_at'],
                    (string) $row['updated_at']
                );
            }
            $result->free();
        }

        return $quotations;
    }

    /**
     * Find quotation by primary key ID.
     */
    public function findById(int $id): ?Quotation
    {
        $stmt = $this->connection->prepare('SELECT id, quotation_number, service_request_id, customer_name, customer_mobile, customer_email, service_name, current_version, total_amount, status, created_at, updated_at FROM quotations WHERE id = ?');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $quotation = new Quotation(
                (int) $row['id'],
                (string) $row['quotation_number'],
                (string) $row['service_request_id'],
                (string) $row['customer_name'],
                (string) $row['customer_mobile'],
                $row['customer_email'] !== null ? (string) $row['customer_email'] : null,
                (string) $row['service_name'],
                (int) $row['current_version'],
                (float) $row['total_amount'],
                (string) $row['status'],
                (string) $row['created_at'],
                (string) $row['updated_at']
            );
            $stmt->close();
            return $quotation;
        }

        $stmt->close();
        return null;
    }

    /**
     * Fetch all version records for a specific quotation.
     *
     * @return QuotationVersion[]
     */
    public function findVersionsByQuotationId(int $quotationId): array
    {
        $versions = [];
        $stmt = $this->connection->prepare('SELECT id, quotation_id, version_number, subtotal, discount, tax, total_amount, revision_notes, created_by, created_at FROM quotation_versions WHERE quotation_id = ? ORDER BY version_number ASC');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $quotationId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $versionId = (int) $row['id'];
            $items = $this->findItemsByVersionId($versionId);

            $versions[] = new QuotationVersion(
                $versionId,
                (int) $row['quotation_id'],
                (int) $row['version_number'],
                (float) $row['subtotal'],
                (float) $row['discount'],
                (float) $row['tax'],
                (float) $row['total_amount'],
                $row['revision_notes'] !== null ? (string) $row['revision_notes'] : null,
                $row['created_by'] !== null ? (string) $row['created_by'] : null,
                (string) $row['created_at'],
                $items
            );
        }

        $stmt->close();
        return $versions;
    }

    /**
     * Fetch items for a specific quotation version.
     *
     * @return QuotationItem[]
     */
    public function findItemsByVersionId(int $versionId): array
    {
        $items = [];
        $stmt = $this->connection->prepare('SELECT id, version_id, item_description, quantity, unit_price, total_price FROM quotation_items WHERE version_id = ? ORDER BY id ASC');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $versionId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = new QuotationItem(
                (int) $row['id'],
                (int) $row['version_id'],
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
     * Create or update a quotation header record.
     */
    public function saveQuotation(Quotation $q): int
    {
        try {
            if ($q->getId() !== null && $q->getId() > 0) {
                $stmt = $this->connection->prepare('UPDATE quotations SET quotation_number = ?, service_request_id = ?, customer_name = ?, customer_mobile = ?, customer_email = ?, service_name = ?, current_version = ?, total_amount = ?, status = ? WHERE id = ?');
                $qNum = $q->getQuotationNumber();
                $sReq = $q->getServiceRequestId();
                $cName = $q->getCustomerName();
                $cMob = $q->getCustomerMobile();
                $cEmail = $q->getCustomerEmail();
                $sName = $q->getServiceName();
                $curVer = $q->getCurrentVersion();
                $tAmt = $q->getTotalAmount();
                $status = $q->getStatus();
                $id = $q->getId();

                $stmt->bind_param('ssssssidsi', $qNum, $sReq, $cName, $cMob, $cEmail, $sName, $curVer, $tAmt, $status, $id);
                $stmt->execute();
                $stmt->close();
                return $q->getId();
            } else {
                $stmt = $this->connection->prepare('INSERT INTO quotations (quotation_number, service_request_id, customer_name, customer_mobile, customer_email, service_name, current_version, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $qNum = $q->getQuotationNumber();
                $sReq = $q->getServiceRequestId();
                $cName = $q->getCustomerName();
                $cMob = $q->getCustomerMobile();
                $cEmail = $q->getCustomerEmail();
                $sName = $q->getServiceName();
                $curVer = $q->getCurrentVersion();
                $tAmt = $q->getTotalAmount();
                $status = $q->getStatus();

                $stmt->bind_param('ssssssids', $qNum, $sReq, $cName, $cMob, $cEmail, $sName, $curVer, $tAmt, $status);
                $stmt->execute();
                $insertId = (int) $this->connection->insert_id;
                $stmt->close();
                return $insertId;
            }
        } catch (Throwable $e) {
            error_log('QuotationRepository saveQuotation error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Save a new version and its line items.
     */
    public function saveVersion(QuotationVersion $version, array $itemsData): int
    {
        try {
            $stmt = $this->connection->prepare('INSERT INTO quotation_versions (quotation_id, version_number, subtotal, discount, tax, total_amount, revision_notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $qId = $version->getQuotationId();
            $verNum = $version->getVersionNumber();
            $sub = $version->getSubtotal();
            $disc = $version->getDiscount();
            $tax = $version->getTax();
            $tot = $version->getTotalAmount();
            $notes = $version->getRevisionNotes();
            $creator = $version->getCreatedBy();

            $stmt->bind_param('iiddddss', $qId, $verNum, $sub, $disc, $tax, $tot, $notes, $creator);
            $stmt->execute();
            $versionId = (int) $this->connection->insert_id;
            $stmt->close();

            if ($versionId > 0 && !empty($itemsData)) {
                $itemStmt = $this->connection->prepare('INSERT INTO quotation_items (version_id, item_description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                foreach ($itemsData as $item) {
                    $desc = (string) ($item['description'] ?? '');
                    $qty = (int) ($item['quantity'] ?? 1);
                    $uPrice = (float) ($item['unit_price'] ?? 0.0);
                    $tPrice = (float) ($item['total_price'] ?? ($qty * $uPrice));

                    if (!empty($desc)) {
                        $itemStmt->bind_param('isidd', $versionId, $desc, $qty, $uPrice, $tPrice);
                        $itemStmt->execute();
                    }
                }
                $itemStmt->close();
            }

            return $versionId;
        } catch (Throwable $e) {
            error_log('QuotationRepository saveVersion error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete quotation by primary key ID.
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM quotations WHERE id = ?');
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Throwable $e) {
            error_log('QuotationRepository delete error: ' . $e->getMessage());
            return false;
        }
    }
}
