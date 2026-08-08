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
    /**
     * Generate or reuse and send 6-digit OTP code to email via PHPMailer SMTP.
     * OTP is valid for 30 minutes. If an active OTP exists, the same OTP is re-sent.
     *
     * @return array{success: bool, message: string, otp_code?: string, errors?: string[]}
     */
    public function requestOtp(string $email, bool $forceNew = false): array
    {
        $email = strtolower(trim($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address.',
                'errors' => ['Please enter a valid email address.'],
            ];
        }

        $activeOtp = $this->repository->getActiveOtp($email);
        $isReused = false;

        if ($activeOtp !== null && !empty($activeOtp['otp_code']) && !$forceNew) {
            // Reuse existing active OTP without creating a new record in DB
            $otpCode = (string) $activeOtp['otp_code'];
            $isReused = true;
        } else {
            // Generate new 6-digit numeric OTP code valid for 30 minutes (1800s)
            try {
                $otpCode = (string) random_int(100000, 999999);
            } catch (\Throwable $e) {
                $otpCode = (string) sprintf("%06d", mt_rand(100000, 999999));
            }

            // Save OTP to customer_otp table (MySQL sets expires_at to DATE_ADD(NOW(), INTERVAL 30 MINUTE))
            $saved = $this->repository->createOtp($email, $otpCode);

            if (!$saved) {
                return [
                    'success' => false,
                    'message' => 'Failed to generate OTP.',
                    'errors' => ['Database error during OTP creation.'],
                ];
            }
        }

        // Calculate expiration timestamp using active OTP if reused, otherwise 30 minutes from now
        $expiresAtTimestamp = isset($activeOtp['expires_at']) && $isReused ? strtotime($activeOtp['expires_at']) : (time() + 1800);
        $formattedExpiry = date('h:i A \I\S\T', $expiresAtTimestamp);

        // Load SMTP configuration. The SMTP config file lives at html/smtp_config.php.
        $smtpConfigFile = __DIR__ . '/../../../smtp_config.php';
        if (!file_exists($smtpConfigFile)) {
            error_log('CustomerAuthService: SMTP config file not found at ' . $smtpConfigFile);
            $smtpConfig = ['enabled' => false];
        } else {
            $smtpConfig = require $smtpConfigFile;
        }

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
                (string) ($smtpConfig['from_email'] ?? $smtpConfig['username'] ?? 'no-reply@example.com'),
                (string) ($smtpConfig['from_name'] ?? 'Tech-xpert Portal')
            );
            $mail->addAddress($email);
            $mail->Subject = 'Tech-xpert Verification Code: ' . $otpCode;
            $mail->isHTML(true);

            $logoPath = __DIR__ . '/../../../uploads/logo.png';
            $logoSrc = '';
            if (file_exists($logoPath)) {
                $logoContent = file_get_contents($logoPath);
                if ($logoContent !== false) {
                    $logoSrc = 'data:image/png;base64,' . base64_encode($logoContent);
                }
            }

            $logoImageHtml = $logoSrc !== '' ? "<img src='{$logoSrc}' alt='Tech-xpert Logo' width='108' style='display:block; margin: 0 auto 14px; max-width: 120px; height: auto;' />" : '';

            $mail->Body = "
                <div style='font-family: \"Segoe UI\", Helvetica, Arial, sans-serif; max-width: 540px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);'>
                    <!-- Header Banner -->
                    <div style='background: linear-gradient(135deg, #61BEF1 0%, #3938b3 100%); padding: 25px 20px; text-align: center;'>
                        {$logoImageHtml}
                        <h2 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 0.5px;'>Tech-xpert Portal</h2>
                    </div>

                    <!-- Body Content -->
                    <div style='padding: 30px 25px; color: #4a5568; font-size: 15px; line-height: 1.6;'>
                        <p style='margin-top: 0; font-size: 16px; color: #2d3748;'>Hello,</p>
                        <p>We received a request to access your account via <strong>{$email}</strong>. Please use the following 6-digit One-Time Password (OTP) to complete your verification:</p>
                        
                        <!-- OTP Box -->
                        <div style='background-color: #f8fafc; border: 2px dashed #61BEF1; text-align: center; padding: 20px; border-radius: 10px; margin: 25px 0;'>
                            <span style='display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #718096; letter-spacing: 1px; margin-bottom: 8px;'>Your One-Time Password</span>
                            <span style='font-size: 34px; font-weight: 800; letter-spacing: 10px; color: #61BEF1; font-family: monospace;'>{$otpCode}</span>
                        </div>

                        <!-- Expiry Notification -->
                        <div style='background-color: #fffaf0; border-left: 4px solid #dd6b20; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px;'>
                            <p style='margin: 0; color: #9c4221; font-size: 13px;'>
                                ⏳ <strong>Validity:</strong> This OTP is valid for <strong>30 minutes</strong>.
                            </p>
                        </div>

                        <p style='color: #718096; font-size: 13px; margin-bottom: 0;'>If you did not request this OTP code, please ignore this email or contact support if you suspect unauthorized access.</p>
                    </div>

                    <!-- Footer -->
                    <div style='background-color: #f7fafc; padding: 18px 25px; border-top: 1px solid #edf2f7; text-align: center; font-size: 12px; color: #a0aec0;'>
                        <p style='margin: 0 0 4px 0;'>Need help? Contact support at <a href='mailto:support.teckxpert@gmail.com' style='color: #61BEF1; text-decoration: none;'>support.teckxpert@gmail.com</a></p>
                        <p style='margin: 0;'>© " . date('Y') . " Tech-xpert Services Pvt Ltd. All rights reserved.</p>
                    </div>
                </div>
            ";

            $mail->AltBody = "Hello,\n\nYour Tech-xpert One-Time Password (OTP) is: {$otpCode}\n\nThis code is valid for 30 minutes (expires at {$formattedExpiry}). Do not share this code with anyone.\n\n© " . date('Y') . " Tech-xpert Services Pvt Ltd.";
            $emailSent = $mail->send();
            if (!$emailSent) {
                $smtpError = $mail->getErrorInfo();
                return [
                    'success' => false,
                    'message' => 'Email delivery failed: ' . $smtpError,
                    'errors' => ['SMTP Error: ' . $smtpError],
                ];
            }
        } else {
            // Fallback to PHP native mail function
            $subject = 'Tech-xpert Verification Code: ' . $otpCode;
            $message = "Hello,\n\nYour OTP for logging in is: {$otpCode}\n\nValid for 30 minutes (expires at {$formattedExpiry}).";
            $headers = 'From: no-reply@techxpert.com';
            @mail($email, $subject, $message, $headers);
        }

        $resMessage = "OTP has been successfully sent to {$email}. Please check your inbox or spam folder (valid until {$formattedExpiry}).";

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
