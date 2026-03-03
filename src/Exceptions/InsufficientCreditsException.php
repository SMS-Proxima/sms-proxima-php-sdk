<?php

namespace SmsProxima\Exceptions;

class InsufficientCreditsException extends SmsProximaException
{
    private int $available;
    private int $required;

    public function __construct(string $message = 'Insufficient credits.', int $available = 0, int $required = 0)
    {
        parent::__construct($message, 402, 'INSUFFICIENT_CREDITS');
        $this->available = $available;
        $this->required  = $required;
    }

    public function getAvailableCredits(): int
    {
        return $this->available;
    }

    public function getRequiredCredits(): int
    {
        return $this->required;
    }
}
