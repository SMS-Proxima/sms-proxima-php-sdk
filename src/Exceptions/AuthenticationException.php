<?php

namespace SmsProxima\Exceptions;

class AuthenticationException extends SmsProximaException
{
    public function __construct(string $message = 'Authentication failed.', ?string $apiCode = null)
    {
        parent::__construct($message, 403, $apiCode);
    }
}
