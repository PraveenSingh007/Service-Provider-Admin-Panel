<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Model\Customer;
use App\User\Repository\CustomerRepository;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../Lib/PHPMailer/PHPMailer.php';

/**
 * CustomerAuthService
 * Business logic for Customer OTP Sign In and Profile Completion.
 */
class CustomerAuthService
{
    private CustomerRepository $repository;

    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Generate and send 6-digit OTP code to email via PHPMailer SMTP.
     *
     * @return array{success: bool, message: string, otp_code?: string, errors?: string[]}
     */
    public function requestOtp(string $email): array
    {
        $email = strtolower(trim($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address.',
                'errors' => ['Please enter a valid email address.'],
            ];
        }

        // Generate 6-digit numeric OTP code
        try {
            $otpCode = (string) random_int(100000, 999999);
        } catch (\Throwable $e) {
            $otpCode = (string) sprintf("%06d", mt_rand(100000, 999999));
        }

        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 Minutes validity

        // Save OTP to customer_otp table
        $saved = $this->repository->createOtp($email, $otpCode, $expiresAt);

        if (!$saved) {
            return [
                'success' => false,
                'message' => 'Failed to generate OTP.',
                'errors' => ['Database error during OTP creation.'],
            ];
        }

        // Load SMTP Configuration
        $smtpConfigFile = __DIR__ . '/../../smtp_config.php';
        $smtpConfig = file_exists($smtpConfigFile) ? require $smtpConfigFile : ['enabled' => false];

        $emailSent = false;
        $smtpError = null;

        if (!empty($smtpConfig['enabled'])) {
            $mail = new PHPMailer();
            $mail->Host = (string) ($smtpConfig['host'] ?? 'smtp.gmail.com');
            $mail->Port = (int) ($smtpConfig['port'] ?? 587);
            $mail->SMTPSecure = (string) ($smtpConfig['smtp_secure'] ?? 'tls');
            $mail->SMTPAuth = (bool) ($smtpConfig['smtp_auth'] ?? true);
            $mail->Username = (string) ($smtpConfig['username'] ?? '');
            $mail->Password = (string) ($smtpConfig['password'] ?? '');

            $mail->setFrom(
                (string) ($smtpConfig['from_email'] ?? $smtpConfig['username']),
                (string) ($smtpConfig['from_name'] ?? 'Service Provider Portal')
            );
            $mail->addAddress($email);
            $mail->Subject = 'Your Service Portal Login OTP: ' . $otpCode;
            $mail->isHTML(true);

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <h2 style='color: #696cff; text-align: center;'>Service Provider Portal</h2>
                    <p>Hello,</p>
                    <p>Your 6-digit One-Time Password (OTP) for logging into your customer account is:</p>
                    <div style='background-color: #f5f5f9; text-align: center; padding: 15px; font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #333; border-radius: 6px; margin: 20px 0;'>
                        {$otpCode}
                    </div>
                    <p style='color: #666; font-size: 13px;'>This OTP code is valid for 10 minutes. Do not share this code with anyone.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;' />
                    <p style='color: #999; font-size: 11px; text-align: center;'>© " . date('Y') . " Service Provider Portal. All rights reserved.</p>
                </div>
            ";

            $emailSent = $mail->send();
            if (!$emailSent) {
                $smtpError = $mail->getErrorInfo();
            }
        } else {
            // Fallback to PHP native mail function
            $subject = 'Your Service Portal Login OTP: ' . $otpCode;
            $message = "Hello,\n\nYour OTP for logging in is: {$otpCode}\n\nValid for 10 minutes.";
            $headers = 'From: no-reply@serviceprovider.com';
            @mail($email, $subject, $message, $headers);
        }

        $resMessage = "OTP code sent to {$email}.";
        if ($smtpError) {
            $resMessage .= " (SMTP Warning: {$smtpError})";
        }

        return [
            'success' => true,
            'message' => $resMessage,
            'otp_code' => $otpCode,
        ];
    }

    /**
     * Verify submitted OTP code and log the customer in.
     *
     * @return array{success: bool, message: string, is_profile_completed?: bool, user?: array<string, mixed>, errors?: string[]}
     */
    public function verifyOtpAndLogin(string $email, string $otpCode): array
    {
        $email = strtolower(trim($email));
        $otpCode = trim($otpCode);

        if ($email === '' || $otpCode === '') {
            return [
                'success' => false,
                'message' => 'Email and OTP code are required.',
                'errors' => ['Please enter both your email and the 6-digit OTP code.'],
            ];
        }

        $isValid = $this->repository->verifyOtp($email, $otpCode);

        if (!$isValid) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
                'errors' => ['The OTP code entered is incorrect or has expired. Please request a new OTP.'],
            ];
        }

        // Fetch or create customer record
        $user = $this->repository->findOrCreateByEmail($email);

        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve user profile.',
                'errors' => ['Customer creation/retrieval error.'],
            ];
        }

        // Set customer session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['customer_user'] = $user->toArray();

        return [
            'success' => true,
            'message' => 'OTP verified successfully. Login granted.',
            'is_profile_completed' => $user->isProfileCompleted(),
            'user' => $user->toArray(),
        ];
    }

    /**
     * Save customer personal information profile (first_name, last_name, mobile_no, address).
     *
     * @param int $userId
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string, errors?: string[]}
     */
    public function updatePersonalInformation(int $userId, array $data): array
    {
        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid user ID.',
                'errors' => ['Session expired. Please log in again.'],
            ];
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $mobile = trim((string) ($data['mobile_no'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));

        $errors = [];
        if ($firstName === '') { $errors[] = 'First name is required.'; }
        if ($lastName === '') { $errors[] = 'Last name is required.'; }
        if ($mobile === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $mobile)) { $errors[] = 'Valid mobile number is required.'; }
        if ($address === '') { $errors[] = 'Address is required.'; }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ];
        }

        $saved = $this->repository->updateProfile($userId, $data);

        if (!$saved) {
            return [
                'success' => false,
                'message' => 'Failed to save personal information.',
                'errors' => ['Database update error.'],
            ];
        }

        // Refresh session data
        $updatedUser = $this->repository->findById($userId);
        if ($updatedUser !== null) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['customer_user'] = $updatedUser->toArray();
        }

        return [
            'success' => true,
            'message' => 'Personal information saved successfully!',
        ];
    }
}
