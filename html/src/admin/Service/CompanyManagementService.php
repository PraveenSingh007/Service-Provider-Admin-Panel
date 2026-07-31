<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\Company;
use App\Admin\Repository\CompanyRepository;

/**
 * Company Management Service
 * Handles validation and business logic for Company Profile.
 */
class CompanyManagementService
{
    private CompanyRepository $repository;

    public function __construct(CompanyRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get Company Profile.
     */
    public function getCompanyProfile(): ?Company
    {
        return $this->repository->getCompany();
    }

    /**
     * Update Company Profile.
     *
     * @param array<string, mixed> $postData
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateCompanyProfile(array $postData): array
    {
        $companyName = trim((string) ($postData['company_name'] ?? ''));
        $registrationNo = !empty($postData['registration_no']) ? trim((string) $postData['registration_no']) : null;
        $gstNo = !empty($postData['gst_no']) ? trim((string) $postData['gst_no']) : null;
        $address = !empty($postData['address']) ? trim((string) $postData['address']) : null;
        $phone = !empty($postData['phone']) ? trim((string) $postData['phone']) : null;
        $fax = !empty($postData['fax']) ? trim((string) $postData['fax']) : null;
        $email = !empty($postData['email']) ? trim((string) $postData['email']) : null;

        $errors = [];
        if (empty($companyName)) {
            $errors[] = 'Company Name is required.';
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => $errors,
            ];
        }

        $saved = $this->repository->save($companyName, $registrationNo, $gstNo, $address, $phone, $fax, $email);

        if (!$saved) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to update company profile.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Company profile updated successfully.',
            'errors' => [],
        ];
    }
}
