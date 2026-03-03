<?php

namespace SmsProxima\Exceptions;

class SmsProximaException extends \RuntimeException
{
    private ?string $apiCode;

    public function __construct(string $message = '', int $httpCode = 0, ?string $apiCode = null)
    {
        parent::__construct($message, $httpCode);
        $this->apiCode = $apiCode;
    }

    /**
     * The API error code returned by SMS Proxima (e.g. "NOT_FOUND").
     */
    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }
}
