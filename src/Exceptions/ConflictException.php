<?php

namespace SmsProxima\Exceptions;

class ConflictException extends SmsProximaException
{
    public function __construct(string $message = 'Conflict.', ?string $apiCode = null)
    {
        parent::__construct($message, 409, $apiCode);
    }
}