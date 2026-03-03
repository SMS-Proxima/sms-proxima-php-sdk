<?php

namespace SmsProxima\Exceptions;

class ValidationException extends SmsProximaException
{
    private array $errors;

    public function __construct(string $message = 'Validation error.', ?string $apiCode = null, array $errors = [])
    {
        parent::__construct($message, 422, $apiCode);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
