<?php

namespace SmsProxima;

use SmsProxima\Exceptions\AuthenticationException;
use SmsProxima\Exceptions\InsufficientCreditsException;
use SmsProxima\Exceptions\ValidationException;
use SmsProxima\Exceptions\SmsProximaException;

class SmsProxima
{
    const VERSION    = '1.0.0';
    const BASE_URL   = 'https://sms-proxima.com/api';
    const USER_AGENT = 'SmsProxima-PHP-SDK/1.0.0 (+https://sms-proxima.com)';

    private string $token;
    private int    $timeout;

    public function __construct(string $token, int $timeout = 10)
    {
        if (empty(trim($token))) {
            throw new \InvalidArgumentException('API token must not be empty.');
        }
        $this->token   = $token;
        $this->timeout = $timeout;
    }

    // -------------------------------------------------------------------------
    // PING
    // -------------------------------------------------------------------------

    /**
     * Test authentication.
     *
     * @return array{message: string, user: array}
     */
    public function ping(): array
    {
        return $this->request('GET', '/ping');
    }

    // -------------------------------------------------------------------------
    // CREDITS
    // -------------------------------------------------------------------------

    /**
     * Get available SMS credits.
     *
     * @return int
     */
    public function credits(): int
    {
        $response = $this->request('GET', '/credits');
        return (int) ($response['credits'] ?? 0);
    }

    // -------------------------------------------------------------------------
    // SEND
    // -------------------------------------------------------------------------

    /**
     * Send one or multiple SMS.
     *
     * @param  string|array $to          Recipient(s) — e.g. "33612345678" or ["33612345678", "33687654321"]
     * @param  string       $sender      Sender name (max 11 chars)
     * @param  string       $message     SMS content
     * @param  array        $options     Optional: stop, timeToSend, sandbox, idempotencyKey
     * @return array{status: int, ticket: string, cost: int, credits: int, total: int}
     */
    public function send($to, string $sender, string $message, array $options = []): array
    {
        $body = [
            'to'      => $to,
            'sender'  => $sender,
            'message' => $message,
            'stop'    => $options['stop'] ?? 1,
        ];

        if (!empty($options['timeToSend'])) {
            $body['timeToSend'] = $options['timeToSend'];
        }

        if (!empty($options['sandbox'])) {
            $body['sandbox'] = 1;
        }

        $headers = [];
        if (!empty($options['idempotencyKey'])) {
            $headers['Idempotency-Key'] = $options['idempotencyKey'];
        }

        return $this->request('POST', '/sms/send', $body, $headers);
    }

    // -------------------------------------------------------------------------
    // COUNT
    // -------------------------------------------------------------------------

    /**
     * Count characters and segments for a message.
     *
     * @param  string $message
     * @return array{nb_sms: int, nb_caracteres: int}
     */
    public function count(string $message): array
    {
        return $this->request('POST', '/sms/count', ['message' => $message]);
    }

    // -------------------------------------------------------------------------
    // CAMPAIGNS
    // -------------------------------------------------------------------------

    /**
     * Get paginated campaign history.
     *
     * @param  int $page
     * @return array
     */
    public function campaigns(int $page = 1): array
    {
        return $this->request('GET', '/campaigns', ['page' => $page]);
    }

    // -------------------------------------------------------------------------
    // DELIVERIES
    // -------------------------------------------------------------------------

    /**
     * Get delivery receipts for a campaign.
     *
     * @param  string $tracker  The ticket returned by send()
     * @param  int    $page
     * @return array
     */
    public function deliveries(string $tracker, int $page = 1): array
    {
        return $this->request('GET', '/sms/' . rawurlencode($tracker) . '/deliveries', ['page' => $page]);
    }

    // -------------------------------------------------------------------------
    // BLACKLIST
    // -------------------------------------------------------------------------

    /**
     * Get all blacklisted numbers.
     *
     * @return array<string>
     */
    public function getBlacklist(): array
    {
        return $this->request('GET', '/blacklist');
    }

    /**
     * Add a number to the blacklist.
     *
     * @param  string $mobile  E.164 format, e.g. "33612345678"
     * @return array{status: int, message: string, mobile: string}
     */
    public function addToBlacklist(string $mobile): array
    {
        return $this->request('POST', '/blacklist', ['mobile' => $mobile]);
    }

    /**
     * Remove a number from the blacklist.
     * Note: numbers that replied STOP cannot be removed.
     *
     * @param  string $mobile
     * @return array{status: int, message: string, mobile: string}
     */
    public function removeFromBlacklist(string $mobile): array
    {
        return $this->request('DELETE', '/blacklist/' . rawurlencode($mobile));
    }

    // -------------------------------------------------------------------------
    // HTTP LAYER
    // -------------------------------------------------------------------------

    /**
     * @throws AuthenticationException
     * @throws InsufficientCreditsException
     * @throws ValidationException
     * @throws SmsProximaException
     */
    private function request(string $method, string $endpoint, array $data = [], array $extraHeaders = []): array
    {
        $url = self::BASE_URL . $endpoint;

        // Append query string for GET requests with data
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'User-Agent'    => self::USER_AGENT,
        ], $extraHeaders);

        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = $key . ': ' . $value;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError) {
            throw new SmsProximaException('Network error: ' . $curlError);
        }

        $decoded = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SmsProximaException('Invalid JSON response from API (HTTP ' . $httpCode . ').');
        }

        // Map HTTP errors to typed exceptions
        switch ($httpCode) {
            case 401:
            case 403:
                throw new AuthenticationException(
                    $decoded['message'] ?? 'Authentication failed.',
                    $decoded['code']    ?? null
                );
            case 402:
                throw new InsufficientCreditsException(
                    $decoded['message'] ?? 'Insufficient credits.',
                    $decoded['credits'] ?? 0,
                    $decoded['cost']    ?? 0
                );
            case 422:
                throw new ValidationException(
                    $decoded['message'] ?? 'Validation error.',
                    $decoded['code']    ?? null,
                    $decoded['errors']  ?? []
                );
        }

        if ($httpCode >= 400) {
            throw new SmsProximaException(
                $decoded['message'] ?? 'API error.',
                $httpCode,
                $decoded['code'] ?? null
            );
        }

        return $decoded;
    }
}
