<?php

namespace SmsProxima\Exceptions;

class MobileBlacklistedException extends ValidationException
{
    public function __construct(string $message = 'Mobile is blacklisted (STOP received).')
    {
        parent::__construct($message, 'MOBILE_BLACKLISTED', []);
    }
}