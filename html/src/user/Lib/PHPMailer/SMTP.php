<?php

namespace PHPMailer\PHPMailer;

class SMTP
{
    const VERSION = '6.8.0';
    const CRLF = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;

    private $smtp_conn;
    private $error = [];
    private $helo_rply;

    public function connect($host, $port = null, $timeout = 10, $options = []): bool
    {
        $this->error = [];
        $port = $port ?: self::DEFAULT_PORT;
        
        $socket_context = stream_context_create($options);
        set_error_handler([$this, 'errorHandler']);
        
        $this->smtp_conn = stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $socket_context
        );
        
        restore_error_handler();

        if (!is_resource($this->smtp_conn)) {
            $this->error = ['error' => 'Failed to connect to server', 'errno' => $errno, 'errstr' => $errstr];
            return false;
        }

        stream_set_timeout($this->smtp_conn, $timeout);
        $announce = $this->get_lines();
        return true;
    }

    public function authenticate($username, $password, $authtype = null): bool
    {
        $this->client_send("AUTH LOGIN" . self::CRLF);
        $this->get_lines();
        $this->client_send(base64_encode($username) . self::CRLF);
        $this->get_lines();
        $this->client_send(base64_encode($password) . self::CRLF);
        $reply = $this->get_lines();
        return (substr($reply, 0, 3) === '235');
    }

    public function hello($host = ''): bool
    {
        $this->client_send("EHLO " . ($host ?: 'localhost') . self::CRLF);
        $this->helo_rply = $this->get_lines();
        return true;
    }

    public function startTLS(): bool
    {
        $this->client_send("STARTTLS" . self::CRLF);
        $reply = $this->get_lines();
        if (substr($reply, 0, 3) !== '220') {
            return false;
        }
        return stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    }

    public function mail($from): bool
    {
        $this->client_send("MAIL FROM:<" . $from . ">" . self::CRLF);
        $reply = $this->get_lines();
        return (substr($reply, 0, 3) === '250');
    }

    public function recipient($to): bool
    {
        $this->client_send("RCPT TO:<" . $to . ">" . self::CRLF);
        $reply = $this->get_lines();
        return (substr($reply, 0, 3) === '250' || substr($reply, 0, 3) === '251');
    }

    public function data($msg_data): bool
    {
        $this->client_send("DATA" . self::CRLF);
        $this->get_lines();
        $this->client_send($msg_data . self::CRLF . "." . self::CRLF);
        $reply = $this->get_lines();
        return (substr($reply, 0, 3) === '250');
    }

    public function quit(): void
    {
        if (is_resource($this->smtp_conn)) {
            $this->client_send("QUIT" . self::CRLF);
            fclose($this->smtp_conn);
        }
    }

    private function client_send($data): int
    {
        return fwrite($this->smtp_conn, $data);
    }

    private function get_lines(): string
    {
        $data = '';
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = fgets($this->smtp_conn, 515);
            if ($str === false) break;
            $data .= $str;
            if (isset($str[3]) && $str[3] === ' ') break;
        }
        return $data;
    }

    public function errorHandler($errno, $errmsg, $errfile = null, $errline = null): void
    {
        $this->error = ['error' => $errmsg, 'errno' => $errno];
    }

    public function getError(): array
    {
        return $this->error;
    }
}
