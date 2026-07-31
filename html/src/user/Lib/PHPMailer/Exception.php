<?php

namespace PHPMailer\PHPMailer;

class Exception extends \Exception
{
    public function errorMessage(): string
    {
        return $this->getMessage();
    }
}
