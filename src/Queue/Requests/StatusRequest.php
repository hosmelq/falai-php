<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Requests;

use HosmelQ\FalAI\Queue\EndpointBuilder;
use HosmelQ\FalAI\Queue\Responses\QueueStatus;
use HosmelQ\FalAI\Queue\Responses\QueueStatusCompleted;
use HosmelQ\FalAI\Queue\Responses\QueueStatusInProgress;
use HosmelQ\FalAI\Queue\Responses\QueueStatusQueued;
use JsonException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * @phpstan-import-type FalAIQueueStatus from QueueStatus
 */
class StatusRequest extends Request
{
    /**
     * {@inheritDoc}
     */
    protected Method $method = Method::GET;

    /**
     * Create a new StatusRequest instance.
     */
    public function __construct(
        protected readonly string $endpointId,
        protected readonly string $requestId,
        protected readonly bool $withLogs = false,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): QueueStatusCompleted|QueueStatusInProgress|QueueStatusQueued
    {
        /** @var FalAIQueueStatus $data */
        $data = $response->json();

        return QueueStatus::parseStatus($data);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return EndpointBuilder::buildStatusUrl($this->endpointId, $this->requestId);
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultQuery(): array
    {
        return [
            'logs' => $this->withLogs,
        ];
    }
}
