<?php

declare(strict_types=1);

namespace HosmelQ\FalAI;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;

abstract class BaseFalConnector extends Connector
{
    /**
     * Base URL for queue-based API calls.
     */
    public const QUEUE_URL_FORMAT = 'https://queue.fal.run';

    /**
     * Base URL for direct synchronous API calls.
     */
    public const RUN_URL_FORMAT = 'https://fal.run';

    /**
     * User agent string for HTTP requests.
     */
    public const USER_AGENT = 'hosmelq/fal-client (PHP)';

    /**
     * Initialize the connector with authentication token.
     */
    public function __construct(private readonly string $token)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->token, 'Key');
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => self::USER_AGENT,
        ];
    }
}
