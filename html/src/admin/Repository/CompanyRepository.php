<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Model\Company;
use mysqli;
use Throwable;

/**
 * Company Repository
 * Data access layer for company_profile table.
 */
class CompanyRepository
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get company profile details.
     */
    public function getCompany(): ?Company
    {
        try {
            $sql = 'SELECT id, company_name, registration_no, gst_no, address, phone, fax, email, created_at, updated_at FROM company_profile ORDER BY id ASC LIMIT 1';
            $stmt = $this->connection->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $company = new Company(
                        (int) $row['id'],
                        (string) $row['company_name'],
                        $row['registration_no'] !== null ? (string) $row['registration_no'] : null,
                        $row['gst_no'] !== null ? (string) $row['gst_no'] : null,
                        $row['address'] !== null ? (string) $row['address'] : null,
                        $row['phone'] !== null ? (string) $row['phone'] : null,
                        $row['fax'] !== null ? (string) $row['fax'] : null,
                        $row['email'] !== null ? (string) $row['email'] : null,
                        $row['created_at'] !== null ? (string) $row['created_at'] : null,
                        $row['updated_at'] !== null ? (string) $row['updated_at'] : null
                    );
                    $stmt->close();
                    return $company;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('CompanyRepository getCompany error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Save / Update company profile.
     */
    public function save(string $companyName, ?string $registrationNo, ?string $gstNo, ?string $address, ?string $phone, ?string $fax, ?string $email): bool
    {
        try {
            $existing = $this->getCompany();

            if ($existing !== null && $existing->getId() !== null) {
                $sql = 'UPDATE company_profile SET company_name = ?, registration_no = ?, gst_no = ?, address = ?, phone = ?, fax = ?, email = ? WHERE id = ?';
                $stmt = $this->connection->prepare($sql);

                if (!$stmt) {
                    return false;
                }

                $id = $existing->getId();
                $stmt->bind_param('sssssssi', $companyName, $registrationNo, $gstNo, $address, $phone, $fax, $email, $id);
                $success = $stmt->execute();
                $stmt->close();
                return $success;
            } else {
                $sql = 'INSERT INTO company_profile (id, company_name, registration_no, gst_no, address, phone, fax, email) VALUES (1, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $this->connection->prepare($sql);

                if (!$stmt) {
                    return false;
                }

                $stmt->bind_param('sssssss', $companyName, $registrationNo, $gstNo, $address, $phone, $fax, $email);
                $success = $stmt->execute();
                $stmt->close();
                return $success;
            }
        } catch (Throwable $e) {
            error_log('CompanyRepository save error: ' . $e->getMessage());
            return false;
        }
    }
}
