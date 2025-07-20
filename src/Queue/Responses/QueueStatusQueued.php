<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Responses;

/**
 * @phpstan-import-type FalAIQueueStatusQueued from QueueStatus
 */
class QueueStatusQueued extends QueueStatus
{
    /**
     * Create a new queued status instance.
     */
    public function __construct(
        string $cancelUrl,
        public readonly int $position,
        string $requestId,
        string $responseUrl,
        string $statusUrl,
    ) {
        parent::__construct($cancelUrl, $requestId, $responseUrl, $statusUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @return FalAIQueueStatusQueued
     */
    public function jsonSerialize(): array
    {
        return [
            'cancel_url' => $this->cancelUrl,
            'queue_position' => $this->position,
            'request_id' => $this->requestId,
            'response_url' => $this->responseUrl,
            'status' => 'IN_QUEUE',
            'status_url' => $this->statusUrl,
        ];
    }
}
