<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['customer_user']);
unset($_SESSION['pending_otp_email']);

header('Location: login.php');
exit;
