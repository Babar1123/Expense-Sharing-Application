<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    protected array $errors = [];

    public function __construct(string $message = 'Request failed.', array $errors = [], int $code = 400)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
