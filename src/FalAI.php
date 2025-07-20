<?php

declare(strict_types=1);

namespace HosmelQ\FalAI;

use HosmelQ\FalAI\Queue\QueueResource;
use HosmelQ\FalAI\Requests\RunRequest;
use InvalidArgumentException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class FalAI
{
    /**
     * Lazy-initialized connector for queue operations.
     */
    private ?FalQueueConnector $queueConnector = null;

    /**
     * Lazy-initialized connector for synchronous operations.
     */
    private ?FalRunConnector $runConnector = null;

    /**
     * Initialize the client.
     */
    public function __construct(private readonly string $token)
    {
    }

    /**
     * Create a new FalAI instance.
     */
    public static function client(?string $token = null): self
    {
        $token ??= $_SERVER['FAL_KEY'] ?? null;

        if (! is_string($token)) {
            throw new InvalidArgumentException('FAL_KEY must be provided either as parameter or environment variable');
        }

        return new self($token);
    }

    /**
     * Get the queue connector instance.
     */
    public function getQueueConnector(): FalQueueConnector
    {
        return $this->queueConnector ??= new FalQueueConnector($this->token);
    }

    /**
     * Get the run connector instance.
     */
    public function getRunConnector(): FalRunConnector
    {
        return $this->runConnector ??= new FalRunConnector($this->token);
    }

    /**
     * Get the queue resource for queue-based operations.
     */
    public function queue(): QueueResource
    {
        return new QueueResource($this->getQueueConnector());
    }

    /**
     * Direct synchronous execution of a model.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function run(string $endpointId, array $input): array
    {
        /** @var array<string, mixed> $response */
        $response = $this->getRunConnector()->send(
            new RunRequest($endpointId, $input)
        )->dto();

        return $response;
    }
}
