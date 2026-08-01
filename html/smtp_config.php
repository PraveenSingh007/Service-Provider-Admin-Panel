<?php

declare(strict_types=1);

/**
 * SMTP Email Configuration for Real OTP Delivery
 *
 * Fill in your SMTP credentials below (Gmail, Mailtrap, Outlook, SendGrid, etc.)
 */
return [
    'enabled'     => true,
    'host'        => 'smtp.gmail.com',
    'port'        => 587,
    'smtp_secure' => 'tls',
    'smtp_auth'   => true,
    'username'    => 'support.teckxpert@gmail.com',
    'password'    => 'Teckxpert@1982',
    'from_email'  => 'support.teckxpert@gmail.com',
    'from_name'   => 'tech-xpert Portal',
];
