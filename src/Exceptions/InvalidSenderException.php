<?php

namespace SmsProxima\Exceptions;

class InvalidSenderException extends ValidationException
{
    public function __construct(string $message = 'Invalid or unauthorized sender.', ?string $apiCode = null)
    {
        parent::__construct($message, $apiCode ?? 'SENDER_INVALID', []);
    }
}