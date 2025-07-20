<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Responses;

/**
 * @phpstan-import-type FalAIQueueLogEntry from QueueStatus
 * @phpstan-import-type FalAIQueueStatusCompleted from QueueStatus
 */
class QueueStatusCompleted extends QueueStatus
{
    /**
     * Create a new completed queue status instance.
     *
     * @param ?list<FalAIQueueLogEntry> $logs
     * @param array<string, mixed> $metrics
     */
    public function __construct(
        string $cancelUrl,
        public readonly ?array $logs,
        public readonly array $metrics,
        string $requestId,
        string $responseUrl,
        string $statusUrl,
    ) {
        parent::__construct($cancelUrl, $requestId, $responseUrl, $statusUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @return FalAIQueueStatusCompleted
     */
    public function jsonSerialize(): array
    {
        return [
            'cancel_url' => $this->cancelUrl,
            'logs' => $this->logs,
            'metrics' => $this->metrics,
            'request_id' => $this->requestId,
            'response_url' => $this->responseUrl,
            'status' => 'COMPLETED',
            'status_url' => $this->statusUrl,
        ];
    }
}
