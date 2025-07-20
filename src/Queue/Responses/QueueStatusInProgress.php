<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Responses;

/**
 * @phpstan-import-type FalAIQueueLogEntry from QueueStatus
 * @phpstan-import-type FalAIQueueStatusInProgress from QueueStatus
 */
class QueueStatusInProgress extends QueueStatus
{
    /**
     * Create a new in-progress queue status instance.
     *
     * @param ?list<FalAIQueueLogEntry> $logs
     */
    public function __construct(
        string $cancelUrl,
        public readonly ?array $logs,
        string $requestId,
        string $responseUrl,
        string $statusUrl,
    ) {
        parent::__construct($cancelUrl, $requestId, $responseUrl, $statusUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @return FalAIQueueStatusInProgress
     */
    public function jsonSerialize(): array
    {
        return [
            'cancel_url' => $this->cancelUrl,
            'logs' => $this->logs,
            'request_id' => $this->requestId,
            'response_url' => $this->responseUrl,
            'status' => 'IN_PROGRESS',
            'status_url' => $this->statusUrl,
        ];
    }
}
