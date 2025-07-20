<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Requests;

use HosmelQ\FalAI\Queue\QueuePriority;
use HosmelQ\FalAI\Queue\Responses\QueueStatus;
use HosmelQ\FalAI\Queue\Responses\QueueStatusQueued;
use JsonException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * @phpstan-import-type FalAIQueueStatusQueued from QueueStatus
 */
class SubmitRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * {@inheritDoc}
     */
    protected Method $method = Method::POST;

    /**
     * Create a new SubmitRequest instance.
     *
     * @param array<string, mixed> $input
     */
    public function __construct(
        protected readonly string $endpointId,
        protected readonly array $input,
        protected readonly ?string $hint = null,
        protected readonly ?QueuePriority $priority = null,
        protected readonly ?string $webhookUrl = null,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): QueueStatusQueued
    {
        /** @var FalAIQueueStatusQueued $data */
        $data = $response->json();

        return new QueueStatusQueued(
            cancelUrl: $data['cancel_url'],
            position: $data['queue_position'],
            requestId: $data['request_id'],
            responseUrl: $data['response_url'],
            statusUrl: $data['status_url'],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return $this->endpointId;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->input;
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultHeaders(): array
    {
        $headers = [];

        if (! is_null($this->hint)) {
            $headers['X-Fal-Runner-Hint'] = $this->hint;
        }

        if (! is_null($this->priority)) {
            $headers['X-Fal-Queue-Priority'] = $this->priority->value;
        }

        return $headers;
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultQuery(): array
    {
        if (is_null($this->webhookUrl)) {
            return [];
        }

        return [
            'fal_webhook' => $this->webhookUrl,
        ];
    }
}
