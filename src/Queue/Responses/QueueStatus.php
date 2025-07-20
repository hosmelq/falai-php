<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Responses;

use function Safe\json_encode;

use JsonSerializable;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;
use Stringable;

/**
 * @phpstan-type FalAIQueueLogEntry array{level: 'DEBUG' | 'ERROR' | 'INFO' | 'STDERR' | 'STDOUT' | 'WARN', message: string, source: string, timestamp: string}
 * @phpstan-type FalAIQueueStatusCompleted array{cancel_url: string, logs: ?list<FalAIQueueLogEntry>, metrics: array<string, mixed>, request_id: string, response_url: string, status: 'COMPLETED', status_url: string}
 * @phpstan-type FalAIQueueStatusInProgress array{cancel_url: string, logs: ?list<FalAIQueueLogEntry>, request_id: string, response_url: string, status: 'IN_PROGRESS', status_url: string}
 * @phpstan-type FalAIQueueStatusQueued array{cancel_url: string, queue_position: int, request_id: string, response_url: string, status: 'IN_QUEUE', status_url: string}
 * @phpstan-type FalAIQueueStatus FalAIQueueStatusCompleted|FalAIQueueStatusInProgress|FalAIQueueStatusQueued
 */
abstract class QueueStatus implements JsonSerializable, Stringable, WithResponse
{
    use HasResponse;

    /**
     * Create a new queue status instance.
     */
    public function __construct(
        public readonly string $cancelUrl,
        public readonly string $requestId,
        public readonly string $responseUrl,
        public readonly string $statusUrl,
    ) {
    }

    /**
     * Parse the raw queue status data and return the appropriate queue status instance.
     *
     * @param FalAIQueueStatus $data
     */
    public static function parseStatus(array $data): QueueStatusCompleted|QueueStatusInProgress|QueueStatusQueued
    {
        return match ($data['status']) {
            'COMPLETED' => new QueueStatusCompleted(
                cancelUrl: $data['cancel_url'],
                logs: $data['logs'] ?? null,
                metrics: $data['metrics'] ?? [], // @phpstan-ignore-line - Legacy apps might not return metrics.
                requestId: $data['request_id'],
                responseUrl: $data['response_url'],
                statusUrl: $data['status_url'],
            ),
            'IN_PROGRESS' => new QueueStatusInProgress(
                cancelUrl: $data['cancel_url'],
                logs: $data['logs'] ?? null,
                requestId: $data['request_id'],
                responseUrl: $data['response_url'],
                statusUrl: $data['status_url'],
            ),
            'IN_QUEUE' => new QueueStatusQueued(
                cancelUrl: $data['cancel_url'],
                position: $data['queue_position'],
                requestId: $data['request_id'],
                responseUrl: $data['response_url'],
                statusUrl: $data['status_url'],
            ),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
