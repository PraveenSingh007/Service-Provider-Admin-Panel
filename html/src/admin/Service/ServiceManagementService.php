<?php

declare(strict_types=1);

namespace App\Admin\Service;

use App\Admin\Model\Service;
use App\Admin\Repository\ServiceRepository;

/**
 * Service Management Service
 * Contains business logic and file upload processing for Services.
 */
class ServiceManagementService
{
    private ServiceRepository $serviceRepository;
    private string $uploadDir;

    public function __construct(ServiceRepository $serviceRepository, string $uploadDir = '')
    {
        $this->serviceRepository = $serviceRepository;
        if ($uploadDir !== '') {
            $this->uploadDir = rtrim($uploadDir, '/') . '/';
        } else {
            $baseDir = dirname(__DIR__, 2);
            $this->uploadDir = $baseDir . '/uploads/services/';
        }
    }

    /**
     * Get all services.
     *
     * @return Service[]
     */
    public function getAllServices(): array
    {
        return $this->serviceRepository->findAll();
    }

    /**
     * Find service by ID.
     */
    public function getServiceById(int $id): ?Service
    {
        return $this->serviceRepository->findById($id);
    }

    /**
     * Create a new service with optional image upload.
     *
     * @param array<string, mixed>|null $fileData
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function createService(string $serviceName, ?array $fileData): array
    {
        $serviceName = trim($serviceName);

        if (empty($serviceName)) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Service Name is required.'],
            ];
        }

        $imagePath = null;
        if ($fileData !== null && isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->processImageUpload($fileData);
            if (!$uploadResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Image upload failed',
                    'errors' => $uploadResult['errors'],
                ];
            }
            $imagePath = $uploadResult['path'];
        }

        $created = $this->serviceRepository->create($serviceName, $imagePath);

        if (!$created) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to add service to database.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Service added successfully.',
            'errors' => [],
        ];
    }

    /**
     * Update an existing service with optional image replacement.
     *
     * @param array<string, mixed>|null $fileData
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function updateService(int $id, string $serviceName, ?array $fileData): array
    {
        $serviceName = trim($serviceName);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Invalid service ID.'],
            ];
        }

        if (empty($serviceName)) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Service Name is required.'],
            ];
        }

        $existingService = $this->serviceRepository->findById($id);
        if ($existingService === null) {
            return [
                'success' => false,
                'message' => 'Not found',
                'errors' => ['Service not found.'],
            ];
        }

        $imagePath = null;
        if ($fileData !== null && isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->processImageUpload($fileData);
            if (!$uploadResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Image upload failed',
                    'errors' => $uploadResult['errors'],
                ];
            }
            $imagePath = $uploadResult['path'];
        }

        $updated = $this->serviceRepository->update($id, $serviceName, $imagePath);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to update service in database.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Service updated successfully.',
            'errors' => [],
        ];
    }

    /**
     * Delete a service by ID.
     *
     * @return array{success: bool, message: string, errors: string[]}
     */
    public function deleteService(int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['Invalid service ID.'],
            ];
        }

        $deleted = $this->serviceRepository->delete($id);
        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Database error',
                'errors' => ['Failed to delete service.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Service deleted successfully.',
            'errors' => [],
        ];
    }

    /**
     * Validate and process image upload securely.
     *
     * @param array<string, mixed> $file
     * @return array{success: bool, path: ?string, errors: string[]}
     */
    private function processImageUpload(array $file): array
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $maxSizeBytes = 10 * 1024 * 1024; // 10MB limit

        $fileTmpPath = (string) ($file['tmp_name'] ?? '');
        $fileName = (string) ($file['name'] ?? '');
        $fileSize = (int) ($file['size'] ?? 0);
        $fileError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($fileError !== UPLOAD_ERR_OK) {
            return ['success' => false, 'path' => null, 'errors' => ['File upload error code: ' . $fileError]];
        }

        if ($fileSize <= 0) {
            return ['success' => false, 'path' => null, 'errors' => ['Uploaded file is empty.']];
        }

        if ($fileSize > $maxSizeBytes) {
            return ['success' => false, 'path' => null, 'errors' => ['File size exceeds 10MB limit.']];
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'path' => null, 'errors' => ['Invalid file extension. Only JPG, PNG, WEBP, and GIF are allowed.']];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                return ['success' => false, 'path' => null, 'errors' => ['Invalid file MIME type (' . $mimeType . ').']];
            }
        }

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }
        @chmod($this->uploadDir, 0777);

        $newFileName = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $this->uploadDir . $newFileName;

        $saved = false;
        if (is_uploaded_file($fileTmpPath)) {
            $saved = @move_uploaded_file($fileTmpPath, $destPath);
        }

        if (!$saved) {
            $saved = @copy($fileTmpPath, $destPath);
        }

        if (!$saved) {
            return [
                'success' => false,
                'path' => null,
                'errors' => ['Failed to save uploaded file on server. Directory: ' . $this->uploadDir],
            ];
        }

        return [
            'success' => true,
            'path' => 'uploads/services/' . $newFileName,
            'errors' => [],
        ];
    }
}
