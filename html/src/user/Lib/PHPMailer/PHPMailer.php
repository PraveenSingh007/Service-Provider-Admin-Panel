<?php

namespace PHPMailer\PHPMailer;

require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/SMTP.php';

class PHPMailer
{
    public $Host = 'smtp.gmail.com';
    public $Port = 587;
    public $SMTPSecure = 'tls'; // 'tls' or 'ssl'
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $isHTML = false;

    private $to = [];
    private $smtp;
    private $errorInfo = '';

    public function __construct($exceptions = null)
    {
        $this->smtp = new SMTP();
    }

    public function isSMTP()
    {
        // Default mode
    }

    public function addAddress($address, $name = '')
    {
        $this->to[] = [$address, $name];
    }

    public function setFrom($address, $name = '')
    {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function isHTML($ishtml = true)
    {
        $this->isHTML = (bool)$ishtml;
    }

    public function send(): bool
    {
        try {
            $prefix = ($this->SMTPSecure === 'ssl') ? 'ssl://' : '';
            $connected = $this->smtp->connect($prefix . $this->Host, $this->Port);

            if (!$connected) {
                $err = $this->smtp->getError();
                $this->errorInfo = 'Connect failed: ' . ($err['errstr'] ?? 'Unknown');
                return false;
            }

            $this->smtp->hello($_SERVER['SERVER_NAME'] ?? 'localhost');

            if ($this->SMTPSecure === 'tls') {
                if (!$this->smtp->startTLS()) {
                    $this->errorInfo = 'TLS start failed';
                    return false;
                }
                $this->smtp->hello($_SERVER['SERVER_NAME'] ?? 'localhost');
            }

            if ($this->SMTPAuth) {
                if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                    $this->errorInfo = 'Authentication failed. Please check SMTP Username and Password.';
                    return false;
                }
            }

            if (!$this->smtp->mail($this->From ?: $this->Username)) {
                $this->errorInfo = 'MAIL FROM command failed';
                return false;
            }

            foreach ($this->to as $recipient) {
                if (!$this->smtp->recipient($recipient[0])) {
                    $this->errorInfo = 'RCPT TO failed for: ' . $recipient[0];
                    return false;
                }
            }

            $mimeType = $this->isHTML ? 'text/html' : 'text/plain';
            $headers = "From: " . ($this->FromName ? "{$this->FromName} <{$this->From}>" : $this->From) . "\r\n";
            $headers .= "To: " . $this->to[0][0] . "\r\n";
            $headers .= "Subject: {$this->Subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: {$mimeType}; charset=UTF-8\r\n\r\n";

            $messageData = $headers . $this->Body;

            if (!$this->smtp->data($messageData)) {
                $this->errorInfo = 'DATA send failed';
                return false;
            }

            $this->smtp->quit();
            return true;

        } catch (\Throwable $e) {
            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    public function getErrorInfo(): string
    {
        return $this->errorInfo;
    }
}
