<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue;

use Generator;
use HosmelQ\FalAI\Queue\Requests\CancelRequest;
use HosmelQ\FalAI\Queue\Requests\ResultRequest;
use HosmelQ\FalAI\Queue\Requests\StatusRequest;
use HosmelQ\FalAI\Queue\Requests\StatusStreamRequest;
use HosmelQ\FalAI\Queue\Requests\SubmitRequest;
use HosmelQ\FalAI\Queue\Responses\QueueStatus;
use HosmelQ\FalAI\Queue\Responses\QueueStatusCompleted;
use HosmelQ\FalAI\Queue\Responses\QueueStatusInProgress;
use HosmelQ\FalAI\Queue\Responses\QueueStatusQueued;
use HosmelQ\SSE\Saloon\Responses\SSEResponse;
use HosmelQ\SSE\SSEProtocolException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\BaseResource;

/**
 * @phpstan-import-type FalAIQueueStatus from QueueStatus
 */
class QueueResource extends BaseResource
{
    /**
     * Cancel a queued request.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function cancel(string $endpointId, string $requestId): bool
    {
        $request = new CancelRequest($endpointId, $requestId);

        return $this->connector->send($request)->successful();
    }

    /**
     * Get the result of a completed request.
     *
     * @return array<string, mixed>
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function result(string $endpointId, string $requestId): array
    {
        /** @var array<string, mixed> $response */
        $response = $this->connector->send(new ResultRequest($endpointId, $requestId))->dto();

        return $response;
    }

    /**
     * Check the status of a queued request.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function status(string $endpointId, string $requestId, bool $withLogs = false): QueueStatusCompleted|QueueStatusInProgress|QueueStatusQueued
    {
        /** @var QueueStatusCompleted|QueueStatusInProgress|QueueStatusQueued $response */
        $response = $this->connector->send(new StatusRequest($endpointId, $requestId, $withLogs))->dto();

        return $response;
    }

    /**
     * Stream real-time status updates for a queued request.
     *
     * @return Generator<QueueStatusCompleted|QueueStatusInProgress|QueueStatusQueued>
     *
     * @throws FatalRequestException
     * @throws RequestException
     * @throws SSEProtocolException
     */
    public function streamStatus(string $endpointId, string $requestId, bool $withLogs = false): Generator
    {
        /** @var SSEResponse $response */
        $response = $this->connector->send(new StatusStreamRequest($endpointId, $requestId, $withLogs));

        foreach ($response->asEventSource()->events() as $event) {
            /** @var ?FalAIQueueStatus $data */
            $data = $event->json();

            if (is_array($data)) {
                yield QueueStatus::parseStatus($data);
            }
        }
    }

    /**
     * Submit a request to the queue.
     *
     * @param array<string, mixed> $input
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function submit(
        string $endpointId,
        array $input,
        ?string $hint = null,
        ?QueuePriority $priority = null,
        ?string $webhookUrl = null,
    ): QueueStatusQueued {
        /** @var QueueStatusQueued $response */
        $response = $this->connector->send(new SubmitRequest(
            endpointId: $endpointId,
            input: $input,
            hint: $hint,
            priority: $priority,
            webhookUrl: $webhookUrl
        ))->dto();

        return $response;
    }
}
