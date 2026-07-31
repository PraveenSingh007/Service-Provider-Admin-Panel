<?php

declare(strict_types=1);

/**
 * SMTP Email Configuration for Real OTP Delivery
 *
 * Fill in your SMTP credentials below (Gmail, Mailtrap, Outlook, SendGrid, etc.)
 */
return [
    'enabled'     => false, // Set to true after entering your SMTP Username and Password below
    'host'        => 'smtp.gmail.com',
    'port'        => 587,
    'smtp_secure' => 'tls', // 'tls' (Port 587) or 'ssl' (Port 465)
    'smtp_auth'   => true,
    'username'    => 'your.email@gmail.com', // Enter your SMTP email address
    'password'    => 'xxxx xxxx xxxx xxxx',   // Enter your Gmail App Password (16-character code)
    'from_email'  => 'your.email@gmail.com',
    'from_name'   => 'Service Provider Portal',
];
