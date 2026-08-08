<?php 
declare(strict_types=1);

// Get the current protocol (http or https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// Get the current host (localhost)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Get the full requested URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Check if the URL path ends with '/admin' or '/admin/'
if (preg_match('/\/admin\/?$/', $requestUri)) {
    header("Location: $protocol://$host/Service-Provider-Admin-Panel/html/src/admin/index.php");
    exit;
}

// Default redirect if '/admin' is not in the URL
header("Location: $protocol://$host/Service-Provider-Admin-Panel/html/src/user/index.php");
exit;
